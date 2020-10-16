<?php

namespace Alcaeus\MongoDbAdapter\Tests\Mongo;

use Alcaeus\MongoDbAdapter\Tests\TestCase;

class MongoDeleteBatchTest extends TestCase
{
    public function testSerialize()
    {
        $batch = new \MongoDeleteBatch($this->getCollection());
        $this->assertIsString(serialize($batch));
    }

    public function testDeleteOne()
    {
        $collection = $this->getCollection();
        $batch = new \MongoDeleteBatch($collection);

        $document = ['foo' => 'bar'];
        $collection->insert($document);
        unset($document['_id']);
        $collection->insert($document);

        $this->assertTrue($batch->add(['q' => ['foo' => 'bar'], 'limit' => 1]));

        $expected = [
            'nRemoved' => 1,
            'ok' => true,
        ];

        $this->assertSame($expected, $batch->execute());

        $newCollection = $this->getCheckDatabase()->selectCollection('test');
        $this->assertSame(1, $newCollection->count());
    }

    public function testDeleteMany()
    {
        $collection = $this->getCollection();
        $batch = new \MongoDeleteBatch($collection);

        $document = ['foo' => 'bar'];
        $collection->insert($document);
        unset($document['_id']);
        $collection->insert($document);

        $this->assertTrue($batch->add(['q' => ['foo' => 'bar'], 'limit' => 0]));

        $expected = [
            'nRemoved' => 2,
            'ok' => true,
        ];

        $this->assertSame($expected, $batch->execute());

        $newCollection = $this->getCheckDatabase()->selectCollection('test');
        $this->assertSame(0, $newCollection->count());
    }

    public function testDeleteManyWithoutAck()
    {
        $collection = $this->getCollection();
        $batch = new \MongoDeleteBatch($collection);

        $document = ['foo' => 'bar'];
        $collection->insert($document);
        unset($document['_id']);
        $collection->insert($document);

        $this->assertTrue($batch->add(['q' => ['foo' => 'bar'], 'limit' => 0]));

        $expected = [
            'nRemoved' => 0,
            'ok' => true,
        ];

        $this->assertSame($expected, $batch->execute(['w' => 0]));

        $newCollection = $this->getCheckDatabase()->selectCollection('test');
        $this->assertSame(0, $newCollection->count());
    }

    public function testValidateItem()
    {
        $collection = $this->getCollection();
        $batch = new \MongoDeleteBatch($collection);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Expected \$item to contain 'q' key");

        $batch->add([]);
    }
}
