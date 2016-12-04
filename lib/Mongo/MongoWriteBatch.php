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
use MongoDB\Driver\Exception\BulkWriteException;
use MongoDB\Driver\WriteError;
use MongoDB\Driver\WriteResult;

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
            trigger_error('j parameter is not supported', E_USER_WARNING);
        }
        if (isset($writeOptions['fsync'])) {
            trigger_error('fsync parameter is not supported', E_USER_WARNING);
        }

        $options['writeConcern'] = $this->createWriteConcernFromArray($writeOptions);
        if (isset($writeOptions['ordered'])) {
            $options['ordered'] = $writeOptions['ordered'];
        }

        try {
            $writeResult = $this->collection->getCollection()->bulkWrite($this->items, $options);
            $resultDocument = [];
            $ok = true;
        } catch (BulkWriteException $e) {
            $writeResult = $e->getWriteResult();
            $resultDocument = ['writeErrors' => $this->convertWriteErrors($writeResult)];
            $ok = false;
        }

        $this->items = [];

        switch ($this->batchType) {
            case self::COMMAND_UPDATE:
                $upsertedIds = [];
                foreach ($writeResult->getUpsertedIds() as $index => $id) {
                    $upsertedIds[] = [
                        'index' => $index,
                        '_id' => TypeConverter::toLegacy($id)
                    ];
                }

                $resultDocument += [
                    'nMatched' => $writeResult->getMatchedCount(),
                    'nModified' => $writeResult->getModifiedCount(),
                    'nUpserted' => $writeResult->getUpsertedCount(),
                    'ok' => true,
                ];

                if (count($upsertedIds)) {
                    $resultDocument['upserted'] = $upsertedIds;
                }
                break;

            case self::COMMAND_DELETE:
                $resultDocument += [
                    'nRemoved' => $writeResult->getDeletedCount(),
                    'ok' => true,
                ];
                break;

            case self::COMMAND_INSERT:
                $resultDocument += [
                    'nInserted' => $writeResult->getInsertedCount(),
                    'ok' => true,
                ];
                break;
        }

        if (! $ok) {
            // Exception code is hardcoded to the value in ext-mongo, see
            // https://github.com/mongodb/mongo-php-driver-legacy/blob/ab4bc0d90e93b3f247f6bcb386d0abc8d2fa7d74/batch/write.c#L428
            throw new \MongoWriteConcernException('Failed write', 911, null, $resultDocument);
        }

        return $resultDocument;
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

    /**
     * @param WriteResult $result
     * @return array
     */
    private function convertWriteErrors(WriteResult $result)
    {
        $writeErrors = [];
        /** @var WriteError $writeError */
        foreach ($result->getWriteErrors() as $writeError) {
            $writeErrors[] = [
                'index' => $writeError->getIndex(),
                'code' => $writeError->getCode(),
                'errmsg' => $writeError->getMessage(),
            ];
        }
        return $writeErrors;
    }
}
