<?php

namespace Alcaeus\MongoDbAdapter\Tests\Mongo;

use Alcaeus\MongoDbAdapter\Tests\TestCase;

/**
 * @author alcaeus <alcaeus@alcaeus.org>
 */
class MongoClientTest extends TestCase
{
    /**
     * @dataProvider provideConnectionUri
     */
    public function testConnectionUri($uri, $expected)
    {
        $this->skipTestIf(extension_loaded('mongo'));
        $this->assertSame($expected, (string)(new \MongoClient($uri, ['connect' => false])));
    }

    public function provideConnectionUri()
    {
        yield ['default', sprintf('mongodb://%s:%d', \MongoClient::DEFAULT_HOST, \MongoClient::DEFAULT_PORT)];
        yield ['localhost', 'mongodb://localhost'];
        yield ['mongodb://localhost', 'mongodb://localhost'];
    }

    public function testSerialize()
    {
        $this->assertIsString(serialize($this->getClient()));
    }

    public function testGetDb()
    {
        $client = $this->getClient();
        $db = $client->selectDB('mongo-php-adapter');
        $this->assertInstanceOf('\MongoDB', $db);
        $this->assertSame('mongo-php-adapter', (string)$db);
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
        $this->assertSame('mongo-php-adapter', (string)$db);
    }

    public function testGetCollection()
    {
        $client = $this->getClient();
        $collection = $client->selectCollection('mongo-php-adapter', 'test');
        $this->assertInstanceOf('MongoCollection', $collection);
        $this->assertSame('mongo-php-adapter.test', (string)$collection);
    }

    public function testGetHosts()
    {
        $client = $this->getClient();
        $hosts = $client->getHosts();
        $this->assertMatches(
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

    public function testGetHostsExceptionHandling()
    {
        $this->expectException(\MongoConnectionException::class);
        $this->expectErrorMessageMatches('/fake_host/');

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
            'readPreferenceTags' => [['a' => 'b']],
        ];

        $overriddenOptions = [
            'readPreference' => \MongoClient::RP_NEAREST,
            'readPreferenceTags' => [['c' => 'd']],
        ];

        $emptyTagSet = [
            'readPreference' => \MongoClient::RP_SECONDARY_PREFERRED,
            'readPreferenceTags' => [[]],
        ];

        $multipleTags = [
            'readPreference' => \MongoClient::RP_SECONDARY_PREFERRED,
            'readPreferenceTags' => [['a' => 'b', 'c' => 'd']],
        ];

        $multipleTagsAsString = [
            'readPreference' => \MongoClient::RP_SECONDARY_PREFERRED,
            'readPreferenceTags' => 'a:b,c:d',
        ];

        $multipleTagSets = [
            'readPreference' => \MongoClient::RP_SECONDARY_PREFERRED,
            'readPreferenceTags' => [['a' => 'b', 'c' =>'d'], ['e' => 'f'], []],
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
            'emptyTagSetInQuery' => [
                'options' => null,
                'uri' => 'mongodb://localhost/?' . self::makeOptionString($emptyTagSet),
                'expectedTagsets' => [[]],
            ],
            'emptyTagSetInOptions' => [
                'options' => $emptyTagSet,
                'uri' => 'mongodb://localhost',
                'expectedTagsets' => [[]],
            ],
            'multipleTagsArrayInOptions' => [
                'options' => $multipleTags,
                'uri' => 'mongodb://localhost',
                'expectedTagsets' => [['a' => 'b', 'c' => 'd']],
            ],
            'multipleTagsInQueryString' => [
                'options' => null,
                'uri' => 'mongodb://localhost/?' . self::makeOptionString($multipleTags),
                'expectedTagsets' => [['a' => 'b', 'c' => 'd']],
            ],
            'multipleTagsStringInOptions' => [
                'options' => $multipleTagsAsString,
                'uri' => 'mongodb://localhost',
                'expectedTagsets' => [['a' => 'b', 'c' => 'd']],
            ],
            'multipleTagSetsInOptions' => [
                'options' => $multipleTagSets,
                'uri' => 'mongodb://localhost',
                'expectedTagsets' => [['a' => 'b', 'c' => 'd'], ['e' => 'f'], []],
            ],
            'multipleTagSetsInQueryString' => [
                'options' => null,
                'uri' => 'mongodb://localhost/?' . self::makeOptionString($multipleTagSets),
                'expectedTagsets' => [['a' => 'b', 'c' => 'd'], ['e' => 'f'], []],
            ],
            'mergedTagSets' => [
                'options' => $multipleTagSets,
                'uri' => 'mongodb://localhost/?' . self::makeOptionString($multipleTagSets) . '&readPreferenceTags=g:h',
                'expectedTagsets' => [['a' => 'b', 'c' => 'd'], ['e' => 'f'], [], ['g' => 'h']],
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

        $client = $this->getClient([], 'mongodb://alcaeus:mySuperSecurePassword@localhost');
        $collection = $client->selectCollection('test', 'foo');

        $document = ['foo' => 'bar'];

        $collection->insert($document);
    }

    public function testConnectionUriOptionIntegerTypeCasting()
    {
        $client = new \MongoClient('mongodb://localhost/db?w=0&wtimeout=0', ['connect' => false]);

        $this->assertSame(['w' => 0, 'wtimeout' => 0], $client->getWriteConcern());
    }

    /**
     * @param array $options
     * @return string
     */
    private static function makeOptionString(array $options)
    {
        $query = '';

        foreach ($options as $key => $value) {
            if (is_array($value)) {
                if ($key === 'readPreferenceTags') {
                    foreach ($value as $tagSet) {

                        $tagString = implode(',', array_map(
                            function ($k, $v) {
                                return $k . ':' . $v;
                            },
                            array_keys($tagSet),
                            array_values($tagSet)
                        ));

                        $query .= $key . '=' . $tagString . '&';
                    }
                }
            } else {
                $query .= $key . '=' . $value . '&';
            }
        }

        return rtrim($query, '&');
    }
}
