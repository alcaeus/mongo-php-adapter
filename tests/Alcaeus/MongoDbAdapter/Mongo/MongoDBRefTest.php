<?php

namespace Alcaeus\MongoDbAdapter\Tests\Mongo;

use Alcaeus\MongoDbAdapter\Tests\TestCase;

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
     * @dataProvider dataCreateThroughMongoDB
     */
    public function testCreateThroughMongoDB($expected, $document_or_id)
    {
        $ref = $this->getDatabase()->createDBRef('test', $document_or_id);

        $this->assertEquals($expected, $ref);
    }

    public static function dataCreateThroughMongoDB()
    {
        $id = new \MongoId();
        $validRef = ['$ref' => 'test', '$id' => $id];

        $object = new \stdClass();
        $object->_id = $id;

        $objectWithoutId = new \stdClass();
        return [
            'simpleId' => [$validRef, $id],
            'arrayWithIdProperty' => [$validRef, ['_id' => $id]],
            'objectWithIdProperty' => [$validRef, $object],
            'arrayWithoutId' => [null, []],
            'objectWithoutId' => [['$ref' => 'test', '$id' => $objectWithoutId], $objectWithoutId],
        ];
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

    public function testGetThroughMongoDB()
    {
        $id = new \MongoId();

        $db = $this->getDatabase();

        $document = ['_id' => $id, 'foo' => 'bar'];
        $db->selectCollection('test')->insert($document);

        $fetchedRef = $db->getDBRef(['$ref' => 'test', '$id' => $id]);
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

    public function testGetWithDifferentDatabase()
    {
        $database = $this->getDatabase();
        $collection = $this->getCollection();

        $document = ['foo' => 'bar'];

        $collection->insert($document);

        $ref = [
            '$ref' => $collection->getName(),
            '$id' => $document['_id'],
            '$db' => (string) $database,
        ];

        $referencedDocument = $this->getClient()->selectDB('foo')->getDBRef($ref);

        $this->assertEquals($document, $referencedDocument);
    }
}
