<?php

namespace Alcaeus\MongoDbAdapter\Tests;

use MongoDB\BSON;
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
            'dateTime'            => [
                \DateTime::createFromFormat('Y-m-d\TH:i:sP', '2021-06-30T12:34:56-7'),
                new BSONDocument([
                    'date'          => '2021-06-30 12:34:56.000000',
                    'timezone_type' => 1,
                    'timezone'      => '-07:00',
                ]),
            ],
        ];
    }

    /**
     * @dataProvider dataIsNumericArray
     */
    public function testIsNumericArray($expected, $array)
    {
        $this->assertSame($expected, TypeConverter::isNumericArray($array));
    }

    public static function dataIsNumericArray()
    {
        return [
            'emptyArray' => [true, []],
            'arrayWithSequentialIndices' => [true, [1, 2, 3]],
            'arrayWithSequentialIndicesOneBased' => [false, [1 => 1, 2, 3]],
            'arrayWithStringKeys' => [false, ['foo' => 'bar']],
            'arrayWithRandomNumbers' => [false, [15 => 'foo', 20 => 'bar']],
        ];
    }
}
