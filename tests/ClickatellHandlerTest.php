<?php

namespace Tylercd100\Monolog\Tests;

use Exception;
use PHPUnit_Framework_TestCase;
use Tylercd100\Monolog\Handler\ClickatellHandler;
use Monolog\Logger;
use Tylercd100\Monolog\Tests\TestCase;

class ClickatellHandlerTest extends TestCase
{
    private $res;

    /** @var  ClickatellHandler */
    private $handler;
    
    public function testCanBeInstantiatedAndProvidesDefaultFormatter()
    {
        $handler = new ClickatellHandler('token', '+15555555555', '+16666666666');

        $this->assertInstanceOf('Tylercd100\\Monolog\\Formatter\\SMSFormatter', $handler->getFormatter());
        $this->assertAttributeEquals('token',        'authToken',  $handler);
        $this->assertAttributeEquals('+15555555555', 'fromNumber', $handler);
        $this->assertAttributeEquals('+16666666666', 'toNumber',   $handler);
    }

    public function testItThrowsExceptionWhenUsingDifferentVersionOtherThanV1()
    {
        $this->setExpectedException(Exception::class);
        $handler = new ClickatellHandler('token', 'auth_id', '+15555555555', '+16666666666', Logger::CRITICAL, true, true, 'twilio.foo.bar', 'v2');
    }

    public function testWriteHeader()
    {
        $this->createHandler();
        $this->handler->handle($this->getRecord(Logger::CRITICAL, 'test1'));
        fseek($this->res, 0);
        $content = fread($this->res, 1024);

        $this->assertRegexp('/POST \/messages HTTP\/1.1\\r\\nHost: platform.clickatell.com\\r\\nAuthorization: token\\r\\nContent-Type: application\/json\\r\\nContent-Length: \d{2,4}\\r\\n\\r\\n/', $content);

        return $content;
    }

    public function testWriteCustomHostHeader()
    {
        $this->createHandler('token', '+15555555555', '+16666666666', Logger::CRITICAL, true, true, 'twilio.foo.bar');
        $this->handler->handle($this->getRecord(Logger::CRITICAL, 'test1'));
        fseek($this->res, 0);
        $content = fread($this->res, 1024);

        $this->assertRegexp('/POST \/messages HTTP\/1.1\\r\\nHost: twilio.foo.bar\\r\\nAuthorization: token\\r\\nContent-Type: application\/json\\r\\nContent-Length: \d{2,4}\\r\\n\\r\\n/', $content);

        return $content;
    }

    /**
     * @depends testWriteHeader
     */
    public function testWriteContent($content)
    {
        $this->assertRegexp('/{"content":"test1","to":\["\+16666666666"\],"from":"\+15555555555"}/', $content);
    }

    public function testWriteContentV1WithoutToAndFromNumbers()
    {
        $this->createHandler('token', false, null, Logger::CRITICAL, true, true, 'twilio.foo.bar');
        $this->handler->handle($this->getRecord(Logger::CRITICAL, 'test1'));
        fseek($this->res, 0);
        $content = fread($this->res, 1024);

        $this->assertRegexp('/{"content":"test1","to":\[null\]}/', $content);

        return $content;
    }

    /**
     * @depends testWriteCustomHostHeader
     */
    public function testWriteContentNotify($content)
    {
        $this->assertRegexp('/{"content":"test1","to":\["\+16666666666"\],"from":"\+15555555555"}/', $content);
    }

    public function testWriteWithComplexMessage()
    {
        $this->createHandler();
        $this->handler->handle($this->getRecord(Logger::CRITICAL, 'Backup of database example finished in 16 minutes.'));
        fseek($this->res, 0);
        $content = fread($this->res, 1024);

        $this->assertRegexp('/{"content":"Backup of database example finished in 16 minutes\.","to":\["\+16666666666"\],"from":"\+15555555555"}/', $content);
    }

    private function createHandler($authToken = 'token', $fromNumber = '+15555555555', $toNumber = '+16666666666', $level = Logger::CRITICAL, $bubble = true, $useSSL = true, $host = 'platform.clickatell.com', $version = ClickatellHandler::API_V1)
    {
        $constructorArgs = array($authToken, $fromNumber, $toNumber, Logger::DEBUG, true, true, $host, $version);
        $this->res = fopen('php://memory', 'a');
        $this->handler = $this->getMock(
            '\Tylercd100\Monolog\Handler\ClickatellHandler',
            array('fsockopen', 'streamSetTimeout', 'closeSocket'),
            $constructorArgs
        );

        $reflectionProperty = new \ReflectionProperty('\Monolog\Handler\SocketHandler', 'connectionString');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->handler, 'localhost:1234');

        $this->handler->expects($this->any())
            ->method('fsockopen')
            ->will($this->returnValue($this->res));
        $this->handler->expects($this->any())
            ->method('streamSetTimeout')
            ->will($this->returnValue(true));
        $this->handler->expects($this->any())
            ->method('closeSocket')
            ->will($this->returnValue(true));

        $this->handler->setFormatter($this->getIdentityFormatter());
    }
}
