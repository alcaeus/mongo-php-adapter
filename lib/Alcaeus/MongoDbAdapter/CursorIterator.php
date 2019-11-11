<?php

namespace Alcaeus\MongoDbAdapter;

use IteratorIterator;
use MongoDB\BSON\ObjectID;
use Traversable;

/**
 * @internal
 */
final class CursorIterator extends IteratorIterator
{
    /** @var bool */
    private $useIdAsKey;

    public function __construct(Traversable $iterator, $useIdAsKey = false)
    {
        parent::__construct($iterator);

        $this->useIdAsKey = $useIdAsKey;
    }

    public function key()
    {
        if (!$this->useIdAsKey) {
            return parent::key();
        }

        $current = $this->current();

        if (!isset($current->_id) || (is_object($current->_id) && !$current->_id instanceof ObjectID)) {
            return parent::key();
        }

        return (string) $current->_id;
    }
}
