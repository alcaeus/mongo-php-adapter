<?php

namespace Alcaeus\MongoDbAdapter\Tests\Mongo;

use Alcaeus\MongoDbAdapter\Tests\TestCase;
use Alcaeus\MongoDbAdapter\TypeInterface;

/**
 * @author alcaeus <alcaeus@alcaeus.org>
 */
class MongoTimestampTest extends TestCase
{
    public function testCreate()
    {
        $timestamp = new \MongoTimestamp(1234567890, 987654321);
        $this->assertSame(1234567890, $timestamp->sec);
        $this->assertSame(987654321, $timestamp->inc);

        $this->assertSame('1234567890', (string) $timestamp);

        return $timestamp;
    }

    /**
     * @depends testCreate
     */
    public function testConvertToBson(\MongoTimestamp $timestamp)
    {
        $this->skipTestUnless($timestamp instanceof TypeInterface);

        $bsonTimestamp = $timestamp->toBSONType();
        $this->assertInstanceOf('MongoDB\BSON\Timestamp', $bsonTimestamp);
        $this->assertSame('[987654321:1234567890]', (string) $bsonTimestamp);
    }

    public function testCreateWithGlobalInc()
    {
        $timestamp1 = new \MongoTimestamp(1234567890);
        $timestamp2 = new \MongoTimestamp(1234567890);

        $this->assertSame(0, $timestamp1->inc);
        $this->assertSame(1, $timestamp2->inc);
    }

    public function testCreateWithBsonTimestamp()
    {
        $this->skipTestUnless(in_array(TypeInterface::class, class_implements('MongoTimestamp')));

        $bsonTimestamp = new \MongoDB\BSON\Timestamp(987654321, 1234567890);
        $timestamp = new \MongoTimestamp($bsonTimestamp);

        $this->assertSame(1234567890, $timestamp->sec);
        $this->assertSame(987654321, $timestamp->inc);
    }

    public function testContructorArgumentOrderDiffers()
    {
        $this->skipTestUnless(in_array(TypeInterface::class, class_implements('MongoTimestamp')));

        /* The legacy MongoTimestamp's constructor takes seconds before the
         * increment, while MongoDB\BSON\Timestamp takes the increment first.
         */
        $bsonTimestamp = new \MongoDB\BSON\Timestamp(12345, 67890);
        $timestamp = new \MongoTimestamp(67890, 12345);

        $this->assertSame((string) $bsonTimestamp, (string) $timestamp->toBSONType());
    }
}
