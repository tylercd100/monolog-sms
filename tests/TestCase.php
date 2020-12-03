<?php

/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tylercd100\Monolog\Tests;

use ReflectionClass;
use Monolog\Logger;
use Monolog\Formatter\FormatterInterface;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

class TestCase extends PHPUnitTestCase
{
    /**
     * @return array Record
     */
    protected function getRecord($level = Logger::WARNING, $message = 'test', $context = array())
    {
        return array(
            'message' => $message,
            'context' => $context,
            'level' => $level,
            'level_name' => Logger::getLevelName($level),
            'channel' => 'test',
            'datetime' => \DateTime::createFromFormat('U.u', sprintf('%.6F', microtime(true))),
            'extra' => array(),
        );
    }

    /**
     * @return array
     */
    protected function getMultipleRecords()
    {
        return array(
            $this->getRecord(Logger::DEBUG, 'debug message 1'),
            $this->getRecord(Logger::DEBUG, 'debug message 2'),
            $this->getRecord(Logger::INFO, 'information'),
            $this->getRecord(Logger::WARNING, 'warning'),
            $this->getRecord(Logger::ERROR, 'error'),
        );
    }

    /**
     * @return Monolog\Formatter\FormatterInterface
     */
    protected function getIdentityFormatter()
    {
        $formatter = $this->getMockBuilder(FormatterInterface::class)->getMock();
        $formatter->expects($this->any())
            ->method('format')
            ->will(
                $this->returnCallback(function ($record) {
                    return $record['message'];
                })
            );

        return $formatter;
    }

    /**
     * Helper to return protected or private property value of an object
     *
     * @param  mixed  $object
     * @param  string $property
     * @return mixed
     */
    protected function accessProtected($object, $property)
    {
        $reflection = new ReflectionClass($object);
        $propertyReflection = $reflection->getProperty($property);
        $propertyReflection->setAccessible(true);

        return $propertyReflection->getValue($object);
    }

    /**
     * {@inheritdoc}
     */
    public static function assertMatchesRegularExpression(string $pattern, string $string, string $message = ''): void
    {
        if (method_exists(PHPUnitTestCase::class, 'assertMatchesRegularExpression')) {
            // For PHPUnit >= 9
            parent::assertMatchesRegularExpression($pattern, $string, $message);
        } else {
            // For PHPUnit < 9
            static::assertRegExp($pattern, $string, $message);
        }
    }
}
