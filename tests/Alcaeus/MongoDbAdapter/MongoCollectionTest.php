<?php

namespace Alcaeus\MongoDbAdapter\Tests;

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

    /**
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
