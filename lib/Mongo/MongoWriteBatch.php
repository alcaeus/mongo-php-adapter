<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

if (class_exists('MongoWriteBatch', false)) {
    return;
}

use Alcaeus\MongoDbAdapter\TypeConverter;
use Alcaeus\MongoDbAdapter\Helper\WriteConcernConverter;

/**
 * MongoWriteBatch allows you to "batch up" multiple operations (of same type)
 * and shipping them all to MongoDB at the same time. This can be especially
 * useful when operating on many documents at the same time to reduce roundtrips.
 *
 * @see http://php.net/manual/en/class.mongowritebatch.php
 */
class MongoWriteBatch
{
    use WriteConcernConverter;

    const COMMAND_INSERT = 1;
    const COMMAND_UPDATE = 2;
    const COMMAND_DELETE = 3;

    /**
     * @var MongoCollection
     */
    private $collection;

    /**
     * @var int
     */
    private $batchType;

    /**
     * @var array
     */
    private $writeOptions;

    /**
     * @var array
     */
    private $items = [];

    /**
     * Creates a new batch of write operations
     *
     * @see http://php.net/manual/en/mongowritebatch.construct.php
     * @param MongoCollection $collection
     * @param int $batchType
     * @param array $writeOptions
     */
    protected function __construct(MongoCollection $collection, $batchType, $writeOptions)
    {
        $this->collection = $collection;
        $this->batchType = $batchType;
        $this->writeOptions = $writeOptions;
    }

    /**
     * Adds a write operation to a batch
     *
     * @see http://php.net/manual/en/mongowritebatch.add.php
     * @param array|object $item
     * @return boolean
     */
    public function add($item)
    {
        if (is_object($item)) {
            $item = (array)$item;
        }

        $this->validate($item);
        $this->addItem($item);

        return true;
    }

    /**
     * Executes a batch of write operations
     *
     * @see http://php.net/manual/en/mongowritebatch.execute.php
     * @param array $writeOptions
     * @return array
     */
    final public function execute(array $writeOptions = [])
    {
        $writeOptions += $this->writeOptions;
        if (! count($this->items)) {
            return ['ok' => true];
        }

        if (isset($writeOptions['j'])) {
            trigger_error('j parameter is not supported', E_WARNING);
        }
        if (isset($writeOptions['fsync'])) {
            trigger_error('fsync parameter is not supported', E_WARNING);
        }

        $options['writeConcern'] = $this->createWriteConcernFromArray($writeOptions);
        if (isset($writeOptions['ordered'])) {
            $options['ordered'] = $writeOptions['ordered'];
        }

        $collection = $this->collection->getCollection();

        try {
            $result = $collection->BulkWrite($this->items, $options);
            $ok = true;
        } catch (\MongoDB\Driver\Exception\BulkWriteException $e) {
            $result = $e->getWriteResult();
            $ok = false;
        }

        if ($ok === true) {
            $this->items = [];
        }

        switch ($this->batchType) {
            case self::COMMAND_UPDATE:
                $upsertedIds = [];
                foreach ($result->getUpsertedIds() as $index => $id) {
                    $upsertedIds[] = [
                        'index' => $index,
                        '_id' => TypeConverter::toLegacy($id)
                    ];
                }

                $result = [
                    'nMatched' => $result->getMatchedCount(),
                    'nModified' => $result->getModifiedCount(),
                    'nUpserted' => $result->getUpsertedCount(),
                    'ok' => $ok,
                ];

                if (count($upsertedIds)) {
                    $result['upserted'] = $upsertedIds;
                }

                return $result;

            case self::COMMAND_DELETE:
                return [
                    'nRemoved' => $result->getDeletedCount(),
                    'ok' => $ok,
                ];

            case self::COMMAND_INSERT:
                return [
                    'nInserted' => $result->getInsertedCount(),
                    'ok' => $ok,
                ];
        }
    }

    private function validate(array $item)
    {
        switch ($this->batchType) {
            case self::COMMAND_UPDATE:
                if (! isset($item['q'])) {
                    throw new Exception("Expected \$item to contain 'q' key");
                }
                if (! isset($item['u'])) {
                    throw new Exception("Expected \$item to contain 'u' key");
                }
                break;

            case self::COMMAND_DELETE:
                if (! isset($item['q'])) {
                    throw new Exception("Expected \$item to contain 'q' key");
                }
                if (! isset($item['limit'])) {
                    throw new Exception("Expected \$item to contain 'limit' key");
                }
                break;
        }
    }

    private function addItem(array $item)
    {
        switch ($this->batchType) {
            case self::COMMAND_UPDATE:
                $method = isset($item['multi']) ? 'updateMany' : 'updateOne';

                $options = [];
                if (isset($item['upsert']) && $item['upsert']) {
                    $options['upsert'] = true;
                }

                $this->items[] = [$method => [TypeConverter::fromLegacy($item['q']), TypeConverter::fromLegacy($item['u']), $options]];
                break;

            case self::COMMAND_INSERT:
                $this->items[] = ['insertOne' => [TypeConverter::fromLegacy($item)]];
                break;

            case self::COMMAND_DELETE:
                $method = $item['limit'] === 0 ? 'deleteMany' : 'deleteOne';

                $this->items[] = [$method => [TypeConverter::fromLegacy($item['q'])]];
                break;
        }
    }
}
