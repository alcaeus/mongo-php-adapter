<?php

namespace Alcaeus\MongoDbAdapter\Tests;
use MongoDB\Driver\ReadPreference;

/**
 * @author alcaeus <alcaeus@alcaeus.org>
 */
class MongoDBTest extends TestCase
{
    public function testGetCollection()
    {
        $db = $this->getDatabase();
        $collection = $db->selectCollection('test');
        $this->assertInstanceOf('MongoCollection', $collection);
        $this->assertSame('mongo-php-adapter.test', (string) $collection);
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
        $this->assertEquals(['ok' => 1], $db->command(['ping' => 1], [], $hash));
    }

    public function testCommandError()
    {
        $db = $this->getDatabase();
        $expected = [
            'ok' => 0,
            'errmsg' => 'listDatabases may only be run against the admin database.',
            'code' => 13,
        ];

        $this->assertEquals($expected, $db->command(['listDatabases' => 1], [], $hash));
    }

    public function testReadPreference()
    {
        $database = $this->getDatabase();
        $this->assertSame(['type' => \MongoClient::RP_PRIMARY], $database->getReadPreference());

        $this->assertTrue($database->setReadPreference(\MongoClient::RP_SECONDARY, ['a' => 'b']));
        $this->assertSame(['type' => \MongoClient::RP_SECONDARY, 'tagsets' => ['a' => 'b']], $database->getReadPreference());

        // Only way to check whether options are passed down is through debugInfo
        $readPreference = $database->getDb()->__debugInfo()['readPreference'];

        $this->assertSame(ReadPreference::RP_SECONDARY, $readPreference->getMode());
        $this->assertSame(['a' => 'b'], $readPreference->getTagSets());
    }

    public function testReadPreferenceIsInherited()
    {
        $client = $this->getClient();
        $client->setReadPreference(\MongoClient::RP_SECONDARY, ['a' => 'b']);

        $database = $client->selectDB('test');
        $this->assertSame(['type' => \MongoClient::RP_SECONDARY, 'tagsets' => ['a' => 'b']], $database->getReadPreference());
    }

    public function testWriteConcern()
    {
        $database = $this->getDatabase();
        $this->assertSame(['w' => 1, 'wtimeout' => 0], $database->getWriteConcern());
        $this->assertSame(1, $database->w);
        $this->assertSame(0, $database->wtimeout);

        $this->assertTrue($database->setWriteConcern('majority', 100));
        $this->assertSame(['w' => 'majority', 'wtimeout' => 100], $database->getWriteConcern());

        $database->w = 2;
        $this->assertSame(['w' => 2, 'wtimeout' => 100], $database->getWriteConcern());

        $database->wtimeout = -1;
        $this->assertSame(['w' => 2, 'wtimeout' => 0], $database->getWriteConcern());

        // Only way to check whether options are passed down is through debugInfo
        $writeConcern = $database->getDb()->__debugInfo()['writeConcern'];

        $this->assertSame(2, $writeConcern->getW());
        $this->assertSame(0, $writeConcern->getWtimeout());
    }

    public function testWriteConcernIsInherited()
    {
        $client = $this->getClient();
        $client->setWriteConcern('majority', 100);

        $database = $client->selectDB('test');
        $this->assertSame(['w' => 'majority', 'wtimeout' => 100], $database->getWriteConcern());
    }

    public function testSlave()
    {
        $database = $this->getDatabase();
        $this->assertFalse($database->getSlaveOkay());
        $this->assertFalse($database->setSlaveOkay());
        $this->assertTrue($database->getSlaveOkay());
        $this->assertTrue($database->setSlaveOkay(false));
        $this->assertFalse($database->getSlaveOkay());
    }

    /**
     * @return \MongoDB
     */
    protected function getDatabase()
    {
        $client = $this->getClient();

        return $client->selectDB('mongo-php-adapter');
    }

    /**
     * @return \MongoClient
     */
    protected function getClient()
    {
        return new \MongoClient();
    }
}
