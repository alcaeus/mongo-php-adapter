<?php

namespace Alcaeus\MongoDbAdapter\Tests\Mongo;

use Alcaeus\MongoDbAdapter\Tests\TestCase;

class MongoGridFSTest extends TestCase
{
    public function testSerialize()
    {
        $this->assertInternalType('string', serialize($this->getGridFS()));
    }

    public function testChunkProperty()
    {
        $collection = $this->getGridFS();
        $this->assertInstanceOf('MongoCollection', $collection->chunks);
        $this->assertSame('mongo-php-adapter.fs.chunks', (string) $collection->chunks);
    }

    public function testCustomCollectionName()
    {
        $collection = $this->getGridFS('foofs');
        $this->assertSame('mongo-php-adapter.foofs.files', (string) $collection);
        $this->assertInstanceOf('MongoCollection', $collection->chunks);
        $this->assertSame('mongo-php-adapter.foofs.chunks', (string) $collection->chunks);
    }

    public function testDrop()
    {
        $collection = $this->getGridFS();

        $document = ['foo' => 'bar'];
        $collection->insert($document);
        unset($document['_id']);
        $collection->chunks->insert($document);

        $collection->drop();

        $newCollection = $this->getCheckDatabase()->selectCollection('fs.files');
        $newChunksCollection = $this->getCheckDatabase()->selectCollection('fs.chunks');
        $this->assertSame(0, $newCollection->count());
        $this->assertSame(0, $newChunksCollection->count());
    }

    public function testFindReturnsGridFSCursor()
    {
        $this->prepareData();
        $collection = $this->getGridFS();

        $this->assertInstanceOf('MongoGridFSCursor', $collection->find());
    }

    public function testStoringData()
    {
        $collection = $this->getGridFS();

        $id = $collection->storeBytes(
            'abcd',
            [
                'foo' => 'bar',
            ]
        );

        $newCollection = $this->getCheckDatabase()->selectCollection('fs.files');
        $newChunksCollection = $this->getCheckDatabase()->selectCollection('fs.chunks');
        $this->assertSame(1, $newCollection->count());
        $this->assertSame(1, $newChunksCollection->count());

        $record = $newCollection->findOne();
        $this->assertNotNull($record);
        $this->assertAttributeInstanceOf('MongoDB\BSON\ObjectID', '_id', $record);
        $this->assertSame((string) $id, (string) $record->_id);
        $this->assertObjectHasAttribute('foo', $record);
        $this->assertAttributeSame('bar', 'foo', $record);
        $this->assertObjectHasAttribute('length', $record);
        $this->assertAttributeSame(4, 'length', $record);
        $this->assertObjectHasAttribute('md5', $record);
        $this->assertAttributeSame('e2fc714c4727ee9395f324cd2e7f331f', 'md5', $record);

        $chunksCursor = $newChunksCollection->find([], ['sort' => ['n' => 1]]);
        $chunks = iterator_to_array($chunksCursor);
        $firstChunk = $chunks[0];
        $this->assertNotNull($firstChunk);
        $this->assertAttributeInstanceOf('MongoDB\BSON\ObjectID', 'files_id', $firstChunk);
        $this->assertSame((string) $id, (string) $firstChunk->files_id);
        $this->assertAttributeSame(0, 'n', $firstChunk);
        $this->assertAttributeInstanceOf('MongoDB\BSON\Binary', 'data', $firstChunk);
        $this->assertSame('abcd', (string) $firstChunk->data->getData());
    }

    public function testIndexesCreation()
    {
        $collection = $this->getGridFS();

        $id = $collection->storeBytes(
            'abcd',
            [
                'foo' => 'bar',
                'chunkSize' => 2,
            ]
        );

        $newChunksCollection = $this->getCheckDatabase()->selectCollection('fs.chunks');
        $indexes = iterator_to_array($newChunksCollection->listIndexes());
        $this->assertCount(2, $indexes);
        $index = $indexes[1];
        $this->assertSame(['files_id' => 1, 'n' => 1], $index->getKey());
        $this->assertTrue($index->isUnique());
    }


