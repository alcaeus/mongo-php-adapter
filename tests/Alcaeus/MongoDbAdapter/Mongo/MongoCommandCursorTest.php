<?php

namespace Alcaeus\MongoDbAdapter\Tests\Mongo;

use MongoCursorInterface;
use MongoDB\Database;
use MongoDB\Driver\ReadPreference;
use Alcaeus\MongoDbAdapter\Tests\TestCase;

/**
 * @author alcaeus <alcaeus@alcaeus.org>
 */
class MongoCommandCursorTest extends TestCase
{
    public function testSerialize()
    {
        $this->prepareData();
        $cursor = $this->getCollection()->aggregateCursor([['$match' => ['foo' => 'bar']]]);
        $this->assertIsString(serialize($cursor));
    }

    public function testInfo()
    {
        $this->prepareData();
        $cursor = $this->getCollection()->aggregateCursor([['$match' => ['foo' => 'bar']]]);

        $expected = [
            'ns' => 'mongo-php-adapter.test',
            'limit' => 0,
            'batchSize' => 0,
            'skip' => 0,
            'flags' => 0,
            'query' => [
                'aggregate' => 'test',
                'pipeline' => [
                    [
                        '$match' => ['foo' => 'bar']
                    ]
                ],
                'cursor' => new \stdClass(),
            ],
            'fields' => null,
            'started_iterating' => false,
        ];
        $info = $cursor->info();
        $this->assertEquals($expected, $info);

        // Ensure cursor started iterating
        $array = iterator_to_array($cursor);

        $expected['started_iterating'] = true;
        $expected += [
            'id' => 0,
            'at' => 0,
            'numReturned' => 0,
            'server' => 'localhost:27017;-;.;' . getmypid(),
            'host' => 'localhost',
            'port' => 27017,
            'connection_type_desc' => 'STANDALONE',
        ];

        $this->assertMatches($expected, $cursor->info());

        $i = 0;
        foreach ($array as $key => $value) {
            $this->assertEquals($i, $key);
            $i++;
        }
    }

    /**
     * @dataProvider dataCommandAppliesCorrectReadPreference
     */
    public function testCommandAppliesCorrectReadPreference($command, $expectedReadPreference)
    {
        $this->skipTestIf(extension_loaded('mongo'));

        $checkReadPreference = function ($other) use ($expectedReadPreference) {
            if (!is_array($other)) {
                return false;
            }

            if (!array_key_exists('readPreference', $other)) {
                return false;
            }

            if (!$other['readPreference'] instanceof ReadPreference) {
                return false;
            }

            return $other['readPreference']->getMode() === $expectedReadPreference;
        };

        $databaseMock = $this->createMock(Database::class);
        $databaseMock
            ->expects($this->once())
            ->method('command')
            ->with($this->anything(), $this->callback($checkReadPreference))
            ->will($this->returnValue(new \ArrayIterator()));

        $cursor = new \MongoCommandCursor($this->getClient(), (string) $this->getDatabase(), $command);
        $reflection = new \ReflectionProperty($cursor, 'db');
        $reflection->setAccessible(true);
        $reflection->setValue($cursor, $databaseMock);
        $cursor->setReadPreference(\MongoClient::RP_SECONDARY);

        iterator_to_array($cursor);

        self::assertSame(\MongoClient::RP_SECONDARY, $cursor->getReadPreference()['type']);
    }

    public function dataCommandAppliesCorrectReadPreference()
    {
        return [
            'findAndUpdate' => [
                [
                    'findandmodify' => (string) $this->getCollection(),
                    'query' => [],
                    'update' => ['$inc' => ['field' => 1]],
                ],
                ReadPreference::RP_PRIMARY,
            ],
            'findAndRemove' => [
                [
                    'findandremove' => (string) $this->getCollection(),
                    'query' => [],
                ],
                ReadPreference::RP_PRIMARY,
            ],
            'mapReduceWithOut' => [
                [
                    'mapReduce' => (string) $this->getCollection(),
                    'out' => 'sample',
                ],
                ReadPreference::RP_PRIMARY,
            ],
            'mapReduceWithOutInline' => [
                [
                    'mapReduce' => (string) $this->getCollection(),
                    'out' => ['inline' => 1],
                ],
                ReadPreference::RP_SECONDARY,
            ],
            'count' => [
                [
                    'count' => (string) $this->getCollection(),
                ],
                ReadPreference::RP_SECONDARY,
            ],
            'group' => [
                [
                    'group' => (string) $this->getCollection(),
                ],
                ReadPreference::RP_SECONDARY,
            ],
            'dbStats' => [
                [
                    'dbStats' => (string) $this->getCollection(),
                ],
                ReadPreference::RP_SECONDARY,
            ],
            'geoNear' => [
                [
                    'geoNear' => (string) $this->getCollection(),
                ],
                ReadPreference::RP_SECONDARY,
            ],
            'geoWalk' => [
                [
                    'geoWalk' => (string) $this->getCollection(),
                ],
                ReadPreference::RP_SECONDARY,
            ],
            'distinct' => [
                [
                    'distinct' => (string) $this->getCollection(),
                ],
                ReadPreference::RP_SECONDARY,
            ],
            'aggregate' => [
                [
                    'aggregate' => (string) $this->getCollection(),
                ],
                ReadPreference::RP_SECONDARY,
            ],
            'collStats' => [
                [
                    'collStats' => (string) $this->getCollection(),
                ],
                ReadPreference::RP_SECONDARY,
            ],
            'geoSearch' => [
                [
                    'geoSearch' => (string) $this->getCollection(),
                ],
                ReadPreference::RP_SECONDARY,
            ],
            'parallelCollectionScan' => [
                [
                    'parallelCollectionScan' => (string) $this->getCollection(),
                ],
                ReadPreference::RP_SECONDARY,
            ],
        ];
    }

    public function testInterfaces()
    {
        $this->prepareData();
        $cursor = $this->getCollection()->aggregateCursor([['$match' => ['foo' => 'bar']]]);

        $this->assertInstanceOf(MongoCursorInterface::class, $cursor);
    }
}
