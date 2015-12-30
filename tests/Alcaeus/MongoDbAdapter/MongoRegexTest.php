<?php

namespace Alcaeus\MongoDbAdapter\Tests;

/**
 * @author alcaeus <alcaeus@alcaeus.org>
 */
class MongoRegexTest extends TestCase
{
    public function testCreate()
    {
        $regex = new \MongoRegex('/abc/i');
        $this->assertAttributeSame('abc', 'regex', $regex);
        $this->assertAttributeSame('i', 'flags', $regex);

        $this->assertSame('/abc/i', (string) $regex);

        $bsonRegex = $regex->toBSONType();
        $this->assertInstanceOf('MongoDB\BSON\Regex', $bsonRegex);
        $this->assertSame('abc', $bsonRegex->getPattern());
        $this->assertSame('i', $bsonRegex->getFlags());
    }

    public function testCreateWithBsonType()
    {
        $bsonRegex = new \MongoDB\BSON\Regex('abc', 'i');
        $regex = new \MongoRegex($bsonRegex);

        $this->assertAttributeSame('abc', 'regex', $regex);
        $this->assertAttributeSame('i', 'flags', $regex);
    }
}
