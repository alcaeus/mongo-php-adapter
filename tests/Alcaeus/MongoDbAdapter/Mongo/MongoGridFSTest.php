<?php

namespace Alcaeus\MongoDbAdapter\Tests\Mongo;

use Alcaeus\MongoDbAdapter\Tests\TestCase;

class MongoGridFSTest extends TestCase
{
    public function testSerialize()
    {
        $this->assertIsString(serialize($this->getGridFS()));
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
                'chunkSize' => 2,
            ]
        );

        $newCollection = $this->getCheckDatabase()->selectCollection('fs.files');
        $newChunksCollection = $this->getCheckDatabase()->selectCollection('fs.chunks');
        $this->assertSame(1, $newCollection->count());
        $this->assertSame(2, $newChunksCollection->count());

        $record = $newCollection->findOne();
        $this->assertNotNull($record);
        $this->assertInstanceOf('MongoDB\BSON\ObjectID', $record->_id);
        $this->assertSame((string) $id, (string) $record->_id);
        $this->assertSame('bar', $record->foo);
        $this->assertSame(4, $record->length);
        $this->assertSame(2, $record->chunkSize);
        $this->assertSame('e2fc714c4727ee9395f324cd2e7f331f', $record->md5);

        $chunksCursor = $newChunksCollection->find([], ['sort' => ['n' => 1]]);
        $chunks = iterator_to_array($chunksCursor);
        $firstChunk = $chunks[0];
        $this->assertNotNull($firstChunk);
        $this->assertInstanceOf('MongoDB\BSON\ObjectID', $firstChunk->files_id);
        $this->assertSame((string) $id, (string) $firstChunk->files_id);
        $this->assertSame(0, $firstChunk->n);
        $this->assertInstanceOf('MongoDB\BSON\Binary', $firstChunk->data);
        $this->assertSame('ab', (string) $firstChunk->data->getData());

        $secondChunck = $chunks[1];
        $this->assertNotNull($secondChunck);
        $this->assertInstanceOf('MongoDB\BSON\ObjectID', $secondChunck->files_id);
        $this->assertSame((string) $id, (string) $secondChunck->files_id);
        $this->assertSame(1, $secondChunck->n);
        $this->assertInstanceOf('MongoDB\BSON\Binary', $secondChunck->data);
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

        $id = $collection->storeFile(__FILE__, ['chunkSize' => 100, 'foo' => 'bar']);


        $newCollection = $this->getCheckDatabase()->selectCollection('fs.files');
        $newChunksCollection = $this->getCheckDatabase()->selectCollection('fs.chunks');
        $this->assertSame(1, $newCollection->count());

        $filename = __FILE__;
        $md5 = md5_file($filename);
        $size = filesize($filename);
        $record = $newCollection->findOne();
        $this->assertNotNull($record);
        $this->assertInstanceOf('MongoDB\BSON\ObjectID', $record->_id);
        $this->assertSame((string) $id, (string) $record->_id);
        $this->assertSame('bar', $record->foo);
        $this->assertSame($size, $record->length);
        $this->assertSame(100, $record->chunkSize);
        $this->assertSame($md5, $record->md5);
        $this->assertSame($filename, $record->filename);

        $numberOfChunks = (int) ceil($size / 100);
        $this->assertSame($numberOfChunks, $newChunksCollection->count());
        $expectedContent = substr(file_get_contents(__FILE__), 0, 100);

        $firstChunk = $newChunksCollection->findOne([], ['sort' => ['n' => 1]]);
        $this->assertNotNull($firstChunk);
        $this->assertInstanceOf('MongoDB\BSON\ObjectID', $firstChunk->files_id);
        $this->assertSame((string) $id, (string) $firstChunk->files_id);
        $this->assertSame(0, $firstChunk->n);
        $this->assertInstanceOf('MongoDB\BSON\Binary', $firstChunk->data);
        $this->assertSame($expectedContent, (string) $firstChunk->data->getData());
    }

    public function testStoreFileResource()
    {
        $collection = $this->getGridFS();

        $id = $collection->storeFile(
            fopen(__FILE__, 'r'),
            ['chunkSize' => 100, 'foo' => 'bar', 'filename' => 'test.php']
        );


        $newCollection = $this->getCheckDatabase()->selectCollection('fs.files');
        $newChunksCollection = $this->getCheckDatabase()->selectCollection('fs.chunks');
        $this->assertSame(1, $newCollection->count());

        $md5 = md5_file(__FILE__);
        $size = filesize(__FILE__);
        $filename = basename(__FILE__);
        $record = $newCollection->findOne();
        $this->assertNotNull($record);
        $this->assertInstanceOf('MongoDB\BSON\ObjectID', $record->_id);
        $this->assertSame((string) $id, (string) $record->_id);
        $this->assertSame('bar', $record->foo);
        $this->assertSame($size, $record->length);
        $this->assertSame(100, $record->chunkSize);
        $this->assertSame($md5, $record->md5);
        $this->assertSame('test.php', $record->filename);

        $numberOfChunks = (int) ceil($size / 100);
        $this->assertSame($numberOfChunks, $newChunksCollection->count());
        $expectedContent = substr(file_get_contents(__FILE__), 0, 100);

        $firstChunk = $newChunksCollection->findOne([], ['sort' => ['n' => 1]]);
        $this->assertNotNull($firstChunk);
        $this->assertInstanceOf('MongoDB\BSON\ObjectID', $firstChunk->files_id);
        $this->assertSame((string) $id, (string) $firstChunk->files_id);
        $this->assertSame(0, $firstChunk->n);
        $this->assertInstanceOf('MongoDB\BSON\Binary', $firstChunk->data);
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
            ['chunkSize' => 100, 'foo' => 'bar']
        );


        $newCollection = $this->getCheckDatabase()->selectCollection('fs.files');
        $newChunksCollection = $this->getCheckDatabase()->selectCollection('fs.chunks');
        $this->assertSame(1, $newCollection->count());

        $md5 = md5_file(__FILE__);
        $size = filesize(__FILE__);
        $record = $newCollection->findOne();
        $this->assertNotNull($record);
        $this->assertInstanceOf('MongoDB\BSON\ObjectID', $record->_id);
        $this->assertSame((string) $id, (string) $record->_id);
        $this->assertSame('bar', $record->foo);
        $this->assertSame($size, $record->length);
        $this->assertSame(100, $record->chunkSize);
        $this->assertSame($md5, $record->md5);
        $this->assertSame('test.php', $record->filename);

        $numberOfChunks = (int) ceil($size / 100);
        $this->assertSame($numberOfChunks, $newChunksCollection->count());
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

        $id = $collection->put(__FILE__, ['chunkSize' => 100, 'foo' => 'bar']);

        $newCollection = $this->getCheckDatabase()->selectCollection('fs.files');
        $newChunksCollection = $this->getCheckDatabase()->selectCollection('fs.chunks');
        $this->assertSame(1, $newCollection->count());

        $size = filesize(__FILE__);
        $numberOfChunks = (int) ceil($size / 100);
        $this->assertSame($numberOfChunks, $newChunksCollection->count());
    }

    public function testStoreByteExceptionWhileInsertingRecord()
    {
        $id = new \MongoID();

        $collection = $this->getGridFS();

        $document = ['_id' => $id];
        $collection->insert($document);

        $this->expectException(\MongoGridFSException::class);
        $this->expectExceptionMessageRegExp('/Could not store file:.* E11000 duplicate key error .* mongo-php-adapter\.fs\.files/');
        $this->expectExceptionCode(11000);

        $collection->storeBytes('foo', ['_id' => $id]);
    }

    public function testStoreByteExceptionWhileInsertingChunks()
    {
        $collection = $this->getGridFS();
        $collection->chunks->createIndex(['n' => 1], ['unique' => true]);

        $document = ['n' => 0];
        $collection->chunks->insert($document);

        $this->expectException(\MongoGridFSException::class);
        $this->expectExceptionMessageRegExp('/Could not store file:.* E11000 duplicate key error .* mongo-php-adapter\.fs\.chunks/');
        $this->expectExceptionCode(11000);

        $collection->storeBytes('foo');
    }

    public function testStoreFileExceptionWhileInsertingRecord()
    {
        $id = new \MongoID();

        $collection = $this->getGridFS();
        $document = ['_id' => $id];
        $collection->insert($document);

        $this->expectException(\MongoGridFSException::class);
        $this->expectExceptionMessageRegExp('/Could not store file:.* E11000 duplicate key error .* mongo-php-adapter\.fs\.files/');
        $this->expectExceptionCode(11000);

        $collection->storeFile(__FILE__, ['_id' => $id]);
    }

    public function testStoreFileExceptionWhileInsertingChunks()
    {
        $collection = $this->getGridFS();
        $collection->chunks->createIndex(['n' => 1], ['unique' => true]);

        $document = ['n' => 0];
        $collection->chunks->insert($document);

        $this->expectException(\MongoGridFSException::class);
        $this->expectExceptionMessageRegExp('/Could not store file:.* E11000 duplicate key error .* mongo-php-adapter\.fs\.chunks/');
        $this->expectExceptionCode(11000);

        $collection->storeFile(__FILE__);
    }

    public function testStoreFileExceptionWhileUpdatingFileRecord()
    {
        $collection = $this->getGridFS();
        $collection->createIndex(['length' => 1], ['unique' => true]);

        $document = ['length' => filesize(__FILE__)];
        $collection->insert($document);

        $this->expectException(\MongoGridFSException::class);
        $this->expectExceptionMessageRegExp('/Could not store file:.* E11000 duplicate key error .* mongo-php-adapter\.fs\.files/');
        $this->expectExceptionCode(11000);

        $collection->storeFile(fopen(__FILE__, 'r'));
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
