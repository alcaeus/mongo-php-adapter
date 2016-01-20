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
            'nMatched' => 1,
            'nModified' => 1,
            'nUpserted' => 0,
            'ok' => true,
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
            'nMatched' => 2,
            'nModified' => 2,
            'nUpserted' => 0,
            'ok' => true,
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
            'upserted' => [
                [
                    'index' => 0,
                ]
            ],
            'nMatched' => 0,
            'nModified' => 0,
            'nUpserted' => 1,
            'ok' => true,
        ];

        $result = $batch->execute();
        $this->assertArraySubset($expected, $result);

        $this->assertInstanceOf('MongoId', $result['upserted'][0]['_id']);

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

        $this->setExpectedException('Exception', "Expected \$item to contain 'q' key");

        $batch->add([]);
    }
}
