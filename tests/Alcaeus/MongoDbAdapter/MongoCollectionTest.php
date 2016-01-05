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
        $id = '54203e08d51d4a1f868b456e';
        $collection = $this->getCollection();

        $expected = [
            'ok' => 1.0,
            'n' => 0,
            'err' => null,
            'errmsg' => null,
        ];
        $this->assertSame($expected, $collection->insert(['_id' => new \MongoId($id), 'foo' => 'bar']));

        $newCollection = $this->getCheckDatabase()->selectCollection('test');
        $this->assertSame(1, $newCollection->count());
        $object = $newCollection->findOne();

        $this->assertNotNull($object);
        $this->assertAttributeInstanceOf('MongoDB\BSON\ObjectID', '_id', $object);
        $this->assertSame($id, (string) $object->_id);
        $this->assertObjectHasAttribute('foo', $object);
        $this->assertAttributeSame('bar', 'foo', $object);
    }

    public function testUnacknowledgedWrite()
    {
        $this->assertTrue($this->getCollection()->insert(['foo' => 'bar'], ['w' => 0]));
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
    }

    public function testUpdateOne()
    {
        $this->getCollection()->insert(['foo' => 'bar']);
        $this->getCollection()->insert(['foo' => 'bar']);
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

    public function testUpdateMany()
    {
        $this->getCollection()->insert(['change' => true, 'foo' => 'bar']);
        $this->getCollection()->insert(['change' => true, 'foo' => 'bar']);
        $this->getCollection()->insert(['change' => true, 'foo' => 'foo']);
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
        $this->getCollection()->insert(['foo' => 'bar']);
        $this->getCollection()->insert(['foo' => 'bar']);

        $this->assertTrue($this->getCollection()->update(['foo' => 'bar'], ['$set' => ['foo' => 'foo']], ['w' => 0]));
    }

    public function testRemoveMultiple()
    {
        $this->getCollection()->insert(['change' => true, 'foo' => 'bar']);
        $this->getCollection()->insert(['change' => true, 'foo' => 'bar']);
        $this->getCollection()->insert(['change' => true, 'foo' => 'foo']);
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
        $this->getCollection()->insert(['change' => true, 'foo' => 'bar']);
        $this->getCollection()->insert(['change' => true, 'foo' => 'bar']);
        $this->getCollection()->insert(['change' => true, 'foo' => 'foo']);
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
        $this->getCollection()->insert(['change' => true, 'foo' => 'bar']);
        $this->getCollection()->insert(['change' => true, 'foo' => 'bar']);
        $this->getCollection()->insert(['change' => true, 'foo' => 'foo']);

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

    public function testFindOne()
    {
        $this->prepareData();

        $document = $this->getCollection()->findOne(['foo' => 'foo'], ['_id' => false]);
        $this->assertSame(['foo' => 'foo'], $document);
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

    public function testReadPreference()
    {
        $collection = $this->getCollection();
        $this->assertSame(['type' => \MongoClient::RP_PRIMARY], $collection->getReadPreference());
        $this->assertFalse($collection->getSlaveOkay());

        $this->assertTrue($collection->setReadPreference(\MongoClient::RP_SECONDARY, ['a' => 'b']));
        $this->assertSame(['type' => \MongoClient::RP_SECONDARY, 'tagsets' => ['a' => 'b']], $collection->getReadPreference());
        $this->assertTrue($collection->getSlaveOkay());

        // Only way to check whether options are passed down is through debugInfo
        $writeConcern = $collection->getCollection()->__debugInfo()['readPreference'];

        $this->assertSame(ReadPreference::RP_SECONDARY, $writeConcern->getMode());
        $this->assertSame(['a' => 'b'], $writeConcern->getTagSets());

        $this->assertTrue($collection->setSlaveOkay(true));
        $this->assertSame(['type' => \MongoClient::RP_SECONDARY_PREFERRED, 'tagsets' => ['a' => 'b']], $collection->getReadPreference());

        $this->assertTrue($collection->setSlaveOkay(false));
        $this->assertSame(['type' => \MongoClient::RP_PRIMARY], $collection->getReadPreference());
    }

    public function testReadPreferenceIsInherited()
    {
        $database = $this->getDatabase();
        $database->setReadPreference(\MongoClient::RP_SECONDARY, ['a' => 'b']);

        $collection = $database->selectCollection('test');
        $this->assertSame(['type' => \MongoClient::RP_SECONDARY, 'tagsets' => ['a' => 'b']], $collection->getReadPreference());
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

        // Only way to check whether options are passed down is through debugInfo
        $writeConcern = $collection->getCollection()->__debugInfo()['writeConcern'];

        $this->assertSame(2, $writeConcern->getW());
        $this->assertSame(0, $writeConcern->getWtimeout());
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

        $collection->save(['_id' => new \MongoId($id), 'foo' => 'bar']);
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

        $collection->insert(['_id' => new \MongoId($id), 'foo' => 'bar']);
        $collection->remove(['_id' => new \MongoId($id)]);

        $newCollection = $this->getCheckDatabase()->selectCollection('test');
        $this->assertSame(0, $newCollection->count());
    }

    public function testSaveUpdate()
    {
        $id = '54203e08d51d4a1f868b456e';
        $collection = $this->getCollection();

        $collection->insert(['_id' => new \MongoId($id), 'foo' => 'bar']);
        $collection->save(['_id' => new \MongoId($id), 'foo' => 'foo']);

        $newCollection = $this->getCheckDatabase()->selectCollection('test');
        $this->assertSame(1, $newCollection->count());
        $object = $newCollection->findOne();

        $this->assertNotNull($object);
        $this->assertAttributeInstanceOf('MongoDB\BSON\ObjectID', '_id', $object);
        $this->assertSame($id, (string) $object->_id);
        $this->assertObjectHasAttribute('foo', $object);
        $this->assertAttributeSame('foo', 'foo', $object);
    }

    public function testGetDBRef()
    {
        $collection = $this->getCollection();

        $collection->insert(['_id' => 1, 'foo' => 'bar']);

        $document = $collection->getDBRef([
            '$ref' => 'test',
            '$id' => 1,
        ]);
        $this->assertEquals(['_id' => 1, 'foo' => 'bar'], $document);
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
        $collection = $this->getCollection();
        $collection->createIndex(['foo' => 1]);

        $newCollection = $this->getCheckDatabase()->selectCollection('test');
        $iterator = $newCollection->listIndexes();
        $indexes = iterator_to_array($iterator);
        $this->assertCount(2, $indexes);
        $index = $indexes[1];
        $this->assertSame(['foo' => 1], $index->getKey());
        $this->assertSame('mongo-php-adapter.test', $index->getNamespace());
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

        $collection->insert(['_id' => new \MongoId($id), 'foo' => 'bar']);
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

        $collection->insert(['_id' => new \MongoId($id), 'foo' => 'bar']);
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

        $collection->insert([
            '_id' => new \MongoId($id),
            'foo' => 'bar',
            'bar' => 'foo',
        ]);
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

        $collection->insert(['a' => 2]);
        $collection->insert(['b' => 5]);
        $collection->insert(['a' => 1]);
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

    public function testFindAndModifyRemove()
    {
        $id = '54203e08d51d4a1f868b456e';
        $collection = $this->getCollection();

        $collection->insert(['_id' => new \MongoId($id), 'foo' => 'bar']);
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
        $collection->insert(['foo' => 'bar']);
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
        $this->getCollection()->insert(['foo' => 'bar']);
        $expected = [
            'ns' => (string) $this->getCollection(),
            'nIndexesWas' => 1,
            'ok' => 1.0
        ];
        $this->assertSame($expected, $this->getCollection()->drop());
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
