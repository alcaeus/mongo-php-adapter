<?php

namespace Alcaeus\MongoDbAdapter\Tests;

class MongoDeleteBatchTest extends TestCase
{
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
            'ok' => 1.0,
            'nInserted' => 0,
            'nMatched' => 0,
            'nModified' => 0,
            'nUpserted' => 0,
            'nRemoved' => 1,
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
            'ok' => 1.0,
            'nInserted' => 0,
            'nMatched' => 0,
            'nModified' => 0,
            'nUpserted' => 0,
            'nRemoved' => 2,
        ];

        $this->assertSame($expected, $batch->execute());

        $newCollection = $this->getCheckDatabase()->selectCollection('test');
        $this->assertSame(0, $newCollection->count());
    }


    public function testValidateItem()
    {
        $collection = $this->getCollection();
        $batch = new \MongoDeleteBatch($collection);

        $this->setExpectedException('Exception', 'invalid item');

        $batch->add([]);
    }
}