    public function testDelete()
    {
        $collection = $this->getGridFS();
        $id = $this->prepareFile();

        $collection->delete($id);

        $newCollection = $this->getCheckDatabase()->selectCollection('fs.files');
        $newChunksCollection = $this->getCheckDatabase()->selectCollection('fs.chunks');
        $this->assertSame(0, $newCollection->count());
        $this->assertSame(0, $newChunksCollection->count());
    }

    public function testRemove()
    {
        $collection = $this->getGridFS();
        $this->prepareFile('data', ['foo' => 'bar']);
        $this->prepareFile('data', ['foo' => 'bar']);

        $collection->remove(['foo' => 'bar']);

        $newCollection = $this->getCheckDatabase()->selectCollection('fs.files');
        $newChunksCollection = $this->getCheckDatabase()->selectCollection('fs.chunks');
        $this->assertSame(0, $newCollection->count());
        $this->assertSame(0, $newChunksCollection->count());
    }

    public function testStoreFile()
    {
        $collection = $this->getGridFS();

        $id = $collection->storeFile(__FILE__, ['foo' => 'bar']);


        $newCollection = $this->getCheckDatabase()->selectCollection('fs.files');
        $newChunksCollection = $this->getCheckDatabase()->selectCollection('fs.chunks');
        $this->assertSame(1, $newCollection->count());

        $filename = __FILE__;
        $md5 = md5_file($filename);
        $size = filesize($filename);
        $record = $newCollection->findOne();
        $this->assertNotNull($record);
        $this->assertAttributeInstanceOf('MongoDB\BSON\ObjectID', '_id', $record);
        $this->assertSame((string) $id, (string) $record->_id);
        $this->assertObjectHasAttribute('foo', $record);
        $this->assertAttributeSame('bar', 'foo', $record);
        $this->assertObjectHasAttribute('length', $record);
        $this->assertAttributeSame($size, 'length', $record);
        $this->assertObjectHasAttribute('chunkSize', $record);
        $this->assertObjectHasAttribute('md5', $record);
        $this->assertAttributeSame($md5, 'md5', $record);
        $this->assertObjectHasAttribute('filename', $record);
        $this->assertAttributeSame($filename, 'filename', $record);

        $this->assertSame(1, $newChunksCollection->count());
        $expectedContent = file_get_contents(__FILE__);

        $firstChunk = $newChunksCollection->findOne([], ['sort' => ['n' => 1]]);
        $this->assertNotNull($firstChunk);
        $this->assertAttributeInstanceOf('MongoDB\BSON\ObjectID', 'files_id', $firstChunk);
        $this->assertSame((string) $id, (string) $firstChunk->files_id);
        $this->assertAttributeSame(0, 'n', $firstChunk);
        $this->assertAttributeInstanceOf('MongoDB\BSON\Binary', 'data', $firstChunk);
        $this->assertSame($expectedContent, (string) $firstChunk->data->getData());
    }

    public function testStoreFileResource()
    {
        $collection = $this->getGridFS();

        $id = $collection->storeFile(
            fopen(__FILE__, 'r'),
            ['foo' => 'bar', 'filename' => 'test.php']
        );


        $newCollection = $this->getCheckDatabase()->selectCollection('fs.files');
        $newChunksCollection = $this->getCheckDatabase()->selectCollection('fs.chunks');
        $this->assertSame(1, $newCollection->count());

        $md5 = md5_file(__FILE__);
        $size = filesize(__FILE__);
        $filename = basename(__FILE__);
        $record = $newCollection->findOne();
        $this->assertNotNull($record);
        $this->assertAttributeInstanceOf('MongoDB\BSON\ObjectID', '_id', $record);
        $this->assertSame((string) $id, (string) $record->_id);
        $this->assertObjectHasAttribute('foo', $record);
        $this->assertAttributeSame('bar', 'foo', $record);
        $this->assertObjectHasAttribute('length', $record);
        $this->assertAttributeSame($size, 'length', $record);
        $this->assertObjectHasAttribute('md5', $record);
        $this->assertAttributeSame($md5, 'md5', $record);
        $this->assertObjectHasAttribute('filename', $record);
        $this->assertAttributeSame('test.php', 'filename', $record);

        $this->assertSame(1, $newChunksCollection->count());
        $expectedContent = file_get_contents(__FILE__);

        $firstChunk = $newChunksCollection->findOne([], ['sort' => ['n' => 1]]);
        $this->assertNotNull($firstChunk);
        $this->assertAttributeInstanceOf('MongoDB\BSON\ObjectID', 'files_id', $firstChunk);
        $this->assertSame((string) $id, (string) $firstChunk->files_id);
        $this->assertAttributeSame(0, 'n', $firstChunk);
        $this->assertAttributeInstanceOf('MongoDB\BSON\Binary', 'data', $firstChunk);
        $this->assertSame($expectedContent, (string) $firstChunk->data->getData());
    }

