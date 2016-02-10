<?php

namespace Alcaeus\MongoDbAdapter\Tests\Mongo;

use Alcaeus\MongoDbAdapter\Tests\TestCase;
use Alcaeus\MongoDbAdapter\TypeInterface;

/**
 * @author alcaeus <alcaeus@alcaeus.org>
 */
class MongoMinKeyTest extends TestCase
{
    public function testConvert()
    {
        $minKey = new \MongoMinKey();
        $this->skipTestUnless($minKey instanceof TypeInterface);
        $this->assertInstanceOf('MongoDB\BSON\MinKey', $minKey->toBSONType());
    }
}
