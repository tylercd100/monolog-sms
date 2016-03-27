<?php

namespace Tylercd100\Monolog\Tests;

use Exception;
use PHPUnit_Framework_TestCase;
use Tylercd100\Monolog\Handler\TwilioHandler;
use Monolog\Logger;
use Tylercd100\Monolog\Tests\TestCase;

class TwilioHandlerTest extends TestCase
{
    private $res;
    /** @var  TwilioHandler */
    private $handler;
    
    public function testCanBeInstantiatedAndProvidesDefaultFormatter()
    {
        $handler = new TwilioHandler('token', 'auth_id', '+15555555555', '+16666666666');

        $this->assertInstanceOf('Tylercd100\\Monolog\\Formatter\\SMSFormatter', $handler->getFormatter());
        $this->assertAttributeEquals('token',        'authToken',  $handler);
        $this->assertAttributeEquals('auth_id',      'authId',     $handler);
        $this->assertAttributeEquals('+15555555555', 'fromNumber', $handler);
        $this->assertAttributeEquals('+16666666666', 'toNumber',   $handler);
    }

    public function testItThrowsExceptionWhenUsingDifferentVersionOtherThanV1()
    {
        $this->setExpectedException(Exception::class);
        $handler = new TwilioHandler('token', 'auth_id', '+15555555555', '+16666666666', Logger::CRITICAL, true, true, 'twilio.foo.bar', 'v2');
    }

    public function testWriteHeader()
    {
        $this->createHandler();
        $this->handler->handle($this->getRecord(Logger::CRITICAL, 'test1'));
        fseek($this->res, 0);
        $content = fread($this->res, 1024);

        $this->assertRegexp('/POST \/2010-04-01\/Accounts\/auth_id\/Messages\.json HTTP\/1.1\\r\\nHost: api.twilio.com\\r\\nAuthorization: Basic YXV0aF9pZDp0b2tlbg==\\r\\nContent-Type: application\/json\\r\\nContent-Length: \d{2,4}\\r\\n\\r\\n/', $content);

        return $content;
    }

    public function testWriteCustomHostHeader()
    {
        $this->createHandler('token', 'auth_id', '+15555555555', '+16666666666', Logger::CRITICAL, true, true, 'twilio.foo.bar');
        $this->handler->handle($this->getRecord(Logger::CRITICAL, 'test1'));
        fseek($this->res, 0);
        $content = fread($this->res, 1024);

        $this->assertRegexp('/POST \/2010-04-01\/Accounts\/auth_id\/Messages.json HTTP\/1.1\\r\\nHost: twilio.foo.bar\\r\\nAuthorization: Basic YXV0aF9pZDp0b2tlbg==\\r\\nContent-Type: application\/json\\r\\nContent-Length: \d{2,4}\\r\\n\\r\\n/', $content);

        return $content;
    }

    /**
     * @depends testWriteHeader
     */
    public function testWriteContent($content)
    {
        $this->assertRegexp('/{"From":"\+15555555555","To":"\+16666666666","Body":"test1"}/', $content);
    }

    public function testWriteContentV1WithoutToAndFromNumbers()
    {
        $this->createHandler('token', 'auth_id', false, null, Logger::CRITICAL, true, true, 'twilio.foo.bar');
        $this->handler->handle($this->getRecord(Logger::CRITICAL, 'test1'));
        fseek($this->res, 0);
        $content = fread($this->res, 1024);

        $this->assertRegexp('/{"From":false,"To":null,"Body":"test1"}/', $content);

        return $content;
    }

    /**
     * @depends testWriteCustomHostHeader
     */
    public function testWriteContentNotify($content)
    {
        $this->assertRegexp('/{"From":"\+15555555555","To":"\+16666666666","Body":"test1"}/', $content);
    }

    public function testWriteWithComplexMessage()
    {
        $this->createHandler();
        $this->handler->handle($this->getRecord(Logger::CRITICAL, 'Backup of database example finished in 16 minutes.'));
        fseek($this->res, 0);
        $content = fread($this->res, 1024);

        $this->assertRegexp('/{"From":"\+15555555555","To":"\+16666666666","Body":"Backup of database example finished in 16 minutes\."}/', $content);
    }

    private function createHandler($authToken = 'token', $authId = 'auth_id', $fromNumber = '+15555555555', $toNumber = '+16666666666', $level = Logger::CRITICAL, $bubble = true, $useSSL = true, $host = 'api.twilio.com', $version = TwilioHandler::API_V1)
    {
        $constructorArgs = array($authToken, $authId, $fromNumber, $toNumber, Logger::DEBUG, true, true, $host, $version);
        $this->res = fopen('php://memory', 'a');
        $this->handler = $this->getMock(
            '\Tylercd100\Monolog\Handler\TwilioHandler',
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
