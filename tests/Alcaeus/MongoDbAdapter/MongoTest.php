<?php

namespace Alcaeus\MongoDbAdapter\Tests;

/**
 * @author alcaeus <alcaeus@alcaeus.org>
 */
class MongoTest extends TestCase
{
	public function testSlave()
    {
        $mongo = $this->getMongo();
        $this->assertFalse($mongo->getSlaveOkay());
        $this->assertFalse($mongo->setSlaveOkay());
        $this->assertTrue($mongo->getSlaveOkay());
        $this->assertTrue($mongo->setSlaveOkay(false));
        $this->assertFalse($mongo->getSlaveOkay());
    }

    /**
     * @param array|null $options
     * @return \MongoClient
     */
    protected function getMongo($options = null)
    {
        $args = ['mongodb://localhost'];
        if ($options !== null) {
            $args[] = $options;
        }

        $reflection = new \ReflectionClass('Mongo');

        return $reflection->newInstanceArgs($args);
    }
}