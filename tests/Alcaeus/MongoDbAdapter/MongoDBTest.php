<?php

namespace Alcaeus\MongoDbAdapter\Tests;

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

    /**
     * @return \MongoDB
     */
    protected function getDatabase()
    {
        $client = new \MongoClient();

        return $client->selectDB('mongo-php-adapter');
    }
}