    public function testStoreUpload()
    {
        $this->skipTestIf(extension_loaded('mongo'));
        $collection = $this->getGridFS();

        $_FILES['foo'] = [
            'name' => 'test.php',
            'error' => UPLOAD_ERR_OK,
            'tmp_name' => __FILE__,
        ];

        $id = $collection->storeUpload(
            'foo',
            ['foo' => 'bar']
        );


        $newCollection = $this->getCheckDatabase()->selectCollection('fs.files');
        $newChunksCollection = $this->getCheckDatabase()->selectCollection('fs.chunks');
        $this->assertSame(1, $newCollection->count());

        $md5 = md5_file(__FILE__);
        $size = filesize(__FILE__);
        $record = $newCollection->findOne();
        $this->assertNotNull($record);
        $this->assertAttributeInstanceOf('MongoDB\BSON\ObjectID', '_id', $record);
        $this->assertSame((string) $id, (string) $record->_id);
        $this->assertObjectHasAttribute('foo', $record);
        $this->assertAttributeSame('bar', 'foo', $record);
        $this->assertObjectHasAttribute('length', $record);
        $this->assertAttributeSame($size, 'length', $record);
        $this->assertObjectHasAttribute('chunkSize', $record);
        $this->assertObjectHasAttribute('md5', $record);
        $this->assertAttributeSame($md5, 'md5', $record);
        $this->assertObjectHasAttribute('filename', $record);
        $this->assertAttributeSame('test.php', 'filename', $record);

        $this->assertSame(1, $newChunksCollection->count());
    }

    public function testFindOneReturnsFile()
    {
        $collection = $this->getGridFS();
        $this->prepareFile();

        $result = $collection->findOne();

        $this->assertInstanceOf('MongoGridFSFile', $result);
    }

    public function testFindOneWithLegacyProjectionReturnsFile()
    {
        $collection = $this->getGridFS();
        $this->prepareFile('abcd', ['date' => new \MongoDate()]);

        $result = $collection->findOne([], ['date']);

        $this->assertInstanceOf('MongoGridFSFile', $result);
        $this->assertCount(2, $result->file);
        $this->assertArrayHasKey('date', $result->file);
    }

    public function testFindOneWithFilenameReturnsFile()
    {
        $collection = $this->getGridFS();
        $this->prepareFile('abcd', ['filename' => 'abcd']);
        $this->prepareFile('test', ['filename' => 'test']);
        $this->prepareFile('zyxv', ['filename' => 'zyxv']);

        $result = $collection->findOne('test');

        $this->assertInstanceOf('MongoGridFSFile', $result);
        $this->assertSame('test', $result->getBytes());
    }

    public function testFindOneNotFoundReturnsNull()
    {
        $collection = $this->getGridFS();

        $result = $collection->findOne();

        $this->assertNull($result);
    }

    public function testPut()
    {
        $collection = $this->getGridFS();

        $id = $collection->put(__FILE__, ['foo' => 'bar']);

        $newCollection = $this->getCheckDatabase()->selectCollection('fs.files');
        $newChunksCollection = $this->getCheckDatabase()->selectCollection('fs.chunks');
        $this->assertSame(1, $newCollection->count());

        $this->assertSame(1, $newChunksCollection->count());
    }

    /**
     * @return \MongoID
     */
    protected function prepareFile($data = 'abcd', $extra = [])
    {
        $collection = $this->getGridFS();

        // to make sure we have multiple chunks
        $extra += ['chunkSize' => 2];

        return $collection->storeBytes($data, $extra);
    }
}
