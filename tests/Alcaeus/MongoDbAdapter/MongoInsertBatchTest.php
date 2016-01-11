<?php

namespace Alcaeus\MongoDbAdapter\Tests;

class MongoInsertBatchTest extends TestCase
{
    public function testInsertBatch()
    {
        $batch = new \MongoInsertBatch($this->getCollection());

        $this->assertTrue($batch->add(['foo' => 'bar']));
        $this->assertTrue($batch->add(['bar' => 'foo']));

        $expected = [
            'ok' => 1.0,
            'nInserted' => 2,
            'nMatched' => 0,
            'nModified' => 0,
            'nUpserted' => 0,
            'nRemoved' => 0,
        ];

        $this->assertSame($expected, $batch->execute());

        $newCollection = $this->getCheckDatabase()->selectCollection('test');
        $this->assertSame(2, $newCollection->count());
        $record = $newCollection->findOne();
        $this->assertNotNull($record);
        $this->assertObjectHasAttribute('foo', $record);
        $this->assertAttributeSame('bar', 'foo', $record);
    }

    public function testInsertBatchError()
    {
        $collection = $this->getCollection();
        $batch = new \MongoInsertBatch($collection);
        $collection->createIndex(['foo' => 1], ['unique' => true]);

        $this->assertTrue($batch->add(['foo' => 'bar']));
        $this->assertTrue($batch->add(['foo' => 'bar']));

        $expected = [
            'ok' => 0.0,
            'nInserted' => 1,
            'nMatched' => 0,
            'nModified' => 0,
            'nUpserted' => 0,
            'nRemoved' => 0,
        ];

        $this->assertSame($expected, $batch->execute());
    }
}
