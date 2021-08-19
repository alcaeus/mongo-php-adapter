<?php

namespace Alcaeus\MongoDbAdapter\Tests\Mongo;

use Alcaeus\MongoDbAdapter\Tests\TestCase;
use MongoDB\Driver\ReadPreference;

/**
 * @author alcaeus <alcaeus@alcaeus.org>
 */
class MongoDBTest extends TestCase
{
    public function testSerialize()
    {
        $this->assertIsString(serialize($this->getDatabase()));
    }

    public function testEmptyDatabaseName()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database name cannot be empty');

        new \MongoDB($this->getClient(), '');
    }

    public function testInvalidDatabaseName()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database name contains invalid characters');

        new \MongoDB($this->getClient(), '/');
    }

    public function testGetCollection()
    {
        $db = $this->getDatabase();
        $collection = $db->selectCollection('test');
        $this->assertInstanceOf('MongoCollection', $collection);
        $this->assertSame('mongo-php-adapter.test', (string) $collection);
    }

    public function testSelectCollectionEmptyName()
    {
        $database = $this->getDatabase();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Collection name cannot be empty');

        $database->selectCollection('');
    }

    public function testSelectCollectionWithNullBytes()
    {
        $database = $this->getDatabase();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Collection name cannot contain null bytes');

        $database->selectCollection('foo' . chr(0));
    }

    public function testCreateCollectionWithoutOptions()
    {
        $database = $this->getDatabase();

        $collection = $database->createCollection('test');
        $this->assertInstanceOf('MongoCollection', $collection);

        $checkDatabase = $this->getCheckDatabase();
        foreach ($checkDatabase->listCollections() as $collectionInfo) {
            if ($collectionInfo->getName() === 'test') {
                $this->assertFalse($collectionInfo->isCapped());
                return;
            }
        }

        $this->fail('Did not find expected collection');
    }

    public function testCreateCollection()
    {
        $database = $this->getDatabase();

        $collection = $database->createCollection('test', ['capped' => true, 'size' => 100]);
        $this->assertInstanceOf('MongoCollection', $collection);

        $document = ['foo' => 'bar'];
        $collection->insert($document);

        $checkDatabase = $this->getCheckDatabase();
        foreach ($checkDatabase->listCollections() as $collectionInfo) {
            if ($collectionInfo->getName() === 'test') {
                $this->assertTrue($collectionInfo->isCapped());
                return;
            }
        }
    }

    public function testCreateCollectionInvalidParameters()
    {
        $database = $this->getDatabase();

        $this->assertInstanceOf('MongoCollection', $database->createCollection('test', ['capped' => 2, 'size' => 100]));
    }

    public function testGetCollectionProperty()
    {
        $db = $this->getDatabase();
        $collection = $db->test;
        $this->assertInstanceOf('MongoCollection', $collection);
        $this->assertSame('mongo-php-adapter.test', (string) $collection);
    }

    public function testCommand()
    {
        $db = $this->getDatabase();
        $this->assertEquals(['ok' => 1], $db->command(['ping' => 1]));
    }

    public function testCommandError()
    {
        $db = $this->getDatabase();
        $expected = [
            'ok' => 0,
            'errmsg' => 'listDatabases may only be run against the admin database.',
            'code' => 13,
        ];

        // Using assertMatches because newer versions (3.4.7?) also return `codeName`
        $this->assertMatches($expected, $db->command(['listDatabases' => 1]));
    }

    public function testCommandCursorTimeout()
    {
        $database = $this->getDatabase();

        $this->failMaxTimeMS();

        $result = $database->command([
            "count" => "test",
            "query" => array("a" => 1),
            "maxTimeMS" => 100,
        ]);

        $this->assertSame([
            'ok' => 0.0,
            'errmsg' => 'operation exceeded time limit',
            'code' => 50,
        ], $result);
    }

    public function testReadPreference()
    {
        $database = $this->getDatabase();
        $this->assertSame(['type' => \MongoClient::RP_PRIMARY], $database->getReadPreference());
        $this->assertFalse($database->getSlaveOkay());

        $this->assertTrue($database->setReadPreference(\MongoClient::RP_SECONDARY, [['a' => 'b']]));
        $this->assertSame(['type' => \MongoClient::RP_SECONDARY, 'tagsets' => [['a' => 'b']]], $database->getReadPreference());
        $this->assertTrue($database->getSlaveOkay());

        $this->assertTrue($database->setSlaveOkay(true));
        $this->assertSame(['type' => \MongoClient::RP_SECONDARY_PREFERRED, 'tagsets' => [['a' => 'b']]], $database->getReadPreference());

        $this->assertTrue($database->setSlaveOkay(false));
        // Only test a subset since we don't keep tagsets around for RP_PRIMARY
        $this->assertMatches(['type' => \MongoClient::RP_PRIMARY], $database->getReadPreference());
    }

    public function testReadPreferenceIsSetInDriver()
    {
        $this->skipTestIf(extension_loaded('mongo'));

        $database = $this->getDatabase();

        $this->assertTrue($database->setReadPreference(\MongoClient::RP_SECONDARY, [['a' => 'b']]));

        // Only way to check whether options are passed down is through debugInfo
        $readPreference = $database->getDb()->__debugInfo()['readPreference'];

        $this->assertSame(ReadPreference::RP_SECONDARY, $readPreference->getMode());
        $this->assertSame([['a' => 'b']], $readPreference->getTagSets());
    }

    public function testReadPreferenceIsInherited()
    {
        $client = $this->getClient();
        $client->setReadPreference(\MongoClient::RP_SECONDARY, [['a' => 'b']]);

        $database = $client->selectDB('test');
        $this->assertSame(['type' => \MongoClient::RP_SECONDARY, 'tagsets' => [['a' => 'b']]], $database->getReadPreference());
    }

    public function testWriteConcern()
    {
        $database = $this->getDatabase();

        $this->assertTrue($database->setWriteConcern('majority', 100));
        $this->assertSame(['w' => 'majority', 'wtimeout' => 100], $database->getWriteConcern());
    }

    public function testWriteConcernIsSetInDriver()
    {
        $this->skipTestIf(extension_loaded('mongo'));

        $database = $this->getDatabase();
        $this->assertTrue($database->setWriteConcern(2, 100));

        // Only way to check whether options are passed down is through debugInfo
        $writeConcern = $database->getDb()->__debugInfo()['writeConcern'];

        $this->assertSame(2, $writeConcern->getW());
        $this->assertSame(100, $writeConcern->getWtimeout());
    }

    public function testWriteConcernIsInherited()
    {
        $client = $this->getClient();
        $client->setWriteConcern(2, 100);

        $database = $client->selectDB('test');
        $this->assertSame(['w' => 2, 'wtimeout' => 100], $database->getWriteConcern());
    }

    public function testProfilingLevel()
    {
        $this->assertSame(\MongoDB::PROFILING_OFF, $this->getDatabase()->getProfilingLevel());
        $this->assertSame(\MongoDB::PROFILING_OFF, $this->getDatabase()->setProfilingLevel(\MongoDB::PROFILING_SLOW));

        $this->assertSame(\MongoDB::PROFILING_SLOW, $this->getDatabase()->getProfilingLevel());
        $this->assertSame(\MongoDB::PROFILING_SLOW, $this->getDatabase()->setProfilingLevel(\MongoDB::PROFILING_ON));
        $this->assertSame(\MongoDB::PROFILING_ON, $this->getDatabase()->getProfilingLevel());
    }

    public function testForceError()
    {
        $result = $this->getDatabase()->forceError();
        $this->assertSame(0.0, $result['ok']);
    }

    public function testExecute()
    {
        $this->skipTestIf(version_compare($this->getServerVersion(), '4.2.0', '>='), 'Eval no longer works on MongoDB 4.2.0 and newer');

        $db = $this->getDatabase();
        $document = ['foo' => 'bar'];
        $this->getCollection()->insert($document);
        $this->assertEquals(['ok' => 1, 'retval' => 1], $db->execute("return db.test.count();"));
    }

    public function testGetCollectionNames()
    {
        $document = ['foo' => 'bar'];
        $this->getCollection()->insert($document);
        $this->assertContains('test', $this->getDatabase()->getCollectionNames());
    }

    public function testGetCollectionNamesExecutionTimeoutException()
    {
        $document = ['foo' => 'bar'];
        $this->getCollection()->insert($document);
        $database = $this->getDatabase();

        $this->failMaxTimeMS();

        $this->expectException(\MongoExecutionTimeoutException::class);

        $database->getCollectionNames(['maxTimeMS' => 1]);
    }

    public function testGetCollectionInfo()
    {
        $document = ['foo' => 'bar'];
        $this->getCollection()->insert($document);

        foreach ($this->getDatabase()->getCollectionInfo() as $collectionInfo) {
            if ($collectionInfo['name'] === 'test') {
                $expected = [
                    'name' => 'test',
                    'options' => []
                ];

                if (version_compare($this->getServerVersion(), '3.4.0', '>=')) {
                    $expected += [
                        'type' => 'collection',
                        'info' => ['readOnly' => false],
                        'idIndex' => [
                            'v' => $this->getDefaultIndexVersion(),
                            'key' => ['_id' => 1],
                            'name' => '_id_',
                            'ns' => (string) $this->getCollection(),
                        ],
                    ];
                }
                $this->assertMatches($expected, $collectionInfo);
                return;
            }
        }

        $this->fail('The test collection was not found');
    }

    public function testGetCollectionInfoExecutionTimeoutException()
    {
        $document = ['foo' => 'bar'];
        $this->getCollection()->insert($document);

        $database = $this->getDatabase();

        $this->failMaxTimeMS();

        $this->expectException(\MongoExecutionTimeoutException::class);

        $database->getCollectionInfo(['maxTimeMS' => 1]);
    }

    public function testListCollections()
    {
        $document = ['foo' => 'bar'];
        $this->getCollection()->insert($document);
        foreach ($this->getDatabase()->listCollections() as $collection) {
            $this->assertInstanceOf('MongoCollection', $collection);

            if ($collection->getName() === 'test') {
                return;
            }
        }

        $this->fail('The test collection was not found');
    }

    public function testGetCollectionNamesDoesNotListSystemCollections()
    {
        // Enable profiling to ensure we have a system.profile collection
        $this->getDatabase()->setProfilingLevel(\MongoDB::PROFILING_ON);

        try {
            $document = ['foo' => 'bar'];
            $this->getCollection()->insert($document);

            $collectionNames = $this->getDatabase()->getCollectionNames();
            $this->assertNotContains('system.profile', $collectionNames);
        } finally {
            $this->getDatabase()->setProfilingLevel(\MongoDB::PROFILING_OFF);
        }
    }

    public function testGetCollectionNamesWithSystemCollections()
    {
        // Enable profiling to ensure we have a system.profile collection
        $this->getDatabase()->setProfilingLevel(\MongoDB::PROFILING_ON);

        try {
            $document = ['foo' => 'bar'];
            $this->getCollection()->insert($document);

            $collectionNames = $this->getDatabase()->getCollectionNames(['includeSystemCollections' => true]);
            $this->assertContains('system.profile', $collectionNames);
        } finally {
            $this->getDatabase()->setProfilingLevel(\MongoDB::PROFILING_OFF);
        }
    }

    public function testListCollectionsExecutionTimeoutException()
    {
        $this->failMaxTimeMS();

        $this->expectException(\MongoExecutionTimeoutException::class);

        $this->getDatabase()->listCollections(['maxTimeMS' => 1]);
    }

    public function testDrop()
    {
        $document = ['foo' => 'bar'];
        $this->getCollection()->insert($document);
        $this->assertSame(['dropped' => 'mongo-php-adapter', 'ok' => 1.0], $this->getDatabase()->drop());
    }

    public function testDropCollection()
    {
        $document = ['foo' => 'bar'];
        $this->getCollection()->insert($document);
        $expected = [
            'ns' => (string) $this->getCollection(),
            'nIndexesWas' => 1,
            'ok' => 1.0
        ];
        $this->assertEquals($expected, $this->getDatabase()->dropCollection('test'));
    }

    public function testRepair()
    {
        $this->skipTestIf(version_compare($this->getServerVersion(), '4.2.0', '>='), 'The "repairDatabase" has been removed in MongoDB 4.2.0');

        $this->assertSame(['ok' => 1.0], $this->getDatabase()->repair());
    }
}
