<?php

namespace Alcaeus\MongoDbAdapter\Tests\Mongo;

use Alcaeus\MongoDbAdapter\Tests\TestCase;
use Alcaeus\MongoDbAdapter\TypeInterface;
use ReflectionProperty;

/**
 * @author alcaeus <alcaeus@alcaeus.org>
 */
class MongoCodeTest extends TestCase
{
    public function testCreate()
    {
        $code = new \MongoCode('code', ['scope' => 'bleh']);

        $this->assertSame('code', $this->getAttributeValue($code, 'code'));
        $this->assertSame(['scope' => 'bleh'], $this->getAttributeValue($code, 'scope'));

        $this->assertSame('code', (string) $code);

        return $code;
    }

    public function testCreateWithoutScope()
    {
        $code = new \MongoCode('code');

        $this->assertSame('code', $this->getAttributeValue($code, 'code'));
        $this->assertSame([], $this->getAttributeValue($code, 'scope'));

        $this->assertSame('code', (string) $code);

        return $code;
    }

    public function testConvertToBson()
    {
        $code = new \MongoCode('code', ['scope' => 'bleh']);

        $this->skipTestUnless($code instanceof TypeInterface);

        $bsonCode = $code->toBSONType();
        $this->assertInstanceOf('MongoDB\BSON\Javascript', $bsonCode);
        $this->assertSame('code', $bsonCode->getCode());
        $this->assertEquals((object) ['scope' => 'bleh'], $bsonCode->getScope());
    }

    public function testConvertToBsonWithoutScope()
    {
        $code = new \MongoCode('code');

        $this->skipTestUnless($code instanceof TypeInterface);

        $bsonCode = $code->toBSONType();
        $this->assertInstanceOf('MongoDB\BSON\Javascript', $bsonCode);
        $this->assertSame('code', $bsonCode->getCode());
        $this->assertNull($bsonCode->getScope());
    }

    public function testCreateWithBsonObject()
    {
        $this->skipTestUnless(in_array(TypeInterface::class, class_implements('MongoCode')));

        $bsonCode = new \MongoDB\BSON\Javascript('code', ['scope' => 'bleh']);
        $code = new \MongoCode($bsonCode);

        $this->assertSame('code', $this->getAttributeValue($code, 'code'));
        $this->assertSame(['scope' => 'bleh'], $this->getAttributeValue($code, 'scope'));
    }

    private function getAttributeValue(\MongoCode $code, $attribute)
    {
        $property = new ReflectionProperty($code, $attribute);
        $property->setAccessible(true);

        return $property->getValue($code);
    }
}
