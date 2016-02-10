<?php

namespace Alcaeus\MongoDbAdapter\Tests;

use MongoDB\BSON;
use MongoDB\Driver\Exception;
use Alcaeus\MongoDbAdapter\TypeConverter;
use MongoDB\Model\BSONDocument;

class TypeConverterTest extends TestCase
{
    /**
     * @dataProvider converterData
     */
    public function testFromLegacy($legacyValue, $modernValue)
    {
        $this->skipTestIf(extension_loaded('mongo'));
        $this->assertEquals($modernValue, TypeConverter::fromLegacy($legacyValue));
    }

    public static function converterData()
    {
        $id = str_repeat('0123', 6);

        return [
            'objectId' => [
                new \MongoId($id), new BSON\ObjectID($id)
            ],
            'numericArray' => [
                ['foo', 'bar'], ['foo', 'bar']
            ],
            'hashWithNumericKeys' => [
                (object) ['foo', 'bar'], new BSONDocument(['foo', 'bar'])
            ],
            'hash' => [
                ['foo' => 'bar'], new BSONDocument(['foo' => 'bar'])
            ],
            'nestedArrays' => [
                [['foo' => 'bar']], [new BSONDocument(['foo' => 'bar'])]
            ],
        ];
    }
}
