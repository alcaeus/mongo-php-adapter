<?php

namespace Alcaeus\MongoDbAdapter\Tests;

use MongoDB\Driver\Exception;
use Alcaeus\MongoDbAdapter\ExceptionConverter;
use PHPUnit\Framework\TestCase;

class ExceptionConverterTest extends TestCase
{
    /**
     * @dataProvider exceptionProvider
     */
    public function testConvertException($e, $expectedClass)
    {
        $exception = ExceptionConverter::toLegacy($e);
        $this->assertInstanceOf($expectedClass, $exception);
        $this->assertSame($e->getMessage(), $exception->getMessage());
        $this->assertSame($e->getCode(), $exception->getCode());
        $this->assertSame($e, $exception->getPrevious());
    }

    public function exceptionProvider()
    {
        return [
            // Driver
            [
                new Exception\AuthenticationException('message', 1),
                'MongoConnectionException',
            ],
            [
                new Exception\BulkWriteException('message', 2),
                'MongoCursorException',
            ],
            [
                new Exception\ConnectionException('message', 2),
                'MongoConnectionException',
            ],
            [
                new Exception\ConnectionTimeoutException('message', 2),
                'MongoConnectionException',
            ],
            [
                new Exception\ExecutionTimeoutException('message', 2),
                'MongoExecutionTimeoutException',
            ],
            [
                new Exception\InvalidArgumentException('message', 2),
                'MongoException',
            ],
            [
                new Exception\LogicException('message', 2),
                'MongoException',
            ],
            [
                new Exception\RuntimeException('message', 2),
                'MongoException',
            ],
            [
                new Exception\SSLConnectionException('message', 2),
                'MongoConnectionException',
            ],
            [
                new Exception\UnexpectedValueException('message', 2),
                'MongoException',
            ],

            // Library
            [
                new \MongoDB\Exception\BadMethodCallException('message', 2),
                'MongoException',
            ],
            [
                new \MongoDB\Exception\InvalidArgumentException('message', 2),
                'MongoException',
            ],
            [
                new \MongoDB\Exception\UnexpectedValueException('message', 2),
                'MongoException',
            ],
        ];
    }
}
