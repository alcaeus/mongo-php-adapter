<?php

namespace Alcaeus\MongoDbAdapter\Tests;

/**
 * @author alcaeus <alcaeus@alcaeus.org>
 */
class MongoMinKeyTest extends TestCase
{
    public function testConvert()
    {
        $minKey = new \MongoMinKey();
        $this->assertInstanceOf('MongoDB\BSON\MinKey', $minKey->toBSONType());
    }
}
