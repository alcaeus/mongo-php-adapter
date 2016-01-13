<?php

namespace Alcaeus\MongoDbAdapter\Tests;

class MongoUpdateBatchTest extends TestCase
{


    public function testUpdateOne()
    {
        $collection = $this->getCollection();
        $batch = new \MongoUpdateBatch($collection);

        $document = ['foo' => 'bar'];
        $collection->insert($document);

        $this->assertTrue($batch->add(['q' => ['foo' => 'bar'], 'u' => ['$set' => ['foo' => 'foo']]]));

        $expected = [
            'ok' => 1.0,
            'nInserted' => 0,
            'nMatched' => 1,
            'nModified' => 1,
            'nUpserted' => 0,
            'nRemoved' => 0,
        ];

        $this->assertSame($expected, $batch->execute());

        $newCollection = $this->getCheckDatabase()->selectCollection('test');
        $this->assertSame(1, $newCollection->count());
        $record = $newCollection->findOne();
        $this->assertNotNull($record);
        $this->assertObjectHasAttribute('foo', $record);
        $this->assertAttributeSame('foo', 'foo', $record);
    }

    public function testUpdateMany()
    {
        $collection = $this->getCollection();
        $batch = new \MongoUpdateBatch($collection);

        $document = ['foo' => 'bar'];
        $collection->insert($document);
        unset($document['_id']);
        $collection->insert($document);


        $this->assertTrue($batch->add(['q' => ['foo' => 'bar'], 'u' => ['$set' => ['foo' => 'foo']], 'multi' => true]));

        $expected = [
            'ok' => 1.0,
            'nInserted' => 0,
            'nMatched' => 2,
            'nModified' => 2,
            'nUpserted' => 0,
            'nRemoved' => 0,
        ];

        $this->assertSame($expected, $batch->execute());

        $newCollection = $this->getCheckDatabase()->selectCollection('test');
        $this->assertSame(2, $newCollection->count());
        $record = $newCollection->findOne();
        $this->assertNotNull($record);
        $this->assertObjectHasAttribute('foo', $record);
        $this->assertAttributeSame('foo', 'foo', $record);
    }

    public function testUpsert()
    {
        $batch = new \MongoUpdateBatch($this->getCollection());

        $this->assertTrue($batch->add(['q' => [], 'u' => ['$set' => ['foo' => 'bar']], 'upsert' => true]));

        $expected = [
            'ok' => 1.0,
            'nInserted' => 0,
            'nMatched' => 0,
            'nModified' => 0,
            'nUpserted' => 1,
            'nRemoved' => 0,
        ];

        $this->assertSame($expected, $batch->execute());

        $newCollection = $this->getCheckDatabase()->selectCollection('test');
        $this->assertSame(1, $newCollection->count());
        $record = $newCollection->findOne();
        $this->assertNotNull($record);
        $this->assertObjectHasAttribute('foo', $record);
        $this->assertAttributeSame('bar', 'foo', $record);
    }

    public function testValidateItem()
    {
        $collection = $this->getCollection();
        $batch = new \MongoUpdateBatch($collection);

        $this->setExpectedException('Exception', 'invalid item');

        $batch->add([]);
    }
}
