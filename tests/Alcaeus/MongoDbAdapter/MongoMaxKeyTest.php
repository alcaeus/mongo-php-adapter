<?php

namespace Alcaeus\MongoDbAdapter\Tests;

/**
 * @author alcaeus <alcaeus@alcaeus.org>
 */
class MongoMaxKeyTest extends TestCase
{
    public function testConvert()
    {
        $MaxKey = new \MongoMaxKey();
        $this->assertInstanceOf('MongoDB\BSON\MaxKey', $MaxKey->toBSONType());
    }
}
