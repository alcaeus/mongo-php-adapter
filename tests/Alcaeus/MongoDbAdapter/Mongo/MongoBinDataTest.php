<?php

namespace Alcaeus\MongoDbAdapter\Tests\Mongo;

use Alcaeus\MongoDbAdapter\Tests\TestCase;
use Alcaeus\MongoDbAdapter\TypeInterface;

/**
 * @author alcaeus <alcaeus@alcaeus.org>
 */
class MongoBinDataTest extends TestCase
{
    const GUID = '0123456789abcdef';

    public function testCreate()
    {
        $bin = new \MongoBinData(self::GUID, \MongoBinData::FUNC);
        $this->assertSame(self::GUID, $bin->bin);
        $this->assertSame(\MongoBinData::FUNC, $bin->type);

        $this->assertSame('<Mongo Binary Data>', (string) $bin);

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

        $this->assertSame(self::GUID, $bsonBinary->getData());
        $this->assertSame(\MongoDB\BSON\Binary::TYPE_FUNCTION, $bsonBinary->getType());
    }

    public function testCreateWithBsonBinary()
    {
        $this->skipTestUnless(in_array(TypeInterface::class, class_implements('MongoBinData')));

        $bsonBinary = new \MongoDB\BSON\Binary(self::GUID, \MongoDB\BSON\Binary::TYPE_UUID);
        $bin = new \MongoBinData($bsonBinary);

        $this->assertSame(self::GUID, $bin->bin);
        $this->assertSame(\MongoBinData::UUID_RFC4122, $bin->type);
    }
}
