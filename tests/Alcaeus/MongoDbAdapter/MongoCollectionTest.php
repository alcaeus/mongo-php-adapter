<?php

namespace Alcaeus\MongoDbAdapter\Tests;

/**
 * @author alcaeus <alcaeus@alcaeus.org>
 * @covers MongoCollection
 */
class MongoCollectionTest extends TestCase
{
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

    /**
     * @return \MongoCollection
     */
    protected function getCollection($name = 'test')
    {
        $client = new \MongoClient();

        return $client->selectCollection('mongo-php-adapter', $name);
    }
}
