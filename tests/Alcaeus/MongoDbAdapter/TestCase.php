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
}
