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
        $collection = $this->getCollection();

        $collection->insert(['sorter' => 1]);

        $this->assertInstanceOf('MongoCursor', $collection->find());
    }

    public function testCount()
    {
        $collection = $this->getCollection();

        $collection->insert(['foo' => 'bar']);
        $collection->insert(['foo' => 'foo']);

        $this->assertSame(2, $collection->count());
        $this->assertSame(1, $collection->count(['foo' => 'bar']));
    }

    /**
     * @return \MongoCollection
     */
    protected function getCollection($name = 'test')
    {
        $client = new \MongoClient();

        return $client->selectCollection('mongo-php-adapter', $name);
    }
}
