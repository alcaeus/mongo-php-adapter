<?php

namespace Alcaeus\MongoDbAdapter\Tests\Mongo;

use Alcaeus\MongoDbAdapter\Tests\TestCase;
use Alcaeus\MongoDbAdapter\TypeInterface;

/**
 * @author alcaeus <alcaeus@alcaeus.org>
 */
class MongoCodeTest extends TestCase
{
    public function testCreate()
    {
        $code = new \MongoCode('code', ['scope' => 'bleh']);
        $this->assertAttributeSame('code', 'code', $code);
        $this->assertAttributeSame(['scope' => 'bleh'], 'scope', $code);

        $this->assertSame('code', (string)$code);

        return $code;
    }

    /**
     * @depends testCreate
     */
    public function testConvertToBson(\MongoCode $code)
    {
        $this->skipTestUnless($code instanceof TypeInterface);

        $bsonCode = $code->toBSONType();
        $this->assertInstanceOf('MongoDB\BSON\Javascript', $bsonCode);
    }

    public function testCreateWithBsonObject()
    {
        $this->skipTestUnless(in_array(TypeInterface::class, class_implements('MongoCode')));

        $bsonCode = new \MongoDB\BSON\Javascript('code', ['scope' => 'bleh']);
        $code = new \MongoCode($bsonCode);

        $this->assertAttributeSame('code', 'code', $code);
        $this->assertAttributeSame(['scope' => 'bleh'], 'scope', $code);
    }
}
