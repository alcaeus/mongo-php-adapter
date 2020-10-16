<?php

namespace Alcaeus\MongoDbAdapter\Tests\Mongo;

use Alcaeus\MongoDbAdapter\Tests\TestCase;
use Alcaeus\MongoDbAdapter\TypeInterface;

/**
 * @author alcaeus <alcaeus@alcaeus.org>
 */
class MongoRegexTest extends TestCase
{
    public function testCreate()
    {
        $regex = new \MongoRegex('/abc/i');
        $this->assertSame('abc', $regex->regex);
        $this->assertSame('i', $regex->flags);

        $this->assertSame('/abc/i', (string) $regex);

        return $regex;
    }

    /**
     * @depends testCreate
     */
    public function testConvertToBson(\MongoRegex $regex)
    {
        $this->skipTestUnless($regex instanceof TypeInterface);

        $bsonRegex = $regex->toBSONType();
        $this->assertInstanceOf('MongoDB\BSON\Regex', $bsonRegex);
        $this->assertSame('abc', $bsonRegex->getPattern());
        $this->assertSame('i', $bsonRegex->getFlags());
    }

    public function testCreateWithBsonType()
    {
        $this->skipTestUnless(in_array(TypeInterface::class, class_implements('MongoRegex')));

        $bsonRegex = new \MongoDB\BSON\Regex('abc', 'i');
        $regex = new \MongoRegex($bsonRegex);

        $this->assertSame('abc', $regex->regex);
        $this->assertSame('i', $regex->flags);
    }
}
