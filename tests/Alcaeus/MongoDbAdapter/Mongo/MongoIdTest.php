<?php

namespace Alcaeus\MongoDbAdapter\Tests\Mongo;

use Alcaeus\MongoDbAdapter\Tests\TestCase;
use MongoDB\BSON\ObjectID;

/**
 * @author alcaeus <alcaeus@alcaeus.org>
 */
class MongoIdTest extends TestCase
{
    public function testCreateWithoutParameter()
    {
        $id = new \MongoId();
        $stringId = (string) $id;

        $this->assertSame(24, strlen($stringId));
        $this->assertSame($stringId, $id->{'$id'});

        $serialized = serialize($id);
        $this->assertSame(sprintf('C:7:"MongoId":24:{%s}', $stringId), $serialized);

        $unserialized = unserialize($serialized);
        $this->assertInstanceOf('MongoId', $unserialized);
        $this->assertSame($stringId, (string) $unserialized);

        $json = json_encode($id);
        $this->assertSame(sprintf('{"$id":"%s"}', $stringId), $json);
    }

    public function testCreateWithString()
    {
        $original = '54203e08d51d4a1f868b456e';
        $id = new \MongoId($original);
        $this->assertSame($original, (string) $id);

        $this->assertSame(9127278, $id->getInc());
        $this->assertSame(1411399176, $id->getTimestamp());
        $this->assertSame(34335, $id->getPID());
    }

    /**
     * @expectedException \MongoException
     * @expectedExceptionMessage Invalid object ID
     */
    public function testCreateWithInvalidStringThrowsMongoException()
    {
        new \MongoId('invalid');
    }

    public function testCreateWithObjectId()
    {
        $this->skipTestIf(extension_loaded('mongo'));

        $original = '54203e08d51d4a1f868b456e';
        $objectId = new ObjectID($original);

        $id = new \MongoId($objectId);
        $this->assertSame($original, (string) $id);

        $this->assertAttributeNotSame($objectId, 'objectID', $id);
    }

    /**
     * @dataProvider dataIsValid
     */
    public function testIsValid($expected, $value)
    {
        $this->skipTestIf($value instanceof ObjectID && extension_loaded('mongo'));
        $this->assertSame($expected, \MongoId::isValid($value));
    }

    public static function dataIsValid()
    {
        $original = '54203e08d51d4a1f868b456e';

        return [
            'validId' => [true, '' . $original . ''],
            'MongoId' => [true, new \MongoId($original)],
            'ObjectID' => [true, new ObjectID($original)],
            'invalidString' => [false, 'abc'],
            'object' => [false, new \stdClass()],
        ];
    }
}
