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
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database name cannot be empty');

        $this->getClient()->selectDB('');
    }

    public function testSelectDBWithInvalidName()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database name contains invalid characters');

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

        $key = sprintf('%s:27017;-;.;%d', $this->getMongoHost(), getmypid());

        $this->assertArraySubset(
            [
                 $key => [
                    'host' => $this->getMongoHost(),
                    'port' => 27017,
                    'health' => 1,
                    'state' => 0,
                 ],
            ],
            $hosts
        );
    }

    public function testGetHostsExceptionHandling()
    {
        $this->expectException(\MongoConnectionException::class);
        $this->expectExceptionMessageRegExp('/fake_host/');

        $client = $this->getClient(null, 'mongodb://fake_host');
        $client->getHosts();
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

    /**
     * @dataProvider dataReadPreferenceOptionsAreInherited
     */
    public function testReadPreferenceOptionsAreInherited($options, $uri, $expectedTagsets)
    {
        $client = $this->getClient($options, $uri);
        $collection = $client->selectCollection('test', 'foo');

        $this->assertSame(
            [
                'type' => \MongoClient::RP_SECONDARY_PREFERRED,
                'tagsets' => $expectedTagsets
            ],
            $collection->getReadPreference()
        );
    }

    public static function dataReadPreferenceOptionsAreInherited()
    {
        $options = [
            'readPreference' => \MongoClient::RP_SECONDARY_PREFERRED,
            'readPreferenceTags' => 'a:b',
        ];

        $overriddenOptions = [
            'readPreference' => \MongoClient::RP_NEAREST,
            'readPreferenceTags' => 'c:d',
        ];

        $multipleTagsets = [
            'readPreference' => \MongoClient::RP_SECONDARY_PREFERRED,
            'readPreferenceTags' => 'a:b,c:d',
        ];

        return [
            'optionsArray' => [
                'options' => $options,
                'uri' => 'mongodb://localhost',
                'expectedTagsets' => [['a' => 'b']],
            ],
            'queryString' => [
                'options' => [],
                'uri' => 'mongodb://localhost/?' . self::makeOptionString($options),
                'expectedTagsets' => [['a' => 'b']],
            ],
            'multipleInQueryString' => [
                'options' => [],
                'uri' => 'mongodb://localhost/?' . self::makeOptionString($options) . '&readPreferenceTags=c:d',
                'expectedTagsets' => [['a' => 'b'], ['c' => 'd']],
            ],
            'overridden' => [
                'options' => $options,
                'uri' => 'mongodb://localhost/?' . self::makeOptionString($overriddenOptions),
                'expectedTagsets' => [['c' => 'd'], ['a' => 'b']],
            ],
            'multipleTagsetsOptions' => [
                'options' => $multipleTagsets,
                'uri' => 'mongodb://localhost',
                'expectedTagsets' => [['a' => 'b', 'c' => 'd']],
            ],
            'multipleTagsetsQueryString' => [
                'options' => null,
                'uri' => 'mongodb://localhost/?' . self::makeOptionString($multipleTagsets),
                'expectedTagsets' => [['a' => 'b', 'c' => 'd']],
            ],
        ];
    }

    /**
     * @dataProvider dataWriteConcernOptionsAreInherited
     */
    public function testWriteConcernOptionsAreInherited($options, $uri)
    {
        $client = $this->getClient($options, $uri);
        $collection = $client->selectCollection('test', 'foo');

        $this->assertSame(['w' => 'majority', 'wtimeout' => 666], $collection->getWriteConcern());
    }

    public static function dataWriteConcernOptionsAreInherited()
    {
        $options = [
            'w' => 'majority',
            'wTimeoutMs' => 666,
        ];

        $overriddenOptions = [
            'w' => '2',
            'wTimeoutMs' => 333,
        ];

        return [
            'optionsArray' => [
                'options' => $options,
                'uri' => 'mongodb://localhost',
            ],
            'queryString' => [
                'options' => [],
                'uri' => 'mongodb://localhost/?' . self::makeOptionString($options),
            ],
            'overridden' => [
                'options' => $options,
                'uri' => 'mongodb://localhost/?' . self::makeOptionString($overriddenOptions),
            ]
        ];
    }

    public function testConnectWithUsernameAndPassword()
    {
        $this->expectException(\MongoConnectionException::class);
        $this->expectExceptionMessage('Authentication failed');

        $client = $this->getClient(['username' => 'alcaeus', 'password' => 'mySuperSecurePassword']);
        $collection = $client->selectCollection('test', 'foo');

        $document = ['foo' => 'bar'];

        $collection->insert($document);
    }

    public function testConnectWithUsernameAndPasswordInConnectionUrl()
    {
        $this->expectException(\MongoConnectionException::class);
        $this->expectExceptionMessage('Authentication failed');

        $uri = 'mongodb://alcaeus:mySuperSecurePassword@' . $this->getMongoHost();
        $client = $this->getClient([], $uri);
        $collection = $client->selectCollection('test', 'foo');

        $document = ['foo' => 'bar'];

        $collection->insert($document);
    }

    /**
     * @param array $options
     * @return string
     */
    private static function makeOptionString(array $options)
    {
        return implode('&', array_map(
            function ($key, $value) {
                return $key . '=' . $value;
            },
            array_keys($options),
            array_values($options)
        ));
    }
}
