<?php

namespace Tylercd100\Monolog\Tests;

use Exception;
use PHPUnit_Framework_TestCase;
use Tylercd100\Monolog\Handler\PlivoHandler;
use Monolog\Logger;
use Tylercd100\Monolog\Tests\TestCase;

class PlivoHandlerTest extends TestCase
{
    private $res;
    /** @var  PlivoHandler */
    private $handler;

    public function testCanBeInstantiatedAndProvidesDefaultFormatter()
    {
        $handler = new PlivoHandler('token', 'auth_id', '+15555555555', '+16666666666');

        $this->assertInstanceOf('Tylercd100\\Monolog\\Formatter\\SMSFormatter', $handler->getFormatter());
        $this->assertAttributeEquals('token',        'authToken',  $handler);
        $this->assertAttributeEquals('auth_id',      'authId',     $handler);
        $this->assertAttributeEquals('+15555555555', 'fromNumber', $handler);
        $this->assertAttributeEquals('+16666666666', 'toNumber',   $handler);
    }

    public function testItThrowsExceptionWhenUsingDifferentVersionOtherThanV1()
    {
        $this->setExpectedException(Exception::class);
        $handler = new PlivoHandler('token', 'auth_id', '+15555555555', '+16666666666', Logger::CRITICAL, true, true, 'plivo.foo.bar', 'v2');
    }

    public function testWriteHeader()
    {
        $this->createHandler();
        $this->handler->handle($this->getRecord(Logger::CRITICAL, 'test1'));
        fseek($this->res, 0);
        $content = fread($this->res, 1024);

        $this->assertRegexp('/POST \/v1\/Account\/auth_id\/Message\/ HTTP\/1.1\\r\\nHost: api.plivo.com\\r\\nAuthorization: Basic YXV0aF9pZDp0b2tlbg==\\r\\nContent-Type: application\/json\\r\\nContent-Length: \d{2,4}\\r\\n\\r\\n/', $content);

        return $content;
    }

    public function testWriteCustomHostHeader()
    {
        $this->createHandler('token', 'auth_id', '+15555555555', '+16666666666', Logger::CRITICAL, true, true, 'plivo.foo.bar');
        $this->handler->handle($this->getRecord(Logger::CRITICAL, 'test1'));
        fseek($this->res, 0);
        $content = fread($this->res, 1024);

        $this->assertRegexp('/POST \/v1\/Account\/auth_id\/Message\/ HTTP\/1.1\\r\\nHost: plivo.foo.bar\\r\\nAuthorization: Basic YXV0aF9pZDp0b2tlbg==\\r\\nContent-Type: application\/json\\r\\nContent-Length: \d{2,4}\\r\\n\\r\\n/', $content);

        return $content;
    }

    /**
     * @depends testWriteHeader
     */
    public function testWriteContent($content)
    {
        $this->assertRegexp('/{"src":"\+15555555555","dst":"\+16666666666","text":"test1"}/', $content);
    }

    public function testWriteContentV1WithoutToAndFromNumbers()
    {
        $this->createHandler('token', 'auth_id', false, null, Logger::CRITICAL, true, true, 'plivo.foo.bar');
        $this->handler->handle($this->getRecord(Logger::CRITICAL, 'test1'));
        fseek($this->res, 0);
        $content = fread($this->res, 1024);

        $this->assertRegexp('/{"src":false,"dst":null,"text":"test1"}/', $content);

        return $content;
    }

    /**
     * @depends testWriteCustomHostHeader
     */
    public function testWriteContentNotify($content)
    {
        $this->assertRegexp('/{"src":"\+15555555555","dst":"\+16666666666","text":"test1"}/', $content);
    }

    public function testWriteWithComplexMessage()
    {
        $this->createHandler();
        $this->handler->handle($this->getRecord(Logger::CRITICAL, 'Backup of database example finished in 16 minutes.'));
        fseek($this->res, 0);
        $content = fread($this->res, 1024);

        $this->assertRegexp('/{"src":"\+15555555555","dst":"\+16666666666","text":"Backup of database example finished in 16 minutes\."}/', $content);
    }

    private function createHandler($authToken = 'token', $authId = 'auth_id', $fromNumber = '+15555555555', $toNumber = '+16666666666', $level = Logger::CRITICAL, $bubble = true, $useSSL = true, $host = 'api.plivo.com', $version = PlivoHandler::API_V1)
    {
        $constructorArgs = array($authToken, $authId, $fromNumber, $toNumber, Logger::DEBUG, true, true, $host, $version);
        $this->res = fopen('php://memory', 'a');
        $this->handler = $this->getMockBuilder(PlivoHandler::class)
            ->setMethods(['fsockopen', 'streamSetTimeout', 'closeSocket'])
            ->setConstructorArgs($constructorArgs)
            ->getMock();

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
