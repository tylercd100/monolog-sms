<?php

namespace Tylercd100\Monolog\Tests;

use PHPUnit_Framework_TestCase;
use Tylercd100\Monolog\Handler\PlivoHandler;

class PlivoHandlerTest extends \PHPUnit_Framework_TestCase
{
    public function testCanBeInstantiatedAndProvidesDefaultFormatter()
    {
        $handler = new PlivoHandler('token', 'auth_id', '+15555555555', '+16666666666');

        $this->assertInstanceOf('Tylercd100\\Monolog\\Formatter\\PlivoFormatter', $handler->getFormatter());
        $this->assertAttributeEquals('token',        'authToken',  $handler);
        $this->assertAttributeEquals('auth_id',      'authId',     $handler);
        $this->assertAttributeEquals('+15555555555', 'fromNumber', $handler);
        $this->assertAttributeEquals('+16666666666', 'toNumber',   $handler);
    }
}