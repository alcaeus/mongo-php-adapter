<?php

namespace Alcaeus\MongoDbAdapter\Tests\Mongo;

use Alcaeus\MongoDbAdapter\Tests\TestCase;
use Alcaeus\MongoDbAdapter\TypeConverter;
use Countable;
use MongoCursorInterface;
use MongoDB\Driver\ReadPreference;
use MongoDB\Model\BSONDocument;
use MongoDB\Operation\Find;

/**
 * @author alcaeus <alcaeus@alcaeus.org>
 */
class MongoCursorTest extends TestCase
{
    public function testSerialize()
    {
        $this->prepareData();
        $cursor = $this->getCollection()->find(['foo' => 'bar']);
        $this->assertIsString(serialize($cursor));
    }

    public function testCursorConvertsTypes()
    {
        $this->prepareData();

        $collection = $this->getCollection();
        $cursor = $collection->find(['foo' => 'bar']);
        $this->assertCount(2, $cursor);

        $this->assertCursorIteration($cursor);
    }

    public function testCursorHandlesHasNextBeforeIteration()
    {
        $this->prepareData();

        $collection = $this->getCollection();
        $cursor = $collection->find(['foo' => 'bar']);
        $this->assertTrue($cursor->hasNext());

        $this->assertCursorIteration($cursor);
    }

    private function assertCursorIteration($cursor)
    {
        $iterated = 0;
        foreach ($cursor as $key => $item) {
            $this->assertSame($iterated, $cursor->info()['at']);
            $this->assertInstanceOf('MongoId', $item['_id']);
            $this->assertEquals($key, (string) $item['_id']);
            $this->assertSame('bar', $item['foo']);
            $iterated++;
        }

        $this->assertSame(2, $iterated);
    }

    public function testCount()
    {
        $this->prepareData();

        $collection = $this->getCollection();
        $cursor = $collection->find(['foo' => 'bar'])->limit(1);

        $this->assertSame(2, $cursor->count());
        $this->assertSame(1, $cursor->count(true));
    }

    public function testCountCannotConnect()
    {
        $client = $this->getClient(['connect' => false], 'mongodb://localhost:28888');
        $cursor = $client->selectCollection('mongo-php-adapter', 'test')->find();

        $this->expectException(\MongoConnectionException::class);

        $cursor->count();
    }

    public function testCountAfterIteration()
    {
        $this->prepareData();

        $collection = $this->getCollection();
        $cursor = $collection->find(['foo' => 'bar']);

        // Ensure the generator is consumed and thus closed
        iterator_to_array($cursor);
        $this->assertSame(2, $cursor->count(true));
    }

    public function testNextStartsWithFirstItem()
    {
        $this->prepareData();

        $collection = $this->getCollection();
        $cursor = $collection->find(['foo' => 'bar']);

        $this->assertTrue($cursor->hasNext());
        $item = $cursor->getNext();
        $this->assertNotNull($item);
        $this->assertInstanceOf('MongoId', $item['_id']);
        $this->assertSame('bar', $item['foo']);

        $this->assertTrue($cursor->hasNext());
        $item = $cursor->getNext();
        $this->assertNotNull($item);
        $this->assertInstanceOf('MongoId', $item['_id']);
        $this->assertSame('bar', $item['foo']);

        $this->assertFalse($cursor->hasNext());
        $item = $cursor->getNext();
        $this->assertNull($item);

        $cursor->reset();

        $this->assertTrue($cursor->hasNext());
        $item = $cursor->getNext();
        $this->assertNotNull($item);
        $this->assertInstanceOf('MongoId', $item['_id']);
        $this->assertSame('bar', $item['foo']);

        $item = $cursor->getNext();
        $this->assertNotNull($item);
        $this->assertInstanceOf('MongoId', $item['_id']);
        $this->assertSame('bar', $item['foo']);
    }

