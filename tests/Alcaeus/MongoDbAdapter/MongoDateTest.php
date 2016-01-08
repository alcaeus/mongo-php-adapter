<?php

namespace Alcaeus\MongoDbAdapter\Tests;
use Alcaeus\MongoDbAdapter\TypeInterface;

/**
 * @author alcaeus <alcaeus@alcaeus.org>
 */
class MongoDateTest extends TestCase
{
    public function testCreate()
    {
        $date = new \MongoDate(1234567890, 123456);
        $this->assertAttributeSame(1234567890, 'sec', $date);
        $this->assertAttributeSame(123000, 'usec', $date);

        $this->assertSame('0.12300000 1234567890', (string) $date);
        $dateTime = $date->toDateTime();

        $this->assertSame(1234567890, $dateTime->getTimestamp());
        $this->assertSame('123000', $dateTime->format('u'));

        return $date;
    }

    /**
     * @depends testCreate
     */
    public function testConvertToBson(\MongoDate $date)
    {
        $this->skipTestUnless($date instanceof TypeInterface);

        $dateTime = $date->toDateTime();

        $bsonDate = $date->toBSONType();
        $this->assertInstanceOf('MongoDB\BSON\UTCDateTime', $bsonDate);
        $this->assertSame('1234567890123', (string) $bsonDate);

        $this->assertEquals($dateTime, $bsonDate->toDateTime());
    }

    public function testCreateWithBsonDate()
    {
        $this->skipTestUnless(in_array(TypeInterface::class, class_implements('MongoDate')));

        $bsonDate = new \MongoDB\BSON\UTCDateTime(1234567890123);
        $date = new \MongoDate($bsonDate);

        $this->assertAttributeSame(1234567890, 'sec', $date);
        $this->assertAttributeSame(123000, 'usec', $date);
    }
}
