<?php

namespace Alcaeus\MongoDbAdapter\Tests;

class MongoGridFSCursorTest extends TestCase
{
    public function testCursorItems()
    {
        $gridfs = $this->getGridFS();
        $gridfs->storeBytes('foo', ['filename' => 'foo.txt']);
        $gridfs->storeBytes('bar', ['filename' => 'bar.txt']);

        $cursor = $gridfs->find(['filename' => 'foo.txt']);
        $this->assertCount(1, $cursor);
        foreach ($cursor as $key => $value) {
            $this->assertSame('foo.txt', $key);
            $this->assertInstanceOf('MongoGridFSFile', $value);
            $this->assertSame('foo', $value->getBytes());

            $this->assertArraySubset([
                'filename' => 'foo.txt',
                'chunkSize' => \MongoGridFS::DEFAULT_CHUNK_SIZE,
                'length' => 3,
                'md5' => 'acbd18db4cc2f85cedef654fccc4a4d8'
            ], $value->file);
        }
    }
}
