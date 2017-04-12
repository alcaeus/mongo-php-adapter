<?php

namespace Alcaeus\MongoDbAdapter\Tests\Mongo;

use Alcaeus\MongoDbAdapter\Tests\TestCase;

class MongoUpdateBatchTest extends TestCase
{
    public function testSerialize()
    {
        $batch = new \MongoUpdateBatch($this->getCollection());
        $this->assertInternalType('string', serialize($batch));
    }

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

    public function testUpdateOneException()
    {
        $collection = $this->getCollection();
        $batch = new \MongoUpdateBatch($collection);

        $document = ['foo' => 'bar'];
        $collection->insert($document);
        $document = ['foo' => 'foo'];
        $collection->insert($document);
        $collection->createIndex(['foo' => 1], ['unique' => true]);

        $this->assertTrue($batch->add(['q' => ['foo' => 'bar'], 'u' => ['$set' => ['foo' => 'foo']]]));

        $expected = [
            'writeErrors' => [
                [
                    'index' => 0,
                    'code' => 11000,
                ]
            ],
            'nMatched' => 0,
            'nModified' => 0,
            'nUpserted' => 0,
            'ok' => true,
        ];

        try {
            $batch->execute();
            $this->fail('Expected MongoWriteConcernException');
        } catch (\MongoWriteConcernException $e) {
            $this->assertSame('Failed write', $e->getMessage());
            $this->assertSame(911, $e->getCode());
            $this->assertArraySubset($expected, $e->getDocument());
        }
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

    public function testUpdateManyException()
    {
        $collection = $this->getCollection();
        $batch = new \MongoUpdateBatch($collection);

        $document = ['foo' => 'bar', 'bar' => 'bar'];
        $collection->insert($document);
        $document = ['foo' => 'foobar', 'bar' => 'bar'];
        $collection->insert($document);
        $collection->createIndex(['foo' => 1], ['unique' => true]);

        $batch->add(['q' => ['bar' => 'bar'], 'u' => ['$set' => ['foo' => 'foo']], 'multi' => true]);

        $expected = [
            'writeErrors' => [
                [
                    'index' => 0,
                    'code' => 11000,
                ]
            ],
            'nMatched' => 0,
            'nModified' => 0,
            'nUpserted' => 0,
            'ok' => true,
        ];

        try {
            $batch->execute();
            $this->fail('Expected MongoWriteConcernException');
        } catch (\MongoWriteConcernException $e) {
            $this->assertSame('Failed write', $e->getMessage());
            $this->assertSame(911, $e->getCode());
            $this->assertArraySubset($expected, $e->getDocument());
        }
    }

    public function testUpsert()
    {
        $document = ['foo' => 'foo'];
        $this->getCollection()->insert($document);
        $batch = new \MongoUpdateBatch($this->getCollection());

        $this->assertTrue($batch->add(['q' => ['foo' => 'foo'], 'u' => ['$set' => ['foo' => 'bar']], 'upsert' => true]));
        $this->assertTrue($batch->add(['q' => ['bar' => 'foo'], 'u' => ['$set' => ['foo' => 'bar']], 'upsert' => true]));

        $expected = [
            'upserted' => [
                [
                    'index' => 1,
                ]
            ],
            'nMatched' => 1,
            'nModified' => 1,
            'nUpserted' => 1,
            'ok' => true,
        ];

        $result = $batch->execute();
        $this->assertArraySubset($expected, $result);

        $this->assertInstanceOf('MongoId', $result['upserted'][0]['_id']);

        $newCollection = $this->getCheckDatabase()->selectCollection('test');
        $this->assertSame(0, $newCollection->count(['foo' => 'foo']));
        $this->assertSame(2, $newCollection->count());
        $record = $newCollection->findOne();
        $this->assertNotNull($record);
        $this->assertObjectHasAttribute('foo', $record);
        $this->assertAttributeSame('bar', 'foo', $record);
    }

    public function testValidateItem()
    {
        $collection = $this->getCollection();
        $batch = new \MongoUpdateBatch($collection);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Expected \$item to contain 'q' key");

        $batch->add([]);
    }
}