    public function testIteratorInterface()
    {
        $this->prepareData();

        $collection = $this->getCollection();
        $cursor = $collection->find(['foo' => 'bar']);

        $this->assertFalse($cursor->valid(), 'Cursor should be invalid to start with');
        $this->assertNull($cursor->current(), 'Cursor should be invalid to start with');
        $this->assertNull($cursor->key(), 'Cursor should be invalid to start with');

        $cursor->next();
        $this->assertTrue($cursor->valid(), 'Cursor should be valid');

        $item = $cursor->current();
        $this->assertNotNull($item);
        $this->assertInstanceOf('MongoId', $item['_id']);
        $this->assertSame('bar', $item['foo']);

        $cursor->next();

        $item = $cursor->current();
        $this->assertNotNull($item);
        $this->assertInstanceOf('MongoId', $item['_id']);
        $this->assertSame('bar', $item['foo']);

        $cursor->next();

        $this->assertNull($cursor->current(), 'Cursor should return null at the end');
        $this->assertFalse($cursor->valid(), 'Cursor should be invalid');

        $cursor->rewind();

        $item = $cursor->current();
        $this->assertNotNull($item);
        $this->assertInstanceOf('MongoId', $item['_id']);
        $this->assertSame('bar', $item['foo']);
    }

    /**
     * @dataProvider getCursorOptions
     */
    public function testCursorAppliesOptions($checkOptionCallback, \Closure $applyOptionCallback = null)
    {
        $this->skipTestIf(extension_loaded('mongo'));

        $query = ['foo' => 'bar'];
        $projection = ['_id' => false, 'foo' => true];

        $collectionMock = $this->getCollectionMock();
        $collectionMock
            ->expects($this->once())
            ->method('find')
            ->with($this->equalTo(TypeConverter::fromLegacy($query)), $this->callback($checkOptionCallback))
            ->will($this->returnValue(new \ArrayIterator([])));

        $collection = $this->getCollection('test');
        $cursor = $collection->find($query, $projection);

        // Replace the original MongoDB collection with our mock
        $reflectionProperty = new \ReflectionProperty($cursor, 'collection');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($cursor, $collectionMock);

        if ($applyOptionCallback !== null) {
            $applyOptionCallback($cursor);
        }

        // Force query by converting to array
        iterator_to_array($cursor);
    }

