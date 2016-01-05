<?php

namespace Alcaeus\MongoDbAdapter\Tests;

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

        $this->assertSame('code', (string) $code);

        $bsonCode = $code->toBSONType();
        $this->assertInstanceOf('MongoDB\BSON\Javascript', $bsonCode);
    }

    public function testCreateWithBsonObject()
    {
        $bsonCode = new \MongoDB\BSON\Javascript('code', ['scope' => 'bleh']);
        $code = new \MongoCode($bsonCode);

        $this->assertAttributeSame('', 'code', $code);
        $this->assertAttributeSame([], 'scope', $code);
    }
}
