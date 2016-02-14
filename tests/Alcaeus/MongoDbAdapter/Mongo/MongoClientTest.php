<?php

namespace Alcaeus\MongoDbAdapter\Tests\Mongo;

use Alcaeus\MongoDbAdapter\Tests\TestCase;

/**
 * @author alcaeus <alcaeus@alcaeus.org>
 */
class MongoClientTest extends TestCase
{
    public function testSerialize()
    {
        $this->assertInternalType('string', serialize($this->getClient()));
    }

    public function testGetDb()
    {
        $client = $this->getClient();
        $db = $client->selectDB('mongo-php-adapter');
        $this->assertInstanceOf('\MongoDB', $db);
        $this->assertSame('mongo-php-adapter', (string) $db);
    }

    public function testSelectDBWithEmptyName()
    {
        $this->setExpectedException('Exception', 'Database name cannot be empty');

        $this->getClient()->selectDB('');
    }

    public function testSelectDBWithInvalidName()
    {
        $this->setExpectedException('Exception', 'Database name contains invalid characters');

        $this->getClient()->selectDB('/');
    }

    public function testGetDbProperty()
    {
        $client = $this->getClient();
        $db = $client->{'mongo-php-adapter'};
        $this->assertInstanceOf('\MongoDB', $db);
        $this->assertSame('mongo-php-adapter', (string) $db);
    }

    public function testGetCollection()
    {
        $client = $this->getClient();
        $collection = $client->selectCollection('mongo-php-adapter', 'test');
        $this->assertInstanceOf('MongoCollection', $collection);
        $this->assertSame('mongo-php-adapter.test', (string) $collection);
    }

    public function testGetHosts()
    {
        $client = $this->getClient();
        $hosts = $client->getHosts();
        $this->assertArraySubset(
            [
                'localhost:27017;-;.;' . getmypid() => [
                    'host' => 'localhost',
                    'port' => 27017,
                    'health' => 1,
                    'state' => 0,
                ],
            ],
            $hosts
        );
    }

    public function testReadPreference()
    {
        $client = $this->getClient();
        $this->assertSame(['type' => \MongoClient::RP_PRIMARY], $client->getReadPreference());

        $this->assertTrue($client->setReadPreference(\MongoClient::RP_SECONDARY, [['a' => 'b']]));
        $this->assertSame(['type' => \MongoClient::RP_SECONDARY, 'tagsets' => [['a' => 'b']]], $client->getReadPreference());
    }

    public function testWriteConcern()
    {
        $client = $this->getClient();

        $this->assertTrue($client->setWriteConcern('majority', 100));
        $this->assertSame(['w' => 'majority', 'wtimeout' => 100], $client->getWriteConcern());
    }

    public function testListDBs()
    {
        $document = ['foo' => 'bar'];
        $this->getCollection()->insert($document);
        $databases = $this->getClient()->listDBs();

        $this->assertSame(1.0, $databases['ok']);
        $this->assertArrayHasKey('totalSize', $databases);
        $this->assertArrayHasKey('databases', $databases);

        foreach ($databases['databases'] as $database) {
            $this->assertArrayHasKey('name', $database);
            $this->assertArrayHasKey('empty', $database);
            $this->assertArrayHasKey('sizeOnDisk', $database);

            if ($database['name'] == 'mongo-php-adapter') {
                $this->assertFalse($database['empty']);
                return;
            }
        }

        $this->fail('Could not find mongo-php-adapter database in list');
    }

    public function testNoPrefixUri()
    {
        $client = $this->getClient(null, 'localhost');
        $this->assertNotNull($client);
    }
}
