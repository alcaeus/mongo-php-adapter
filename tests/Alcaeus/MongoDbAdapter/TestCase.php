<?php

namespace Alcaeus\MongoDbAdapter\Tests;

use Alcaeus\MongoDbAdapter\Tests\Constraint\Matches;
use MongoDB\Client;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Symfony\Bridge\PhpUnit\SetUpTearDownTrait;

abstract class TestCase extends BaseTestCase
{
    use SetUpTearDownTrait;

    const INDEX_VERSION_1 = 1;
    const INDEX_VERSION_2 = 2;

    private function doTearDown()
    {
        $this->getCheckDatabase()->drop();

        parent::tearDown();
    }

    public function assertMatches($expected, $value, $message = '')
    {
        $constraint = new Matches($expected, true, true, true);
        $this->assertThat($value, $constraint, $message);
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
            throw new \RuntimeException("Failpoint failed");
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
    protected function skipTestUnless($condition, $message = null)
    {
        $this->skipTestIf(! $condition, $message);
    }

    /**
     * @param bool $condition
     * @param string|null $message
     */
    protected function skipTestIf($condition, $message = null)
    {
        if ($condition) {
            $this->markTestSkipped($message !== null ? $message : 'Test only applies when running against mongo-php-adapter');
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
     * @return string
     */
    protected function getFeatureCompatibilityVersion()
    {
        $featureCompatibilityVersion = $this->getClient()->selectDB('admin')->command(['getParameter' => true, 'featureCompatibilityVersion' => true]);
        if (! isset($featureCompatibilityVersion['featureCompatibilityVersion'])) {
            return '3.2';
        }

        return isset($featureCompatibilityVersion['featureCompatibilityVersion']['version']) ? $featureCompatibilityVersion['featureCompatibilityVersion']['version'] : $featureCompatibilityVersion['featureCompatibilityVersion'];
    }

    /**
     * Indexes created in MongoDB 3.4 default to v: 2.
     * @return int
     * @see https://docs.mongodb.com/manual/release-notes/3.4-compatibility/#backwards-incompatible-features
     */
    protected function getDefaultIndexVersion()
    {
        if (version_compare($this->getServerVersion(), '3.4.0', '<')) {
            self::INDEX_VERSION_1;
        }

        // Check featureCompatibilityFlag
        $compatibilityVersion = $this->getFeatureCompatibilityVersion();
        return version_compare($compatibilityVersion, '3.4', '>=') ? self::INDEX_VERSION_2 : self::INDEX_VERSION_1;
    }
}
