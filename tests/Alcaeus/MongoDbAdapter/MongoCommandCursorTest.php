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
                'cursor' => new \stdClass()
            ],
            'fields' => null,
            'started_iterating' => false,
        ];
        $this->assertEquals($expected, $cursor->info());

        // Ensure cursor started iterating
        iterator_to_array($cursor);

        $expected['started_iterating'] = true;
        $expected += [
            'id' => '0',
            'at' => null,
            'numReturned' => null,
            'server' => null,
            'host' => 'localhost',
            'port' => 27017,
            'connection_type_desc' => 'STANDALONE'
        ];

        $this->assertEquals($expected, $cursor->info());
    }

    /**
     * @param string $name
     * @return \MongoCollection
     */
    protected function getCollection($name = 'test')
    {
        $client = new \MongoClient();

        return $client->selectCollection('mongo-php-adapter', $name);
    }

    /**
     * @return \MongoCollection
     */
    protected function prepareData()
    {
        $collection = $this->getCollection();

        $collection->insert(['foo' => 'bar']);
        $collection->insert(['foo' => 'bar']);
        $collection->insert(['foo' => 'foo']);
        return $collection;
    }
}
