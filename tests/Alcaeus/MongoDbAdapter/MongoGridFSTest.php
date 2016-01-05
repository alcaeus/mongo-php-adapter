<?php

namespace Alcaeus\MongoDbAdapter\Tests;

class MongoGridFSTest extends TestCase
{
    public function testChunkProperty()
    {
        $collection = $this->getGridFS();
        $this->assertInstanceOf('MongoCollection', $collection->chunks);
        $this->assertSame('mongo-php-adapter.testfs.chunks', (string) $collection->chunks);
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

        $collection->insert(['foo' => 'bar']);
        $collection->chunks->insert(['foo' => 'bar']);

        $collection->drop();

        $newCollection = $this->getCheckDatabase()->selectCollection('testfs.files');
        $newChunksCollection = $this->getCheckDatabase()->selectCollection('testfs.chunks');
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
                'chunkSize' => 2,
            ]
        );

        $newCollection = $this->getCheckDatabase()->selectCollection('testfs.files');
        $newChunksCollection = $this->getCheckDatabase()->selectCollection('testfs.chunks');
        $this->assertSame(1, $newCollection->count());
        $this->assertSame(2, $newChunksCollection->count());

        $record = $newCollection->findOne();
        $this->assertNotNull($record);
        $this->assertAttributeInstanceOf('MongoDB\BSON\ObjectID', '_id', $record);
        $this->assertSame((string) $id, (string) $record->_id);
        $this->assertObjectHasAttribute('foo', $record);
        $this->assertAttributeSame('bar', 'foo', $record);
        $this->assertObjectHasAttribute('length', $record);
        $this->assertAttributeSame(4, 'length', $record);
        $this->assertObjectHasAttribute('chunkSize', $record);
        $this->assertAttributeSame(2, 'chunkSize', $record);
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
        $this->assertSame('ab', (string) $firstChunk->data->getData());

        $secondChunck = $chunks[1];
        $this->assertNotNull($secondChunck);
        $this->assertAttributeInstanceOf('MongoDB\BSON\ObjectID', 'files_id', $secondChunck);
        $this->assertSame((string) $id, (string) $secondChunck->files_id);
        $this->assertAttributeSame(1, 'n', $secondChunck);
        $this->assertAttributeInstanceOf('MongoDB\BSON\Binary', 'data', $secondChunck);
        $this->assertSame('cd', (string) $secondChunck->data->getData());
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

        $newCollection = $this->getCheckDatabase()->selectCollection('testfs.files');

        $indexes = iterator_to_array($newCollection->listIndexes());
        $this->assertCount(2, $indexes);
        $index = $indexes[1];
        $this->assertSame(['filename' => 1, 'uploadDate' => 1], $index->getKey());

        $newChunksCollection = $this->getCheckDatabase()->selectCollection('testfs.chunks');
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

        $newCollection = $this->getCheckDatabase()->selectCollection('testfs.files');
        $newChunksCollection = $this->getCheckDatabase()->selectCollection('testfs.chunks');
        $this->assertSame(0, $newCollection->count());
        $this->assertSame(0, $newChunksCollection->count());
    }

    public function testRemove()
    {
        $collection = $this->getGridFS();
        $this->prepareFile('data', ['foo' => 'bar']);
        $this->prepareFile('data', ['foo' => 'bar']);

        $collection->remove(['foo' => 'bar']);

        $newCollection = $this->getCheckDatabase()->selectCollection('testfs.files');
        $newChunksCollection = $this->getCheckDatabase()->selectCollection('testfs.chunks');
        $this->assertSame(0, $newCollection->count());
        $this->assertSame(0, $newChunksCollection->count());
    }

    public function testStoreFile()
    {
        $collection = $this->getGridFS();

        $id = $collection->storeFile(__FILE__, ['chunkSize' => 100, 'foo' => 'bar']);


        $newCollection = $this->getCheckDatabase()->selectCollection('testfs.files');
        $newChunksCollection = $this->getCheckDatabase()->selectCollection('testfs.chunks');
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
        $this->assertObjectHasAttribute('chunkSize', $record);
        $this->assertAttributeSame(100, 'chunkSize', $record);
        $this->assertObjectHasAttribute('md5', $record);
        $this->assertAttributeSame($md5, 'md5', $record);
        $this->assertObjectHasAttribute('filename', $record);
        $this->assertAttributeSame($filename, 'filename', $record);

        $numberOfChunks = (int)ceil($size / 100);
        $this->assertSame($numberOfChunks, $newChunksCollection->count());
        $expectedContent = substr(file_get_contents(__FILE__), 0, 100);

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
            ['chunkSize' => 100, 'foo' => 'bar', 'filename' => 'test.php']
        );


        $newCollection = $this->getCheckDatabase()->selectCollection('testfs.files');
        $newChunksCollection = $this->getCheckDatabase()->selectCollection('testfs.chunks');
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
        $this->assertObjectHasAttribute('chunkSize', $record);
        $this->assertAttributeSame(100, 'chunkSize', $record);
        $this->assertObjectHasAttribute('md5', $record);
        $this->assertAttributeSame($md5, 'md5', $record);
        $this->assertObjectHasAttribute('filename', $record);
        $this->assertAttributeSame('test.php', 'filename', $record);

        $numberOfChunks = (int)ceil($size / 100);
        $this->assertSame($numberOfChunks, $newChunksCollection->count());
        $expectedContent = substr(file_get_contents(__FILE__), 0, 100);

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
        $collection = $this->getGridFS();

        $_FILES['foo'] = [
            'name' => 'test.php',
            'error' => UPLOAD_ERR_OK,
            'tmp_name' => __FILE__,
        ];

        $id = $collection->storeUpload(
            'foo',
            ['chunkSize' => 100, 'foo' => 'bar']
        );


        $newCollection = $this->getCheckDatabase()->selectCollection('testfs.files');
        $newChunksCollection = $this->getCheckDatabase()->selectCollection('testfs.chunks');
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
        $this->assertObjectHasAttribute('chunkSize', $record);
        $this->assertAttributeSame(100, 'chunkSize', $record);
        $this->assertObjectHasAttribute('md5', $record);
        $this->assertAttributeSame($md5, 'md5', $record);
        $this->assertObjectHasAttribute('filename', $record);
        $this->assertAttributeSame('test.php', 'filename', $record);

        $numberOfChunks = (int)ceil($size / 100);
        $this->assertSame($numberOfChunks, $newChunksCollection->count());
    }

    public function testFindOneReturnsFile()
    {
        $collection = $this->getGridFS();
        $this->prepareFile();

        $result = $collection->findOne();

        $this->assertInstanceOf('MongoGridFSFile', $result);
    }

    public function testMagicGetter()
    {
        $collection = $this->getGridFS();
        $id = (string) $this->prepareFile();

        $result = $collection->$id;

        $this->assertInstanceOf('MongoGridFSFile', $result);
    }

    public function testPut()
    {
        $collection = $this->getGridFS();

        $id = $collection->put(__FILE__, ['chunkSize' => 100, 'foo' => 'bar']);


        $newCollection = $this->getCheckDatabase()->selectCollection('testfs.files');
        $newChunksCollection = $this->getCheckDatabase()->selectCollection('testfs.chunks');
        $this->assertSame(1, $newCollection->count());

        $size = filesize(__FILE__);
        $numberOfChunks = (int)ceil($size / 100);
        $this->assertSame($numberOfChunks, $newChunksCollection->count());
    }

    /**
     * @var \MongoID
     */
    protected function prepareFile($data = 'abcd', $extra = [])
    {
        $collection = $this->getGridFS();

        // to make sure we have multiple chunks
        $extra += ['chunkSize' => 2];

        return $collection->storeBytes($data, $extra);
    }

    /**
     * @param string $name
     * @param \MongoDB|null $database
     * @return \MongoGridFS
     */
    protected function getGridFS($name = 'testfs', \MongoDB $database = null)
    {
        if ($database === null) {
            $database = $this->getDatabase();
        }

        return new \MongoGridFS($database, $name);
    }

    /**
     * @return \MongoCollection
     */
    protected function prepareData()
    {
        $collection = $this->getGridFS();

        $collection->insert(['foo' => 'bar']);
        $collection->insert(['foo' => 'bar']);
        $collection->insert(['foo' => 'foo']);
        return $collection;
    }
}
