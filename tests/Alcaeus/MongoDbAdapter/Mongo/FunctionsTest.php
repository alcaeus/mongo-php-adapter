<?php

namespace Alcaeus\MongoDbAdapter\Tests\Mongo;

use Alcaeus\MongoDbAdapter\Tests\TestCase;

/**
 * @author alcaeus <alcaeus@alcaeus.org>
 */
class FunctionsTest extends TestCase
{
    /**
     * @return array Returns tupels: [$encoded, $decoded]
     */
    public static function data()
    {
        // The encoded values were retrieved by encoding data with the legacy driver and encoding them base64
        $simpleArray = ['foo' => 'bar'];
        $simpleArrayEncoded = "EgAAAAJmb28ABAAAAGJhcgAA";

        $arrayWithObjectId = ['_id' => new \MongoId('1234567890abcdef12345678')];
        $arrayWithObjectIdEncoded = "FgAAAAdfaWQAEjRWeJCrze8SNFZ4AA==";

        return [
            'simpleArray' => [base64_decode($simpleArrayEncoded), $simpleArray],
            'arrayWithObjectId' => [base64_decode($arrayWithObjectIdEncoded), $arrayWithObjectId],
        ];
    }

    /**
     * @dataProvider data
     */
    public function testEncode($encoded, $decoded)
    {
        $this->assertEquals($encoded, bson_encode($decoded));
    }

    /**
     * @dataProvider data
     */
    public function testDecode($encoded, $decoded)
    {
        $this->assertEquals($decoded, bson_decode($encoded));
    }
}
