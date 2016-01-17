<?php

namespace Alcaeus\MongoDbAdapter\Tests;
use MongoDB\Driver\ReadPreference;
use MongoDB\Operation\Find;

/**
 * @author alcaeus <alcaeus@alcaeus.org>
 */
class MongoCommandCursorTest extends TestCase
{
    public function testInfo()
    {
        $this->prepareData();
        $cursor = $this->getCollection()->aggregateCursor([['$match' => ['foo' => 'bar']]]);

        $expected = [
            'ns' => 'mongo-php-adapter.test',
            'limit' => 0,
            'batchSize' => null,
            'skip' => 0,
            'flags' => 0,
            'query' => [
                'aggregate' => 'test',
                'pipeline' => [
                    [
                        '$match' => ['foo' => 'bar']
                    ]
                ],
                'cursor' => true,
            ],
            'fields' => null,
            'started_iterating' => false,
        ];
        $this->assertEquals($expected, $cursor->info());

        // Ensure cursor started iterating
        $array = iterator_to_array($cursor);

        $expected['started_iterating'] = true;
        $expected += [
            'id' => '0',
            'at' => null,
            'numReturned' => null,
            'server' => 'localhost:27017;-;.;' . getmypid(),
            'host' => 'localhost',
            'port' => 27017,
            'connection_type_desc' => 'STANDALONE'
        ];

        $this->assertEquals($expected, $cursor->info());

        $i = 0;
        foreach ($array as $key => $value) {
            $this->assertEquals($i, $key);
            $i++;
        }
    }
}
