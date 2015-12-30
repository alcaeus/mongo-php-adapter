<?php

namespace Alcaeus\MongoDbAdapter\Tests;

/**
 * @author alcaeus <alcaeus@alcaeus.org>
 */
class MongoCursorTest extends TestCase
{
    public function testCursorConvertsTypes()
    {
        $this->prepareData();

        $collection = $this->getCollection();
        $cursor = $collection->find(['foo' => 'bar']);
        $this->assertCount(2, $cursor);

        $iterated = 0;
        foreach ($cursor as $item) {
            $iterated++;
            $this->assertInstanceOf('MongoId', $item['_id']);
            $this->assertSame('bar', $item['foo']);
        }

        $this->assertSame(2, $iterated);
    }

    public function testCount()
    {
        $this->prepareData();

        $collection = $this->getCollection();
        $cursor = $collection->find(['foo' => 'bar'])->limit(1);

        $this->assertSame(2, $cursor->count());
        $this->assertSame(1, $cursor->count(true));
    }

    /**
     * @return \MongoCollection
     */
    protected function getCollection($name = 'test')
    {
        $client = new \MongoClient();

        return $client->selectCollection('mongo-php-adapter', $name);
    }

    /**
     * @return \MongoCollection
     */
    protected function prepareData()
    {
        $collection = $this->getCollection();

        $collection->insert(['foo' => 'bar']);
        $collection->insert(['foo' => 'bar']);
        $collection->insert(['foo' => 'foo']);
        return $collection;
    }
}
