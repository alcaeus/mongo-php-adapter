<?php

namespace Alcaeus\MongoDbAdapter\Tests;

use MongoDB\Client;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function tearDown()
    {
        $this->getCheckDatabase()->drop();
    }

    /**
     * @return \MongoDB\Client
     */
    protected function getCheckClient()
    {
        return new Client('mongodb://localhost', ['connect' => true]);
    }

    /**
     * @return \MongoDB\Database
     */
    protected function getCheckDatabase()
    {
        $client = $this->getCheckClient();
        return $client->selectDatabase('mongo-php-adapter');
    }

    /**
     * @param array|null $options
     * @return \MongoClient
     */
    protected function getClient($options = null, $uri = 'mongodb://localhost')
    {
        $args = [$uri];
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

    /**
     * @param string $prefix
     * @param \MongoDB|null $database
     * @return \MongoGridFS
     */
    protected function getGridFS($prefix = 'fs', \MongoDB $database = null)
    {
        if ($database === null) {
            $database = $this->getDatabase();
        }

        return $database->getGridFS($prefix);
    }

    /**
     * @return \MongoCollection
     */
    protected function prepareData()
    {
        $collection = $this->getCollection();

        $document = ['foo' => 'bar'];
        $collection->insert($document);

        unset($document['_id']);
        $collection->insert($document);

        $document = ['foo' => 'foo'];
        $collection->insert($document);

        return $collection;
    }

    protected function configureFailPoint($failPoint, $mode, $data = [])
    {
        $this->checkFailPoint();

        $doc = array(
            "configureFailPoint" => $failPoint,
            "mode"               => $mode,
        );
        if ($data) {
            $doc["data"] = $data;
        }

        $adminDb = $this->getCheckClient()->selectDatabase('admin');
        $result = $adminDb->command($doc);
        $arr = current($result->toArray());
        if (empty($arr->ok)) {
            throw new RuntimeException("Failpoint failed");
        }

        return true;
    }

    protected function checkFailPoint()
    {
        $database = $this->getCheckClient()->selectDatabase('test');
        try {
            $database->command(['configureFailPoint' => 1]);
        } catch (\MongoDB\Driver\Exception\Exception $e) {
            /* command not found */
            if ($e->getCode() == 59) {
                $this->markTestSkipped(
                  'This test require the mongo daemon to be started with the test flag: --setParameter enableTestCommands=1'
                );
            }
        }
    }

    protected function failMaxTimeMS()
    {
        return $this->configureFailPoint("maxTimeAlwaysTimeOut", array("times" => 1));
    }

    /**
     * @param bool $condition
     */
    protected function skipTestUnless($condition)
    {
        $this->skipTestIf(! $condition);
    }

    /**
     * @param bool $condition
     */
    protected function skipTestIf($condition)
    {
        if ($condition) {
            $this->markTestSkipped('Test only applies when running against mongo-php-adapter');
        }
    }

    /**
     * @return string
     */
    protected function getServerVersion()
    {
        $serverInfo = $this->getDatabase()->command(['buildinfo' => true]);
        return $serverInfo['version'];
    }

    /**
     * Indexes created in MongoDB 3.4 default to v: 2.
     * @return int
     * @see https://docs.mongodb.com/manual/release-notes/3.4-compatibility/#backwards-incompatible-features
     */
    protected function getDefaultIndexVersion()
    {
        return version_compare($this->getServerVersion(), '3.4.0', '>=') ? 2 : 1;
    }
}
