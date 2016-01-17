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
        $collection = $this->getCollection();

        $expected = [
            'ok' => 1.0,
            'n' => 0,
            'err' => null,
            'errmsg' => null,
        ];
        $document = ['foo' => 'bar'];
        $this->assertSame($expected, $collection->insert($document));

        $this->assertInstanceOf('MongoId', $document['_id']);
        $id = (string) $document['_id'];

        $newCollection = $this->getCheckDatabase()->selectCollection('test');
        $this->assertSame(1, $newCollection->count());
        $object = $newCollection->findOne();

        $this->assertNotNull($object);
        $this->assertAttributeInstanceOf('MongoDB\BSON\ObjectID', '_id', $object);
        $this->assertSame($id, (string) $object->_id);
        $this->assertObjectHasAttribute('foo', $object);
        $this->assertAttributeSame('bar', 'foo', $object);
    }

    public function testInsertInvalidData()
    {
        $this->setExpectedException('PHPUnit_Framework_Error_Warning', 'ongoCollection::insert expects parameter 1 to be an array or object, integer given');

        $document = 8;
        $this->getCollection()->insert($document);
    }

    public function testInsertEmptyArray()
    {
        $this->setExpectedException('MongoException', 'document cannot be empty');

        $document = [];
        $this->getCollection()->insert($document);
    }

    public function testInsertArrayWithNumericKeys()
    {
        $this->setExpectedException('MongoException', 'document contain invalid key');

        $document = [1 => 'foo'];
        $this->getCollection()->insert($document);
    }

    public function testInsertEmptyObject()
    {
        $this->setExpectedException('MongoException', 'document cannot be empty');

        $document = (object) [];
        $this->getCollection()->insert($document);
    }

    public function testInsertObjectWithPrivateProperties()
    {
        $this->setExpectedException('MongoException', 'document contain invalid key');

        $document = $this->getCollection();
        $this->getCollection()->insert($document);
    }

    public function testInsertDuplicate()
    {
        $collection = $this->getCollection();

        $collection->createIndex(['foo' => 1], ['unique' => true]);

        $document = ['foo' => 'bar'];
        $collection->insert($document);

        unset($document['_id']);
        $this->assertArraySubset(
            [
                'ok' => 0.0,
                'n' => 0,
                'err' => 11000,
            ],
            $collection->insert($document)
        );
    }

    public function testUnacknowledgedWrite()
    {
        $document = ['foo' => 'bar'];
        $this->assertTrue($this->getCollection()->insert($document, ['w' => 0]));
    }

    public function testInsertWriteConcernException()
    {
        $this->setExpectedException('MongoConnectionException');

        $document = ['foo' => 'bar'];
        $this->getCollection()->insert($document, ['w' => 2]);
    }

    public function testInsertMany()
    {
        $expected = [
            'connectionId' => 0,
            'n' => 0,
            'syncMillis' => 0,
            'writtenTo' => null,
            'err' => null,
            'errmsg' => null
        ];

        $documents = [
            ['foo' => 'bar'],
            ['bar' => 'foo']
        ];
        $this->assertSame($expected, $this->getCollection()->batchInsert($documents));

        foreach ($documents as $document) {
            $this->assertInstanceOf('MongoId', $document['_id']);
        }
    }


    public function testInsertManyWithNonNumericKeys()
    {
        $expected = [
            'connectionId' => 0,
            'n' => 0,
            'syncMillis' => 0,
            'writtenTo' => null,
            'err' => null,
            'errmsg' => null
        ];

        $documents = [
            'a' => ['foo' => 'bar'],
            'b' => ['bar' => 'foo']
        ];
        $this->assertSame($expected, $this->getCollection()->batchInsert($documents));

        $newCollection = $this->getCheckDatabase()->selectCollection('test');
        $this->assertSame(2, $newCollection->count());
    }

    public function testBatchInsertContinuesOnError()
    {
        $expected = [
            'connectionId' => 0,
            'n' => 0,
            'syncMillis' => 0,
            'writtenTo' => null,
            'err' => null,
            'errmsg' => null
        ];

        $documents = [
            8,
            'b' => ['bar' => 'foo']
        ];
        $this->assertSame($expected, $this->getCollection()->batchInsert($documents, ['continueOnError' => true]));

        $newCollection = $this->getCheckDatabase()->selectCollection('test');
        $this->assertSame(1, $newCollection->count());
    }

    public function testBatchInsertException()
    {
        $this->setExpectedException('MongoConnectionException');

        $documents = [['foo' => 'bar']];
        $this->getCollection()->batchInsert($documents, ['w' => 2]);
    }

    public function testBatchInsertEmptyBatchException()
    {
        $this->setExpectedException('MongoException', 'No write ops were included in the batch');

        $documents = [];
        $this->getCollection()->batchInsert($documents, ['w' => 2]);
    }

    public function testUpdateWriteConcern()
    {
        $this->setExpectedException('MongoConnectionException'); // does not match driver

        $this->getCollection()->update([], ['$set' => ['foo' => 'bar']], ['w' => 2]);
    }

    public function testUpdateOne()
    {
        $document = ['foo' => 'bar'];
        $this->getCollection()->insert($document);

        // Unset ID to re-insert
        unset($document['_id']);
        $this->getCollection()->insert($document);

        $expected = [
            'ok' => 1.0,
            'nModified' => 1,
            'n' => 1,
            'err' => null,
            'errmsg' => null,
            'updatedExisting' => true,
        ];

        $result = $this->getCollection()->update(['foo' => 'bar'], ['$set' => ['foo' => 'foo']]);
        $this->assertSame($expected, $result);

        $this->assertSame(1, $this->getCheckDatabase()->selectCollection('test')->count(['foo' => 'foo']));
    }

    public function testUpdateFail()
    {
        $collection = $this->getCollection();
        $collection->createIndex(['foo' => 1], ['unique' => 1]);

        $document = ['foo' => 'bar'];
        $collection->insert($document);
        $document = ['foo' => 'foo'];
        $collection->insert($document);

        $this->assertArraySubset(
            [
                'ok' => 0.0,
                'nModified' => 0,
                'n' => 0,
                'err' => 11000,
                'updatedExisting' => true,
            ],
            $collection->update(['foo' => 'bar'], ['$set' => ['foo' => 'foo']])
        );
    }

    public function testUpdateMany()
    {
        $document = ['change' => true, 'foo' => 'bar'];
        $this->getCollection()->insert($document);

        unset($document['_id']);
        $this->getCollection()->insert($document);

        $document = ['change' => true, 'foo' => 'foo'];
        $this->getCollection()->insert($document);
        $expected = [
            'ok' => 1.0,
            'nModified' => 2,
            'n' => 3,
            'err' => null,
            'errmsg' => null,
            'updatedExisting' => true,
        ];

        $result = $this->getCollection()->update(['change' => true], ['$set' => ['foo' => 'foo']], ['multiple' => true]);
        $this->assertSame($expected, $result);

        $this->assertSame(3, $this->getCheckDatabase()->selectCollection('test')->count(['foo' => 'foo']));
    }

    public function testUnacknowledgedUpdate()
    {
        $document = ['foo' => 'bar'];
        $this->getCollection()->insert($document);
        unset($document['_id']);
        $this->getCollection()->insert($document);

        $this->assertTrue($this->getCollection()->update($document, ['$set' => ['foo' => 'foo']], ['w' => 0]));
    }

    public function testRemoveMultiple()
    {
        $document = ['change' => true, 'foo' => 'bar'];
        $this->getCollection()->insert($document);

        unset($document['_id']);
        $this->getCollection()->insert($document);

        $document = ['change' => true, 'foo' => 'foo'];
        $this->getCollection()->insert($document);
        $expected = [
            'ok' => 1.0,
            'n' => 2,
            'err' => null,
            'errmsg' => null,
        ];

        $result = $this->getCollection()->remove(['foo' => 'bar']);
        $this->assertSame($expected, $result);

        $this->assertSame(1, $this->getCheckDatabase()->selectCollection('test')->count());
    }

    public function testRemoveSingle()
    {
        $document = ['change' => true, 'foo' => 'bar'];
        $this->getCollection()->insert($document);
        unset($document['_id']);
        $this->getCollection()->insert($document);
        unset($document['_id']);
        $this->getCollection()->insert($document);
        $expected = [
            'ok' => 1.0,
            'n' => 1,
            'err' => null,
            'errmsg' => null,
        ];

        $result = $this->getCollection()->remove(['foo' => 'bar'], ['justOne' => true]);
        $this->assertSame($expected, $result);

        $this->assertSame(2, $this->getCheckDatabase()->selectCollection('test')->count());
    }

    public function testRemoveUnacknowledged()
    {
        $document = ['change' => true, 'foo' => 'bar'];
        $this->getCollection()->insert($document);
        unset($document['_id']);
        $this->getCollection()->insert($document);
        unset($document['_id']);
        $this->getCollection()->insert($document);

        $this->assertTrue($this->getCollection()->remove(['foo' => 'bar'], ['w' => 0]));
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

    public function testCountTimeout()
    {
        $this->failMaxTimeMS();

        $this->setExpectedException('MongoExecutionTimeoutException');

        $this->getCollection()->count([], ['maxTimeMS' => 1]);
    }

    public function testFindOne()
    {
        $this->prepareData();

        $document = $this->getCollection()->findOne(['foo' => 'foo'], ['_id' => false]);
        $this->assertSame(['foo' => 'foo'], $document);
    }

    public function testFindOneConnectionIssue()
    {
        $client = $this->getClient([], 'mongodb://localhost:28888?connectTimeoutMS=1');
        $collection = $client->selectCollection('mongo-php-adapter', 'test');

        $this->setExpectedException('MongoConnectionException');

        $collection->findOne();
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

        $this->prepareData();

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

    public function testAggregateTimeoutException()
    {
        $collection = $this->getCollection();

        $this->failMaxTimeMS();

        $this->setExpectedException('MongoExecutionTimeoutException');

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

        $collection->aggregate($pipeline, ['maxTimeMS' => 1]);
    }

    public function testAggregateCursor()
    {
        $collection = $this->getCollection();

        $this->prepareData();

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

        $this->assertTrue($collection->setReadPreference(\MongoClient::RP_SECONDARY, [['a' => 'b']]));
        $this->assertSame(['type' => \MongoClient::RP_SECONDARY, 'tagsets' => [['a' => 'b']]], $collection->getReadPreference());
        $this->assertTrue($collection->getSlaveOkay());

        $this->assertTrue($collection->setSlaveOkay(true));
        $this->assertSame(['type' => \MongoClient::RP_SECONDARY_PREFERRED, 'tagsets' => [['a' => 'b']]], $collection->getReadPreference());

        $this->assertTrue($collection->setSlaveOkay(false));
        $this->assertSame(['type' => \MongoClient::RP_PRIMARY], $collection->getReadPreference());
    }

    public function testReadPreferenceIsSetInDriver()
    {
        $this->skipTestIf(extension_loaded('mongo'));

        $collection = $this->getCollection();

        $this->assertTrue($collection->setReadPreference(\MongoClient::RP_SECONDARY, [['a' => 'b']]));

        // Only way to check whether options are passed down is through debugInfo
        $readPreference = $collection->getCollection()->__debugInfo()['readPreference'];

        $this->assertSame(ReadPreference::RP_SECONDARY, $readPreference->getMode());
        $this->assertSame([['a' => 'b']], $readPreference->getTagSets());
    }

    public function testReadPreferenceIsInherited()
    {
        $database = $this->getDatabase();
        $database->setReadPreference(\MongoClient::RP_SECONDARY, [['a' => 'b']]);

        $collection = $database->selectCollection('test');
        $this->assertSame(['type' => \MongoClient::RP_SECONDARY, 'tagsets' => [['a' => 'b']]], $collection->getReadPreference());
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
    }

    public function testWriteConcernIsSetInDriver()
    {
        $this->skipTestIf(extension_loaded('mongo'));

        $collection = $this->getCollection();
        $this->assertTrue($collection->setWriteConcern(2, 100));

        // Only way to check whether options are passed down is through debugInfo
        $writeConcern = $collection->getCollection()->__debugInfo()['writeConcern'];

        $this->assertSame(2, $writeConcern->getW());
        $this->assertSame(100, $writeConcern->getWtimeout());
    }

    public function testWriteConcernIsInherited()
    {
        $database = $this->getDatabase();
        $database->setWriteConcern('majority', 100);

        $collection = $database->selectCollection('test');
        $this->assertSame(['w' => 'majority', 'wtimeout' => 100], $collection->getWriteConcern());
    }

    public function testSaveInsert()
    {
        $id = '54203e08d51d4a1f868b456e';
        $collection = $this->getCollection();

        $expected = [
            'ok' => 1.0,
            'nModified' => 0,
            'n' => 0,
            'err' => null,
            'errmsg' => null,
            'updatedExisting' => false,
        ];

        $document = ['_id' => new \MongoId($id), 'foo' => 'bar'];
        $this->assertSame($expected, $collection->save($document));

        $newCollection = $this->getCheckDatabase()->selectCollection('test');
        $this->assertSame(1, $newCollection->count());
        $object = $newCollection->findOne();

        $this->assertNotNull($object);
        $this->assertAttributeInstanceOf('MongoDB\BSON\ObjectID', '_id', $object);
        $this->assertSame($id, (string) $object->_id);
        $this->assertObjectHasAttribute('foo', $object);
        $this->assertAttributeSame('bar', 'foo', $object);
    }

    public function testRemoveOne()
    {
        $id = '54203e08d51d4a1f868b456e';
        $collection = $this->getCollection();

        $document = ['_id' => new \MongoId($id), 'foo' => 'bar'];
        $collection->insert($document);
        $collection->remove(['_id' => new \MongoId($id)]);

        $newCollection = $this->getCheckDatabase()->selectCollection('test');
        $this->assertSame(0, $newCollection->count());
    }

    public function testSaveUpdate()
    {
        $expected = [
            'ok' => 1.0,
            'nModified' => 1,
            'n' => 1,
            'err' => null,
            'errmsg' => null,
            'updatedExisting' => true,
        ];

        $id = '54203e08d51d4a1f868b456e';
        $collection = $this->getCollection();

        $insertDocument = ['_id' => new \MongoId($id), 'foo' => 'bar'];
        $saveDocument = ['_id' => new \MongoId($id), 'foo' => 'foo'];
        $collection->insert($insertDocument);
        $this->assertSame($expected, $collection->save($saveDocument));

        $newCollection = $this->getCheckDatabase()->selectCollection('test');
        $this->assertSame(1, $newCollection->count());
        $object = $newCollection->findOne();

        $this->assertNotNull($object);
        $this->assertAttributeInstanceOf('MongoDB\BSON\ObjectID', '_id', $object);
        $this->assertSame($id, (string) $object->_id);
        $this->assertObjectHasAttribute('foo', $object);
        $this->assertAttributeSame('foo', 'foo', $object);
    }

    public function testSavingShouldReplaceTheWholeDocument() {
        $id = '54203e08d51d4a1f868b456e';
        $collection = $this->getCollection();

        $insertDocument = ['_id' => new \MongoId($id), 'foo' => 'bar'];
        $saveDocument = ['_id' => new \MongoId($id)];

        $collection->insert($insertDocument);
        $collection->save($saveDocument);

        $newCollection = $this->getCheckDatabase()->selectCollection('test');
        $this->assertSame(1, $newCollection->count());
        $object = $newCollection->findOne();

        $this->assertNotNull($object);
        $this->assertObjectNotHasAttribute('foo', $object);
    }

    public function testSaveDuplicate()
    {
        $collection = $this->getCollection();

        $collection->createIndex(['foo' => 1], ['unique' => true]);

        $document = ['foo' => 'bar'];
        $collection->save($document);

        $this->setExpectedException('MongoCursorException');

        unset($document['_id']);
        $this->assertArraySubset(
            [
                'ok' => 0.0,
                'nModified' => 0,
                'n' => 0,
                'err' => 11000,
                'updatedExisting' => true,
            ],
            $collection->save($document)
        );
    }

    public function testSaveEmptyKeys()
    {
        $this->setExpectedException('MongoException');

        $document = [];
        $this->getCollection()->save($document);
    }

    public function testSaveEmptyObject()
    {
        $this->setExpectedException('MongoException');

        $document = (object) [];
        $this->getCollection()->save($document);
    }

    public function testSaveWrite()
    {
        $this->setExpectedException('MongoConnectionException'); // should be MongoCursorException

        $document = ['foo' => 'bar'];
        $this->getCollection()->save($document, ['w' => 2, 'wtimeout' => 1000]);
    }

    public function testGetDBRef()
    {
        $collection = $this->getCollection();

        $insertDocument = ['_id' => 1, 'foo' => 'bar'];
        $collection->insert($insertDocument);

        $document = $collection->getDBRef([
            '$ref' => 'test',
            '$id' => 1,
        ]);
        $this->assertEquals($insertDocument, $document);
    }

    public function testCreateDBRef()
    {
        $collection = $this->getCollection();
        $reference = $collection->createDBRef(['_id' => 'foo']);
        $this->assertSame(
            [
                '$ref' => 'test',
                '$id' => 'foo',
            ],
            $reference
        );
    }

    public function testCreateIndex()
    {
        $expected = [
            'createdCollectionAutomatically' => true,
            'numIndexesBefore' => 1,
            'numIndexesAfter' => 2,
            'ok' => 1.0,
        ];

        $collection = $this->getCollection();
        $this->assertSame($expected, $collection->createIndex(['foo' => 1]));

        $newCollection = $this->getCheckDatabase()->selectCollection('test');
        $iterator = $newCollection->listIndexes();
        $indexes = iterator_to_array($iterator);
        $this->assertCount(2, $indexes);
        $index = $indexes[1];
        $this->assertSame(['foo' => 1], $index->getKey());
        $this->assertSame('mongo-php-adapter.test', $index->getNamespace());
    }

    public function testCreateIndexInvalid()
    {
        $this->setExpectedException('MongoException', 'keys cannot be empty');

        $this->getCollection()->createIndex([]);
    }

    public function testCreateIndexTwice()
    {
        $this->getCollection()->createIndex(['foo' => 1]);

        $expected = [
            'createdCollectionAutomatically' => false,
            'numIndexesBefore' => 1,
            'numIndexesAfter' => 1,
            'note' => 'all indexes already exist',
            'ok' => 1.0
        ];
        $this->assertSame($expected, $this->getCollection()->createIndex(['foo' => 1]));
    }

    public function testCreateIndexesWithDifferentOptions()
    {
        $this->setExpectedException('MongoResultException');

        $this->getCollection()->createIndex(['foo' => 1]);

        $this->getCollection()->createIndex(['foo' => 1], ['unique' => true]);
    }

    public function testCreateIndexWithSameName()
    {
        $this->setExpectedException('MongoResultException');

        $this->getCollection()->createIndex(['foo' => 1], ['name' => 'foo']);

        $this->getCollection()->createIndex(['bar' => 1], ['name' => 'foo']);
    }

    public function testEnsureIndex()
    {
        $collection = $this->getCollection();
        $this->assertTrue($collection->ensureIndex(['bar' => 1], ['unique' => true]));

        $newCollection = $this->getCheckDatabase()->selectCollection('test');
        $indexes = iterator_to_array($newCollection->listIndexes());
        $this->assertCount(2, $indexes);
        $index = $indexes[1];
        $this->assertSame(['bar' => 1], $index->getKey());
        $this->assertTrue($index->isUnique());
        $this->assertSame('mongo-php-adapter.test', $index->getNamespace());
    }

    public function testDeleteIndexUsingIndexName()
    {
        $newCollection = $this->getCheckDatabase()->selectCollection('test');
        $newCollection->createIndex(['bar' => 1], ['name' => 'bar']);

        $expected = [
            'nIndexesWas' => 2,
            'ok' => 1.0,
        ];
        $this->assertSame($expected, $this->getCollection()->deleteIndex('bar'));

        $this->assertCount(1, iterator_to_array($newCollection->listIndexes()));
    }

    public function testDeleteIndexUsingKeys()
    {
        $newCollection = $this->getCheckDatabase()->selectCollection('test');
        $newCollection->createIndex(['bar' => 1]);

        $expected = [
            'nIndexesWas' => 2,
            'ok' => 1.0,
        ];
        $this->assertSame($expected, $this->getcollection()->deleteIndex(['bar' => 1]));

        $this->assertCount(1, iterator_to_array($newCollection->listIndexes()));
    }

    public function testDeleteIndexes()
    {
        $newCollection = $this->getCheckDatabase()->selectCollection('test');
        $newCollection->createIndex(['bar' => 1]);

        $expected = [
            'nIndexesWas' => 2,
            'msg' => 'non-_id indexes dropped for collection',
            'ok' => 1.0,
        ];
        $this->assertSame($expected, $this->getcollection()->deleteIndexes());

        $this->assertCount(1, iterator_to_array($newCollection->listIndexes())); // ID index is present by default
    }

    public function testGetIndexInfo()
    {
        $collection = $this->getCollection();
        $collection->createIndex(['foo' => 1]);

        $expected = [
            [
                'v' => 1,
                'key' => ['_id' => 1],
                'name' => '_id_',
                'ns' => 'mongo-php-adapter.test',
            ],
            [
                'v' => 1,
                'key' => ['foo' => 1],
                'name' => 'foo_1',
                'ns' => 'mongo-php-adapter.test',
            ],
        ];

        $this->assertSame(
            $expected,
            $collection->getIndexInfo()
        );
    }

    public function testFindAndModifyUpdate()
    {
        $id = '54203e08d51d4a1f868b456e';
        $collection = $this->getCollection();

        $document = ['_id' => new \MongoId($id), 'foo' => 'bar'];
        $collection->insert($document);
        $document = $collection->findAndModify(
            ['_id' => new \MongoId($id)],
            ['$set' => ['foo' => 'foo']]
        );
        $this->assertSame('bar', $document['foo']);

        $newCollection = $this->getCheckDatabase()->selectCollection('test');
        $this->assertSame(1, $newCollection->count());
        $object = $newCollection->findOne();

        $this->assertNotNull($object);
        $this->assertAttributeSame('foo', 'foo', $object);
    }

    public function testFindAndModifyUpdateReturnNew()
    {
        $id = '54203e08d51d4a1f868b456e';
        $collection = $this->getCollection();

        $document = ['_id' => new \MongoId($id), 'foo' => 'bar'];
        $collection->insert($document);
        $document = $collection->findAndModify(
            ['_id' => new \MongoId($id)],
            ['$set' => ['foo' => 'foo']],
            null,
            ['new' => true]
        );
        $this->assertSame('foo', $document['foo']);
    }

    public function testFindAndModifyWithFields()
    {
        $id = '54203e08d51d4a1f868b456e';
        $collection = $this->getCollection();

        $document = [
            '_id' => new \MongoId($id),
            'foo' => 'bar',
            'bar' => 'foo',
        ];
        $collection->insert($document);
        $document = $collection->findAndModify(
            ['_id' => new \MongoId($id)],
            ['$set' => ['foo' => 'foo']],
            ['foo' => true]
        );
        $this->assertArrayNotHasKey('bar', $document);
        $this->assertArrayHasKey('foo', $document);
    }

    public function testGroup()
    {
        $collection = $this->getCollection();

        $document1 = ['a' => 2];
        $collection->insert($document1);
        $document2 = ['b' => 5];
        $collection->insert($document2);
        $document3 = ['a' => 1];
        $collection->insert($document3);
        $keys = [];
        $initial = ["count" => 0];
        $reduce = "function (obj, prev) { prev.count++; }";
        $condition = ['condition' => ["a" => [ '$gt' => 1]]];

        $result = $collection->group($keys, $initial, $reduce, $condition);

        $this->assertArraySubset(
            [
                'retval' => [['count' => 1.0]],
                'count' => 1.0,
                'keys' => 1,
                'ok' => 1.0,
            ],
            $result
        );
    }

    public function testFindAndModifyResultException()
    {
        $this->markTestSkipped('Test fails on travis-ci - skipped while investigating this');
        $collection = $this->getCollection();

        $this->setExpectedException('MongoResultException');

        $collection->findAndModify(
            array("inprogress" => false, "name" => "Next promo"),
            array('$unsupportedOperator' => array("tasks" => -1)),
            array("tasks" => true),
            array("new" => true)
        );
    }

    public function testFindAndModifyExceptionTimeout()
    {
        $this->failMaxTimeMS();

        $id = '54203e08d51d4a1f868b456e';
        $collection = $this->getCollection();

        $this->setExpectedException('MongoExecutionTimeoutException');

        $document = $collection->findAndModify(
            ['_id' => new \MongoId($id)],
            null,
            null,
            ['maxTimeMS' => 1, 'remove' => true]
        );
    }

    public function testFindAndModifyRemove()
    {
        $id = '54203e08d51d4a1f868b456e';
        $collection = $this->getCollection();

        $document = ['_id' => new \MongoId($id), 'foo' => 'bar'];
        $collection->insert($document);
        $document = $collection->findAndModify(
            ['_id' => new \MongoId($id)],
            null,
            null,
            ['remove' => true]
        );

        $this->assertEquals('bar', $document['foo']);

        $newCollection = $this->getCheckDatabase()->selectCollection('test');
        $this->assertSame(0, $newCollection->count());
    }

    public function testValidate()
    {
        $collection = $this->getCollection();
        $document = ['foo' => 'bar'];
        $collection->insert($document);
        $result = $collection->validate();

        $this->assertArraySubset(
            [
                'ns' => 'mongo-php-adapter.test',
                'nrecords' => 1,
                'nIndexes' => 1,
                'keysPerIndex' => ['mongo-php-adapter.test.$_id_' => 1],
                'valid' => true,
                'errors' => [],
                'warning' => 'Some checks omitted for speed. use {full:true} option to do more thorough scan.',
                'ok'  => 1.0
            ],
            $result
        );
    }

    public function testDrop()
    {
        $document = ['foo' => 'bar'];
        $this->getCollection()->insert($document);
        $expected = [
            'ns' => (string) $this->getCollection(),
            'nIndexesWas' => 1,
            'ok' => 1.0
        ];
        $this->assertSame($expected, $this->getCollection()->drop());
    }

    public function testEmptyCollectionName()
    {
        $this->setExpectedException('Exception', 'Collection name cannot be empty');

        new \MongoCollection($this->getDatabase(), '');
    }

    public function testSelectCollectionWithNullBytes()
    {
        $this->setExpectedException('Exception', 'Collection name cannot contain null bytes');

        new \MongoCollection($this->getDatabase(), 'foo' . chr(0));
    }

    public function testSubCollectionWithNullBytes()
    {
        $collection = $this->getCollection();

        $this->setExpectedException('Exception', 'Collection name cannot contain null bytes');

        $collection->{'foo' . chr(0)};
    }
}