    public static function getCursorOptions()
    {
        function getMissingOptionCallback($optionName)
        {
            return function ($value) use ($optionName) {
                return
                    is_array($value) &&
                    ! array_key_exists($optionName, $value);
            };
        }

        function getBasicCheckCallback($expected, $optionName)
        {
            return function ($value) use ($expected, $optionName) {
                return
                    is_array($value) &&
                    array_key_exists($optionName, $value) &&
                    $value[$optionName] == $expected;
            };
        }

        function getModifierCheckCallback($expected, $modifierName)
        {
            return function ($value) use ($expected, $modifierName) {
                return
                    is_array($value) &&
                    is_array($value['modifiers']) &&
                    array_key_exists($modifierName, $value['modifiers']) &&
                    $value['modifiers'][$modifierName] == $expected;
            };
        }

        $tests = [
            'allowPartialResults' => [
                getBasicCheckCallback(true, 'allowPartialResults'),
                function (\MongoCursor $cursor) {
                    $cursor->partial(true);
                },
            ],
            'batchSize' => [
                getBasicCheckCallback(10, 'batchSize'),
                function (\MongoCursor $cursor) {
                    $cursor->batchSize(10);
                },
            ],
            'cursorTypeNonTailable' => [
                getMissingOptionCallback('cursorType'),
                function (\MongoCursor $cursor) {
                    $cursor
                        ->tailable(false)
                        ->awaitData(true);
                },
            ],
            'cursorTypeTailable' => [
                getBasicCheckCallback(Find::TAILABLE, 'cursorType'),
                function (\MongoCursor $cursor) {
                    $cursor->tailable(true);
                },
            ],
            'cursorTypeTailableAwait' => [
                getBasicCheckCallback(Find::TAILABLE_AWAIT, 'cursorType'),
                function (\MongoCursor $cursor) {
                    $cursor->tailable(true)->awaitData(true);
                },
            ],
            'hint' => [
                getModifierCheckCallback('index_name', '$hint'),
                function (\MongoCursor $cursor) {
                    $cursor->hint('index_name');
                },
            ],
            'limit' => [
                getBasicCheckCallback(5, 'limit'),
                function (\MongoCursor $cursor) {
                    $cursor->limit(5);
                }
            ],
            'maxTimeMS' => [
                getBasicCheckCallback(100, 'maxTimeMS'),
                function (\MongoCursor $cursor) {
                    $cursor->maxTimeMS(100);
                },
            ],
            'noCursorTimeout' => [
                getBasicCheckCallback(true, 'noCursorTimeout'),
                function (\MongoCursor $cursor) {
                    $cursor->immortal(true);
                },
            ],
            'slaveOkay' => [
                getBasicCheckCallback(new ReadPreference(ReadPreference::RP_SECONDARY_PREFERRED), 'readPreference'),
                function (\MongoCursor $cursor) {
                    $cursor->slaveOkay(true);
                },
            ],
            'slaveOkayWithReadPreferenceSet' => [
                getBasicCheckCallback(new ReadPreference(ReadPreference::RP_SECONDARY), 'readPreference'),
                function (\MongoCursor $cursor) {
                    $cursor
                        ->setReadPreference(\MongoClient::RP_SECONDARY)
                        ->slaveOkay(true);
                },
            ],
            'projectionDefaultFields' => [
                getBasicCheckCallback(new BSONDocument(['_id' => false, 'foo' => true]), 'projection'),
            ],
            'projectionDifferentFields' => [
                getBasicCheckCallback(new BSONDocument(['_id' => false, 'foo' => true, 'bar' => true]), 'projection'),
                function (\MongoCursor $cursor) {
                    $cursor->fields(['_id' => false, 'foo' => true, 'bar' => true]);
                },
            ],
            'readPreferencePrimary' => [
                getBasicCheckCallback(new ReadPreference(ReadPreference::RP_PRIMARY), 'readPreference'),
                function (\MongoCursor $cursor) {
                    $cursor->setReadPreference(\MongoClient::RP_PRIMARY);
                },
            ],
            'skip' => [
                getBasicCheckCallback(5, 'skip'),
                function (\MongoCursor $cursor) {
                    $cursor->skip(5);
                },
            ],
            'sort' => [
                getBasicCheckCallback(['foo' => -1], 'sort'),
                function (\MongoCursor $cursor) {
                    $cursor->sort(['foo' => -1]);
                },
            ],
        ];

        return $tests;
    }

    public function testCursorInfo()
    {
        $this->prepareData();

        $collection = $this->getCollection();
        $cursor = $collection->find(['foo' => 'bar'], ['_id' => false])->skip(1)->limit(3);

        $expected = [
            'ns' => 'mongo-php-adapter.test',
            'limit' => 3,
            'batchSize' => 0,
            'skip' => 1,
            'flags' => 0,
            'query' => ['foo' => 'bar'],
            'fields' => ['_id' => false],
            'started_iterating' => false,
        ];

        $this->assertSame($expected, $cursor->info());

        // Ensure cursor started iterating
        iterator_to_array($cursor);

        $expected['started_iterating'] = true;
        $expected += [
            'id' => 0,
            'at' => 1,
            'numReturned' => 1,
            'server' => 'localhost:27017;-;.;' . getmypid(),
            'host' => 'localhost',
            'port' => 27017,
            'connection_type_desc' => 'STANDALONE'
        ];

        $this->assertSame($expected, $cursor->info());
    }

    public function testCursorInfoWithBatchSize()
    {
        $this->prepareData();

        $collection = $this->getCollection();
        $cursor = $collection->find(['foo' => 'bar'], ['_id' => false])->skip(1)->limit(3);
        $cursor->batchSize(1);

        $expected = [
            'ns' => 'mongo-php-adapter.test',
            'limit' => 3,
            'batchSize' => 1,
            'skip' => 1,
            'flags' => 0,
            'query' => ['foo' => 'bar'],
            'fields' => ['_id' => false],
            'started_iterating' => false,
        ];

        $this->assertSame($expected, $cursor->info());

        // Ensure cursor started iterating
        iterator_to_array($cursor);

        $expected['started_iterating'] = true;
        $expected += [
            'id' => 0,
            'at' => 1,
            'numReturned' => 1,
            'server' => 'localhost:27017;-;.;' . getmypid(),
            'host' => 'localhost',
            'port' => 27017,
            'connection_type_desc' => 'STANDALONE'
        ];

        $this->assertSame($expected, $cursor->info());
    }

