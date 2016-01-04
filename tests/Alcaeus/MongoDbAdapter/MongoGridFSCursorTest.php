<?php

namespace Alcaeus\MongoDbAdapter\Tests;

class MongoGridFSCursorTest extends TestCase
{
    public function testCursor()
    {
        $cursor = $this->getCursor();
        $array = iterator_to_array($cursor);


        $this->assertCount(2, $array);
        $this->assertArrayHasKey('One.txt', $array);
        $this->assertArrayHasKey('Two.txt', $array);
        $firstFile = $array['One.txt'];
        $this->assertInstanceOf('MongoGridFSFile', $firstFile);
        $this->assertArraySubset(['length' => 3, 'filename' => 'One.txt'], $firstFile->file);
        $secondFile = $array['Two.txt'];
        $this->assertInstanceOf('MongoGridFSFile', $secondFile);
        $this->assertArraySubset(['length' => 3, 'filename' => 'Two.txt'], $secondFile->file);
    }

    private function getCursor()
    {
        $gridFS = $this->getGridFS();
        $gridFS->storeBytes('One', ['filename' => 'One.txt']);
        $gridFS->storeBytes('Two', ['filename' => 'Two.txt']);

        return $gridFS->find();
    }

    /**
     * @param string $name
     * @param \MongoDB|null $database
     * @return \MongoGridFS
     */
    private function getGridFS($name = 'testfs', \MongoDB $database = null)
    {
        if ($database === null) {
            $database = $this->getDatabase();
        }

        return new \MongoGridFS($database, $name);
    }
}
