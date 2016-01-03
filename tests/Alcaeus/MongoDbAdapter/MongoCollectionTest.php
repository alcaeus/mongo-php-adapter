<?php

namespace Alcaeus\MongoDbAdapter\Tests;
use MongoDB\Driver\ReadPreference;

/**
 * @author alcaeus <alcaeus@alcaeus.org>
 */
class MongoCollectionTest extends TestCase
{
    public function testGetNestedCollections()
    {
        $collection = $this->getCollection()->foo->bar;
        $this->assertSame('mongo-php-adapter.test.foo.bar', (string) $collection);
    }

    public function testCreateRecord()
    {
        $id = '54203e08d51d4a1f868b456e';
        $collection = $this->getCollection();

        $collection->insert(['_id' => new \MongoId($id), 'foo' => 'bar']);

        $newCollection = $this->getCheckDatabase()->selectCollection('test');
        $this->assertSame(1, $newCollection->count());
        $object = $newCollection->findOne();

        $this->assertNotNull($object);
        $this->assertAttributeInstanceOf('MongoDB\BSON\ObjectID', '_id', $object);
        $this->assertSame($id, (string) $object->_id);
        $this->assertObjectHasAttribute('foo', $object);
        $this->assertAttributeSame('bar', 'foo', $object);
    }

    public function testFindReturnsCursor()
    {
        $this->prepareData();
        $collection = $this->getCollection();

        $this->assertInstanceOf('MongoCursor', $collection->find());
    }

    public function testCount()
    {
        $this->prepareData();

        $collection = $this->getCollection();

        $this->assertSame(3, $collection->count());
        $this->assertSame(2, $collection->count(['foo' => 'bar']));
    }

    public function testFindOne()
    {
        $this->prepareData();

        $document = $this->getCollection()->findOne(['foo' => 'foo'], ['_id' => false]);
        $this->assertEquals(['foo' => 'foo'], $document);
    }

    public function testDistinct()
    {
        $this->prepareData();

        $values = $this->getCollection()->distinct('foo');
        $this->assertInternalType('array', $values);

        sort($values);
        $this->assertEquals(['bar', 'foo'], $values);
    }

    public function testDistinctWithQuery()
    {
        $this->prepareData();

        $values = $this->getCollection()->distinct('foo', ['foo' => 'bar']);
        $this->assertInternalType('array', $values);
        $this->assertEquals(['bar'], $values);
    }

    public function testAggregate()
    {
        $collection = $this->getCollection();

        $collection->insert(['foo' => 'bar']);
        $collection->insert(['foo' => 'bar']);
        $collection->insert(['foo' => 'foo']);

        $pipeline = [
            [
                '$group' => [
                    '_id' => '$foo',
                    'count' => [ '$sum' => 1 ],
                ],
            ],
            [
                '$sort' => ['_id' => 1]
            ]
        ];

        $result = $collection->aggregate($pipeline);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('result', $result);

        $this->assertEquals([
            ['_id' => 'bar', 'count' => 2],
            ['_id' => 'foo', 'count' => 1],
        ], $result['result']);
    }

    public function testAggregateCursor()
    {
        $collection = $this->getCollection();

        $collection->insert(['foo' => 'bar']);
        $collection->insert(['foo' => 'bar']);
        $collection->insert(['foo' => 'foo']);

        $pipeline = [
            [
                '$group' => [
                    '_id' => '$foo',
                    'count' => [ '$sum' => 1 ],
                ],
            ],
            [
                '$sort' => ['_id' => 1]
            ]
        ];

        $cursor = $collection->aggregateCursor($pipeline);
        $this->assertInstanceOf('MongoCommandCursor', $cursor);

        $this->assertEquals([
            ['_id' => 'bar', 'count' => 2],
            ['_id' => 'foo', 'count' => 1],
        ], iterator_to_array($cursor));
    }

    public function testReadPreference()
    {
        $collection = $this->getCollection();
        $this->assertSame(['type' => \MongoClient::RP_PRIMARY], $collection->getReadPreference());
        $this->assertFalse($collection->getSlaveOkay());

        $this->assertTrue($collection->setReadPreference(\MongoClient::RP_SECONDARY, ['a' => 'b']));
        $this->assertSame(['type' => \MongoClient::RP_SECONDARY, 'tagsets' => ['a' => 'b']], $collection->getReadPreference());
        $this->assertTrue($collection->getSlaveOkay());

        // Only way to check whether options are passed down is through debugInfo
        $writeConcern = $collection->getCollection()->__debugInfo()['readPreference'];

        $this->assertSame(ReadPreference::RP_SECONDARY, $writeConcern->getMode());
        $this->assertSame(['a' => 'b'], $writeConcern->getTagSets());

        $this->assertTrue($collection->setSlaveOkay(true));
        $this->assertSame(['type' => \MongoClient::RP_SECONDARY_PREFERRED, 'tagsets' => ['a' => 'b']], $collection->getReadPreference());

        $this->assertTrue($collection->setSlaveOkay(false));
        $this->assertSame(['type' => \MongoClient::RP_PRIMARY], $collection->getReadPreference());
    }

    public function testReadPreferenceIsInherited()
    {
        $database = $this->getDatabase();
        $database->setReadPreference(\MongoClient::RP_SECONDARY, ['a' => 'b']);

        $collection = $database->selectCollection('test');
        $this->assertSame(['type' => \MongoClient::RP_SECONDARY, 'tagsets' => ['a' => 'b']], $collection->getReadPreference());
    }

    public function testWriteConcern()
    {
        $collection = $this->getCollection();
        $this->assertSame(['w' => 1, 'wtimeout' => 0], $collection->getWriteConcern());
        $this->assertSame(1, $collection->w);
        $this->assertSame(0, $collection->wtimeout);

        $this->assertTrue($collection->setWriteConcern('majority', 100));
        $this->assertSame(['w' => 'majority', 'wtimeout' => 100], $collection->getWriteConcern());

        $collection->w = 2;
        $this->assertSame(['w' => 2, 'wtimeout' => 100], $collection->getWriteConcern());

        $collection->wtimeout = -1;
        $this->assertSame(['w' => 2, 'wtimeout' => 0], $collection->getWriteConcern());

        // Only way to check whether options are passed down is through debugInfo
        $writeConcern = $collection->getCollection()->__debugInfo()['writeConcern'];

        $this->assertSame(2, $writeConcern->getW());
        $this->assertSame(0, $writeConcern->getWtimeout());
    }

    public function testWriteConcernIsInherited()
    {
        $database = $this->getDatabase();
        $database->setWriteConcern('majority', 100);

        $collection = $database->selectCollection('test');
        $this->assertSame(['w' => 'majority', 'wtimeout' => 100], $collection->getWriteConcern());
    }

    /**
     * @return \MongoCollection
     */
    protected function getCollection($name = 'test')
    {
        return $this->getDatabase()->selectCollection($name);
    }

    /**
     * @return \MongoDB
     */
    protected function getDatabase()
    {
        $client = new \MongoClient();

        return $client->selectDB('mongo-php-adapter');
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
