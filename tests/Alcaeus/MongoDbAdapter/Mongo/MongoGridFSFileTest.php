<?php

namespace Alcaeus\MongoDbAdapter\Tests\Mongo;

use Alcaeus\MongoDbAdapter\Tests\TestCase;

class MongoGridFSFileTest extends TestCase
{
    public function testSerialize()
    {
        $this->prepareFile('abcd', ['filename' => 'foo']);
        $file = $this->getGridFS()->findOne(['filename' => 'foo']);
        $this->assertInstanceOf(\MongoGridFSFile::class, $file);

        $this->assertInternalType('string', serialize($file));
    }

    public function testFileProperty()
    {
        $file = $this->getFile();
        $this->assertArrayHasKey('_id', $file->file);
        $this->assertArraySubset(
            [
                'length' => 666,
                'filename' => 'file',
                'md5' => 'md5',
            ],
            $file->file
        );
    }

    public function testGetFilename()
    {
        $file = $this->getFile();
        $this->assertSame('file', $file->getFilename());
    }

    public function testGetSize()
    {
        $file = $this->getFile();
        $this->assertSame(666, $file->getSize());
    }

    public function testWrite()
    {
        $filename = '/tmp/test-mongo-grid-fs-file';
        $id = $this->prepareFile('abcd', ['filename' => $filename]);
        @unlink($filename);
        $file = $this->getGridFS()->findOne(['_id' => $id]);
        $this->assertInstanceOf(\MongoGridFSFile::class, $file);

        $file->write();

        $this->assertTrue(file_exists($filename));
        $this->assertSame('e2fc714c4727ee9395f324cd2e7f331f', md5_file($filename));
        unlink($filename);
    }

    public function testWriteSpecifyFilename()
    {
        $id = $this->prepareFile();
        $filename = '/tmp/test-mongo-grid-fs-file';
        @unlink($filename);
        $file = $this->getGridFS()->findOne(['_id' => $id]);
        $this->assertInstanceOf(\MongoGridFSFile::class, $file);

        $file->write($filename);

        $this->assertTrue(file_exists($filename));
        $this->assertSame('e2fc714c4727ee9395f324cd2e7f331f', md5_file($filename));
        unlink($filename);
    }

    public function testGetBytes()
    {
        $id = $this->prepareFile();
        $file = $this->getFile(['_id' => $id, 'length' => 4]);

        $result = $file->getBytes();

        $this->assertSame('abcd', $result);
    }

    public function testGetResource()
    {
        $data = str_repeat('a', 500 * 1024);
        $id = $this->prepareFile($data);
        $file = $this->getGridFS()->findOne(['_id' => $id]);
        $this->assertInstanceOf(\MongoGridFSFile::class, $file);

        $result = $file->getResource();

        $this->assertTrue(is_resource($result));
        $this->assertSame($data, stream_get_contents($result));
    }

    /**
     * @var \MongoGridFSFile
     */
    protected function getFile($extra = [])
    {
        $file = [
            '_id' => new \MongoID(),
            'length' => 666,
            'filename' => 'file',
            'md5' => 'md5',
        ];
        $file = array_merge($file, $extra);
        return new \MongoGridFSFile($this->getGridFS(), $file);
    }

    /**
     * @var \MongoID
     */
    protected function prepareFile($data = 'abcd', $extra = [])
    {
        $collection = $this->getGridFS();

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
}
