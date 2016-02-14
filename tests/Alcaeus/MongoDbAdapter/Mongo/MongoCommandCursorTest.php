<?php

namespace Alcaeus\MongoDbAdapter\Tests\Mongo;

use MongoDB\Driver\ReadPreference;
use Alcaeus\MongoDbAdapter\Tests\TestCase;

/**
 * @author alcaeus <alcaeus@alcaeus.org>
 */
class MongoCommandCursorTest extends TestCase
{
    public function testSerialize()
    {
        $this->prepareData();
        $cursor = $this->getCollection()->aggregateCursor([['$match' => ['foo' => 'bar']]]);
        $this->assertInternalType('string', serialize($cursor));
    }

    public function testInfo()
    {
        $this->prepareData();
        $cursor = $this->getCollection()->aggregateCursor([['$match' => ['foo' => 'bar']]]);

        $expected = [
            'ns' => 'mongo-php-adapter.test',
            'limit' => 0,
            'batchSize' => 0,
            'skip' => 0,
            'flags' => 0,
            'query' => [
                'aggregate' => 'test',
                'pipeline' => [
                    [
                        '$match' => ['foo' => 'bar']
                    ]
                ],
                'cursor' => new \stdClass(),
            ],
            'fields' => null,
            'started_iterating' => false,
        ];
        $info = $cursor->info();
        $this->assertEquals($expected, $info);

        // Ensure cursor started iterating
        $array = iterator_to_array($cursor);

        $expected['started_iterating'] = true;
        $expected += [
            'id' => 0,
            'at' => 0,
            'numReturned' => 0,
            'server' => 'localhost:27017;-;.;' . getmypid(),
            'host' => 'localhost',
            'port' => 27017,
            'connection_type_desc' => 'STANDALONE',
        ];

        $this->assertArraySubset($expected, $cursor->info());

        $i = 0;
        foreach ($array as $key => $value) {
            $this->assertEquals($i, $key);
            $i++;
        }
    }
}
