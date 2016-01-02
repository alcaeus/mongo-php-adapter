<?php

namespace Alcaeus\MongoDbAdapter\Tests;

/**
 * @author alcaeus <alcaeus@alcaeus.org>
 */
class MongoClientTest extends TestCase
{
    public function testConnectAndDisconnect()
    {
        $client = $this->getClient();
        $this->assertTrue($client->connected);

        $client->close();
        $this->assertFalse($client->connected);
    }

    public function testClientWithoutAutomaticConnect()
    {
        $client = $this->getClient([]);
        $this->assertFalse($client->connected);
    }

    public function testGetDb()
    {
        $client = $this->getClient();
        $db = $client->selectDB('mongo-php-adapter');
        $this->assertInstanceOf('\MongoDB', $db);
        $this->assertSame('mongo-php-adapter', (string) $db);
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

    public function testReadPreferenceAndWriteConcernArePassedOnToCollection()
    {
        $client = $this->getClient();
        $client->setReadPreference(\MongoClient::RP_SECONDARY, ['foo']);
        $client->setWriteConcern(2, 500);
        $collection = $client->selectCollection('mongo-php-adapter', 'test');
        $this->assertInstanceOf('MongoCollection', $collection);
        $this->assertEquals(
            ['type' => \MongoClient::RP_SECONDARY, 'tagsets' => ['foo']],
            $collection->getReadPreference()
        );
        $this->assertEquals(
            ['w' => 2, 'wtimeout' => 500],
            $collection->getWriteConcern()
        );
    }

    public function testGetHosts()
    {
        $client = $this->getClient();
        $this->assertArraySubset(
            [
                'localhost:27017' => [
                    'host' => 'localhost',
                    'port' => 27017,
                    'health' => 1,
                    'state' => 1,
                ],
            ],
            $client->getHosts()
        );
    }

    public function testReadPreference()
    {
        $client = $this->getClient();

        $this->assertTrue($client->setReadPreference(\MongoClient::RP_SECONDARY, ['foo']));
        $this->assertEquals(
            ['type' => \MongoClient::RP_SECONDARY, 'tagsets' => ['foo']],
            $client->getReadPreference()
        );
    }

    public function testWriteConcern()
    {
        $client = $this->getClient();
        $this->assertSame(['w' => 1, 'wtimeout' => 0], $client->getWriteConcern());

        $this->assertTrue($client->setWriteConcern('majority', 100));
        $this->assertSame(['w' => 'majority', 'wtimeout' => 100], $client->getWriteConcern());
    }

    /**
     * @param array|null $options
     * @return \MongoClient
     */
    protected function getClient($options = null)
    {
        $args = ['mongodb://localhost'];
        if ($options !== null) {
            $args[] = $options;
        }

        $reflection = new \ReflectionClass('MongoClient');

        return $reflection->newInstanceArgs($args);
    }
}
