<?php

namespace Alcaeus\MongoDbAdapter\Tests\Mongo;

use Alcaeus\MongoDbAdapter\Tests\TestCase;
use Alcaeus\MongoDbAdapter\TypeInterface;

/**
 * @author alcaeus <alcaeus@alcaeus.org>
 */
class MongoBinDataTest extends TestCase
{
    public function testCreate()
    {
        $bin = new \MongoBinData('foo', \MongoBinData::FUNC);
        $this->assertAttributeSame('foo', 'bin', $bin);
        $this->assertAttributeSame(\MongoBinData::FUNC, 'type', $bin);

        $this->assertSame('<Mongo Binary Data>', (string)$bin);

        return $bin;
    }

    /**
     * @depends testCreate
     */
    public function testConvertToBson(\MongoBinData $bin)
    {
        $this->skipTestUnless($bin instanceof TypeInterface);

        $bsonBinary = $bin->toBSONType();
        $this->assertInstanceOf('MongoDB\BSON\Binary', $bsonBinary);

        $this->assertSame('foo', $bsonBinary->getData());
        $this->assertSame(\MongoDB\BSON\Binary::TYPE_FUNCTION, $bsonBinary->getType());
    }

    public function testCreateWithBsonBinary()
    {
        $this->skipTestUnless(in_array(TypeInterface::class, class_implements('MongoBinData')));

        $bsonBinary = new \MongoDB\BSON\Binary('foo', \MongoDB\BSON\Binary::TYPE_UUID);
        $bin = new \MongoBinData($bsonBinary);

        $this->assertAttributeSame('foo', 'bin', $bin);
        $this->assertAttributeSame(\MongoBinData::UUID_RFC4122, 'type', $bin);
    }
}