    public function testReadPreferenceIsInherited()
    {
        $collection = $this->getCollection();
        $collection->setReadPreference(\MongoClient::RP_SECONDARY, [['a' => 'b']]);

        $cursor = $collection->find(['foo' => 'bar']);
        $this->assertSame(['type' => \MongoClient::RP_SECONDARY, 'tagsets' => [['a' => 'b']]], $cursor->getReadPreference());
    }

    public function testExplain()
    {
        $this->prepareData();

        $collection = $this->getCollection();
        $cursor = $collection->find(['foo' => 'bar'], ['_id' => false])->skip(1)->limit(3);

        $expected = [
            'queryPlanner' => [
                'plannerVersion' => 1,
                'namespace' => 'mongo-php-adapter.test',
                'indexFilterSet' => false,
                'parsedQuery' => [
                    'foo' => ['$eq' => 'bar']
                ],
                'winningPlan' => ['$$exists' => true],
                'rejectedPlans' => ['$$exists' => true],
            ],
            'executionStats' => [
                'executionSuccess' => true,
                'nReturned' => 1,
                'totalKeysExamined' => 0,
                'totalDocsExamined' => 3,
                'executionStages' => ['$$exists' => true],
                'allPlansExecution' => ['$$exists' => true],
            ],
            'serverInfo' => [
                'port' => 27017,
            ],
        ];

        $this->assertMatches($expected, $cursor->explain());
    }

    public function testExplainWithEmptyProjection()
    {
        $this->prepareData();

        $collection = $this->getCollection();
        $cursor = $collection->find(['foo' => 'bar']);

        $expected = [
            'queryPlanner' => [
                'plannerVersion' => 1,
                'namespace' => 'mongo-php-adapter.test',
                'indexFilterSet' => false,
                'parsedQuery' => [
                    'foo' => ['$eq' => 'bar']
                ],
                'winningPlan' => ['$$exists' => true],
                'rejectedPlans' => ['$$exists' => true],
            ],
            'executionStats' => [
                'executionSuccess' => true,
                'nReturned' => 2,
                'totalKeysExamined' => 0,
                'totalDocsExamined' => 3,
                'executionStages' => ['$$exists' => true],
                'allPlansExecution' => ['$$exists' => true],
            ],
            'serverInfo' => [
                'port' => 27017,
            ],
        ];

        $this->assertMatches($expected, $cursor->explain());
    }

    public function testExplainConvertsQuery()
    {
        $this->prepareData();

        $collection = $this->getCollection();
        $cursor = $collection->find(['foo' => new \MongoRegex('/^b/')]);

        $expected = [
            'queryPlanner' => [
                'plannerVersion' => 1,
                'namespace' => 'mongo-php-adapter.test',
                'indexFilterSet' => false,
                'winningPlan' => ['$$exists' => true],
                'rejectedPlans' => ['$$exists' => true],
            ],
            'executionStats' => [
                'executionSuccess' => true,
                'nReturned' => 2,
                'totalKeysExamined' => 0,
                'totalDocsExamined' => 3,
                'executionStages' => ['$$exists' => true],
                'allPlansExecution' => ['$$exists' => true],
            ],
            'serverInfo' => [
                'port' => 27017,
            ],
        ];

        $this->assertMatches($expected, $cursor->explain());
    }

    public function testInterfaces()
    {
        $collection = $this->getCollection();
        $cursor = $collection->find();

        $this->assertInstanceOf(MongoCursorInterface::class, $cursor);

        // The countable interface is necessary for compatibility with PHP 7.3+, but not implemented by MongoCursor
        if (! extension_loaded('mongo')) {
            $this->assertInstanceOf(Countable::class, $cursor);
        }
    }


    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getCollectionMock()
    {
        return $this->createMock('MongoDB\Collection', [], [], '', false);
    }
}
