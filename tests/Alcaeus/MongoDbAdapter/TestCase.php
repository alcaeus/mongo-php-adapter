<?php

namespace Alcaeus\MongoDbAdapter\Tests;

use MongoDB\Client;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    protected function tearDown()
    {
        $this->getCheckDatabase()->drop();
    }

    /**
     * @return \MongoDB\Database
     */
    protected function getCheckDatabase()
    {
        $client = new Client('mongodb://localhost', ['connect' => true]);
        return $client->selectDatabase('mongo-php-adapter');
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

    /**
     * @param \MongoClient|null $client
     * @return \MongoDB
     */
    protected function getDatabase(\MongoClient $client = null)
    {
        if ($client === null) {
            $client = $this->getClient();
        }

        return $client->selectDB('mongo-php-adapter');
    }

    /**
     * @param string $name
     * @param \MongoDB|null $database
     * @return \MongoCollection
     */
    protected function getCollection($name = 'test', \MongoDB $database = null)
    {
        if ($database === null) {
            $database = $this->getDatabase();
        }

        return $database->selectCollection($name);
    }
}
