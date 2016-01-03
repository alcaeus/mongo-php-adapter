<?php

namespace Alcaeus\MongoDbAdapter\Tests;

/**
 * @author alcaeus <alcaeus@alcaeus.org>
 */
class MongoDBRefTest extends TestCase
{
    public function testCreate()
    {
        $id = new \MongoId();
        $ref = \MongoDBRef::create('foo', $id);
        $this->assertSame(['$ref' => 'foo', '$id' => $id], $ref);
    }

    public function testCreateWithDatabase()
    {
        $id = new \MongoId();
        $ref = \MongoDBRef::create('foo', $id, 'database');
        $this->assertSame(['$ref' => 'foo', '$id' => $id, '$db' => 'database'], $ref);
    }

    /**
     * @dataProvider dataIsRef
     */
    public function testIsRef($expected, $ref)
    {
        $this->assertSame($expected, \MongoDBRef::isRef($ref));
    }

    public static function dataIsRef()
    {
        $objectRef = new \stdClass();
        $objectRef->{'$ref'} = 'coll';
        $objectRef->{'$id'} = 'id';

        return [
            'validRef' => [true, ['$ref' => 'coll', '$id' => 'id']],
            'validRefWithDatabase' => [true, ['$ref' => 'coll', '$id' => 'id', '$db' => 'db']],
            'refMissing' => [false, ['$id' => 'id']],
            'idMissing' => [false, ['$ref' => 'coll']],
            'objectRef' => [true, $objectRef],
            'int' => [false, 5],
        ];
    }

    public function testGet()
    {
        $id = new \MongoId();

        $db = $this->getDatabase();

        $document = ['_id' => $id, 'foo' => 'bar'];
        $db->selectCollection('test')->insert($document);

        $fetchedRef = \MongoDBRef::get($db, ['$ref' => 'test', '$id' => $id]);
        $this->assertInternalType('array', $fetchedRef);
        $this->assertEquals($document, $fetchedRef);
    }

    public function testGetWithNonExistingDocument()
    {
        $db = $this->getDatabase();

        $this->assertNull(\MongoDBRef::get($db, ['$ref' => 'test', '$id' => 'foo']));
    }

    public function testGetWithInvalidRef()
    {
        $db = $this->getDatabase();

        $this->assertNull(\MongoDBRef::get($db, []));
    }
}
