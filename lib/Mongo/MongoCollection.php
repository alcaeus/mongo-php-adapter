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

if (class_exists('MongoCollection', false)) {
    return;
}

use Alcaeus\MongoDbAdapter\Helper;
use Alcaeus\MongoDbAdapter\TypeConverter;
use Alcaeus\MongoDbAdapter\ExceptionConverter;
use MongoDB\Driver\Exception\CommandException;

/**
 * Represents a database collection.
 * @link http://www.php.net/manual/en/class.mongocollection.php
 */
class MongoCollection
{
    use Helper\ReadPreference;
    use Helper\SlaveOkay;
    use Helper\WriteConcern;

    const ASCENDING = 1;
    const DESCENDING = -1;

    /**
     * @var MongoDB
     */
    public $db = null;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var \MongoDB\Collection
     */
    protected $collection;

    /**
     * Creates a new collection
     *
     * @link http://www.php.net/manual/en/mongocollection.construct.php
     * @param MongoDB $db Parent database.
     * @param string $name Name for this collection.
     * @throws Exception
     */
    public function __construct(MongoDB $db, $name)
    {
        $this->checkCollectionName($name);
        $this->db = $db;
        $this->name = (string) $name;

        $this->setReadPreferenceFromArray($db->getReadPreference());
        $this->setWriteConcernFromArray($db->getWriteConcern());

        $this->createCollectionObject();
    }

    /**
     * Gets the underlying collection for this object
     *
     * @internal This part is not of the ext-mongo API and should not be used
     * @return \MongoDB\Collection
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * String representation of this collection
     *
     * @link http://www.php.net/manual/en/mongocollection.--tostring.php
     * @return string Returns the full name of this collection.
     */
    public function __toString()
    {
        return (string) $this->db . '.' . $this->name;
    }

    /**
     * Gets a collection
     *
     * @link http://www.php.net/manual/en/mongocollection.get.php
     * @param string $name The next string in the collection name.
     * @return MongoCollection
     */
    public function __get($name)
    {
        // Handle w and wtimeout properties that replicate data stored in $readPreference
        if ($name === 'w' || $name === 'wtimeout') {
            return $this->getWriteConcern()[$name];
        }

        return $this->db->selectCollection($this->name . '.' . str_replace(chr(0), '', $name));
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        if ($name === 'w' || $name === 'wtimeout') {
            $this->setWriteConcernFromArray([$name => $value] + $this->getWriteConcern());
            $this->createCollectionObject();
        }
    }

    /**
     * Perform an aggregation using the aggregation framework
     *
     * @link http://www.php.net/manual/en/mongocollection.aggregate.php
     * @param array $pipeline
     * @param array $op
     * @return array
     */
    public function aggregate(array $pipeline, array $op = [])
    {
        if (! TypeConverter::isNumericArray($pipeline)) {
            $operators = func_get_args();
            $pipeline = [];
            $options = [];

            $i = 0;
            foreach ($operators as $operator) {
                $i++;
                if (! is_array($operator)) {
                    trigger_error("Argument $i is not an array", E_USER_WARNING);
                    return;
                }

                $pipeline[] = $operator;
            }
        } else {
            $options = $op;
        }

        if (isset($options['cursor'])) {
            $options['useCursor'] = true;

            if (isset($options['cursor']['batchSize'])) {
                $options['batchSize'] = $options['cursor']['batchSize'];
            }

            unset($options['cursor']);
        } else {
            $options['useCursor'] = false;
        }

        try {
            $cursor = $this->collection->aggregate(TypeConverter::fromLegacy($pipeline), $options);

            return [
                'ok' => 1.0,
                'result' => TypeConverter::toLegacy($cursor),
                'waitedMS' => 0,
            ];
        } catch (\MongoDB\Driver\Exception\Exception $e) {
            throw ExceptionConverter::toLegacy($e, 'MongoResultException');
        }
    }

    /**
     * Execute an aggregation pipeline command and retrieve results through a cursor
     *
     * @link http://php.net/manual/en/mongocollection.aggregatecursor.php
     * @param array $pipeline
     * @param array $options
     * @return MongoCommandCursor
     */
    public function aggregateCursor(array $pipeline, array $options = [])
    {
        // Build command manually, can't use mongo-php-library here
        $command = [
            'aggregate' => $this->name,
            'pipeline' => $pipeline
        ];

        // Convert cursor option
        if (! isset($options['cursor'])) {
            $options['cursor'] = new \stdClass();
        }

        $command += $options;

        $cursor = new MongoCommandCursor($this->db->getConnection(), (string) $this, $command);
        $cursor->setReadPreference($this->getReadPreference());

        return $cursor;
    }

    /**
     * Returns this collection's name
     *
     * @link http://www.php.net/manual/en/mongocollection.getname.php
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function setReadPreference($readPreference, $tags = null)
    {
        $result = $this->setReadPreferenceFromParameters($readPreference, $tags);
        $this->createCollectionObject();

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function setWriteConcern($wstring, $wtimeout = 0)
    {
        $result = $this->setWriteConcernFromParameters($wstring, $wtimeout);
        $this->createCollectionObject();

        return $result;
    }

    /**
     * Drops this collection
     *
     * @link http://www.php.net/manual/en/mongocollection.drop.php
     * @return array Returns the database response.
     */
    public function drop()
    {
        return TypeConverter::toLegacy($this->collection->drop());
    }

    /**
     * Validates this collection
     *
     * @link http://www.php.net/manual/en/mongocollection.validate.php
     * @param bool $scan_data Only validate indices, not the base collection.
     * @return array Returns the database's evaluation of this object.
     */
    public function validate($scan_data = false)
    {
        $command = [
            'validate' => $this->name,
            'full'     => $scan_data,
        ];

        return $this->db->command($command);
    }

    /**
     * Inserts an array into the collection
     *
     * @link http://www.php.net/manual/en/mongocollection.insert.php
     * @param array|object $a
     * @param array $options
     * @throws MongoException if the inserted document is empty or if it contains zero-length keys. Attempting to insert an object with protected and private properties will cause a zero-length key error.
     * @throws MongoCursorException if the "w" option is set and the write fails.
     * @throws MongoCursorTimeoutException if the "w" option is set to a value greater than one and the operation takes longer than MongoCursor::$timeout milliseconds to complete. This does not kill the operation on the server, it is a client-side timeout. The operation in MongoCollection::$wtimeout is milliseconds.
     * @return bool|array Returns an array containing the status of the insertion if the "w" option is set.
     */
    public function insert(&$a, array $options = [])
    {
        if ($this->ensureDocumentHasMongoId($a) === null) {
            trigger_error(sprintf('%s(): expects parameter %d to be an array or object, %s given', __METHOD__, 1, gettype($a)), E_USER_WARNING);
            return;
        }

        $this->mustBeArrayOrObject($a);

        try {
            $result = $this->collection->insertOne(
                TypeConverter::fromLegacy($a),
                $this->convertWriteConcernOptions($options)
            );
        } catch (\MongoDB\Driver\Exception\Exception $e) {
            throw ExceptionConverter::toLegacy($e);
        }

        if (! $result->isAcknowledged()) {
            return true;
        }

        return [
            'ok' => 1.0,
            'n' => 0,
            'err' => null,
            'errmsg' => null,
        ];
    }

    /**
     * Inserts multiple documents into this collection
     *
     * @link http://www.php.net/manual/en/mongocollection.batchinsert.php
     * @param array $a An array of arrays.
     * @param array $options Options for the inserts.
     * @throws MongoCursorException
     * @return mixed If "safe" is set, returns an associative array with the status of the inserts ("ok") and any error that may have occured ("err"). Otherwise, returns TRUE if the batch insert was successfully sent, FALSE otherwise.
     */
    public function batchInsert(array &$a, array $options = [])
    {
        if (empty($a)) {
            throw new \MongoException('No write ops were included in the batch');
        }

        $continueOnError = isset($options['continueOnError']) && $options['continueOnError'];

        foreach ($a as $key => $item) {
            try {
                if (! $this->ensureDocumentHasMongoId($a[$key])) {
                    if ($continueOnError) {
                        unset($a[$key]);
                    } else {
                        trigger_error(sprintf('%s expects parameter %d to be an array or object, %s given', __METHOD__, 1, gettype($a)), E_USER_WARNING);
                        return;
                    }
                }
            } catch (MongoException $e) {
                if (! $continueOnError) {
                    throw $e;
                }
            }
        }

        try {
            $result = $this->collection->insertMany(
                TypeConverter::fromLegacy(array_values($a)),
                $this->convertWriteConcernOptions($options)
            );
        } catch (\MongoDB\Driver\Exception\Exception $e) {
            throw ExceptionConverter::toLegacy($e, 'MongoResultException');
        }

        if (! $result->isAcknowledged()) {
            return true;
        }

        return [
            'ok' => 1.0,
            'connectionId' => 0,
            'n' => 0,
            'syncMillis' => 0,
            'writtenTo' => null,
            'err' => null,
        ];
    }

    /**
     * Update records based on a given criteria
     *
     * @link http://www.php.net/manual/en/mongocollection.update.php
     * @param array|object $criteria Description of the objects to update.
     * @param array|object $newobj The object with which to update the matching records.
     * @param array $options
     * @return bool|array
     * @throws MongoException
     * @throws MongoWriteConcernException
     */
    public function update($criteria, $newobj, array $options = [])
    {
        $this->mustBeArrayOrObject($criteria);
        $this->mustBeArrayOrObject($newobj);

        $this->checkKeys((array) $newobj);

        $multiple = isset($options['multiple']) ? $options['multiple'] : false;
        $isReplace = ! \MongoDB\is_first_key_operator($newobj);

        if ($isReplace && $multiple) {
            throw new \MongoWriteConcernException('multi update only works with $ operators', 9);
        }
        unset($options['multiple']);

        $method = $isReplace ? 'replace' : 'update';
        $method .= $multiple ? 'Many' : 'One';

        try {
            /** @var \MongoDB\UpdateResult $result */
            $result = $this->collection->$method(
                TypeConverter::fromLegacy($criteria),
                TypeConverter::fromLegacy($newobj),
                $this->convertWriteConcernOptions($options)
            );
        } catch (\MongoDB\Driver\Exception\Exception $e) {
            throw ExceptionConverter::toLegacy($e);
        }

        if (! $result->isAcknowledged()) {
            return true;
        }

        return [
            'ok' => 1.0,
            'nModified' => $result->getModifiedCount(),
            'n' => $result->getMatchedCount(),
            'err' => null,
            'errmsg' => null,
            'updatedExisting' => $result->getUpsertedCount() == 0 && $result->getModifiedCount() > 0,
        ];
    }

    /**
     * Remove records from this collection
     *
     * @link http://www.php.net/manual/en/mongocollection.remove.php
     * @param array $criteria Query criteria for the documents to delete.
     * @param array $options An array of options for the remove operation.
     * @throws MongoCursorException
     * @throws MongoCursorTimeoutException
     * @return bool|array Returns an array containing the status of the removal
     * if the "w" option is set. Otherwise, returns TRUE.
     */
    public function remove(array $criteria = [], array $options = [])
    {
        $multiple = isset($options['justOne']) ? !$options['justOne'] : true;
        $method = $multiple ? 'deleteMany' : 'deleteOne';

        try {
            /** @var \MongoDB\DeleteResult $result */
            $result = $this->collection->$method(
                TypeConverter::fromLegacy($criteria),
                $this->convertWriteConcernOptions($options)
            );
        } catch (\MongoDB\Driver\Exception\Exception $e) {
            throw ExceptionConverter::toLegacy($e);
        }

        if (! $result->isAcknowledged()) {
            return true;
        }

        return [
            'ok' => 1.0,
            'n' => $result->getDeletedCount(),
            'err' => null,
            'errmsg' => null
        ];
    }

    /**
     * Querys this collection
     *
     * @link http://www.php.net/manual/en/mongocollection.find.php
     * @param array $query The fields for which to search.
     * @param array $fields Fields of the results to return.
     * @return MongoCursor
     */
    public function find(array $query = [], array $fields = [])
    {
        $cursor = new MongoCursor($this->db->getConnection(), (string) $this, $query, $fields);
        $cursor->setReadPreference($this->getReadPreference());

        return $cursor;
    }

    /**
     * Retrieve a list of distinct values for the given key across a collection
     *
     * @link http://www.php.net/manual/ru/mongocollection.distinct.php
     * @param string $key The key to use.
     * @param array $query An optional query parameters
     * @return array|bool Returns an array of distinct values, or FALSE on failure
     */
    public function distinct($key, array $query = [])
    {
        try {
            return array_map([TypeConverter::class, 'toLegacy'], $this->collection->distinct($key, TypeConverter::fromLegacy($query)));
        } catch (\MongoDB\Driver\Exception\Exception $e) {
            return false;
        }
    }

    /**
     * Update a document and return it
     *
     * @link http://www.php.net/manual/ru/mongocollection.findandmodify.php
     * @param array $query The query criteria to search for.
     * @param array $update The update criteria.
     * @param array $fields Optionally only return these fields.
     * @param array $options An array of options to apply, such as remove the match document from the DB and return it.
     * @return array Returns the original document, or the modified document when new is set.
     */
    public function findAndModify(array $query, array $update = null, array $fields = null, array $options = [])
    {
        $query = TypeConverter::fromLegacy($query);
        try {
            if (isset($options['remove'])) {
                unset($options['remove']);
                $document = $this->collection->findOneAndDelete($query, $options);
            } else {
                $update = is_array($update) ? $update : [];
                if (isset($options['update']) && is_array($options['update'])) {
                    $update = $options['update'];
                    unset($options['update']);
                }

                $update = TypeConverter::fromLegacy($update);

                if (isset($options['new'])) {
                    $options['returnDocument'] = \MongoDB\Operation\FindOneAndUpdate::RETURN_DOCUMENT_AFTER;
                    unset($options['new']);
                }

                $options['projection'] = TypeConverter::convertProjection($fields);

                if (! \MongoDB\is_first_key_operator($update)) {
                    $document = $this->collection->findOneAndReplace($query, $update, $options);
                } else {
                    $document = $this->collection->findOneAndUpdate($query, $update, $options);
                }
            }
        } catch (\MongoDB\Driver\Exception\ConnectionException $e) {
            throw new MongoResultException($e->getMessage(), $e->getCode(), $e);
        } catch (\MongoDB\Driver\Exception\Exception $e) {
            throw ExceptionConverter::toLegacy($e, 'MongoResultException');
        }

        if ($document) {
            $document = TypeConverter::toLegacy($document);
        }

        return $document;
    }

    /**
     * Querys this collection, returning a single element
     *
     * @link http://www.php.net/manual/en/mongocollection.findone.php
     * @param array $query The fields for which to search.
     * @param array $fields Fields of the results to return.
     * @param array $options
     * @return array|null
     */
    public function findOne($query = [], array $fields = [], array $options = [])
    {
        // Can't typehint for array since MongoGridFS extends and accepts strings
        if (! is_array($query)) {
            trigger_error(sprintf('MongoCollection::findOne(): expects parameter 1 to be an array or object, %s given', gettype($query)), E_USER_WARNING);
            return;
        }

        $options = ['projection' => TypeConverter::convertProjection($fields)] + $options;
        try {
            $document = $this->collection->findOne(TypeConverter::fromLegacy($query), $options);
        } catch (\MongoDB\Driver\Exception\Exception $e) {
            throw ExceptionConverter::toLegacy($e);
        }

        if ($document !== null) {
            $document = TypeConverter::toLegacy($document);
        }

        return $document;
    }

    /**
     * Creates an index on the given field(s), or does nothing if the index already exists
     *
     * @link http://www.php.net/manual/en/mongocollection.createindex.php
     * @param array $keys Field or fields to use as index.
     * @param array $options [optional] This parameter is an associative array of the form array("optionname" => <boolean>, ...).
     * @return array Returns the database response.
     */
    public function createIndex($keys, array $options = [])
    {
        if (is_string($keys)) {
            if (empty($keys)) {
                throw new MongoException('empty string passed as key field');
            }
            $keys = [$keys => 1];
        }

        if (is_object($keys)) {
            $keys = (array) $keys;
        }

        if (! is_array($keys) || ! count($keys)) {
            throw new MongoException('index specification has no elements');
        }

        if (! isset($options['name'])) {
            $options['name'] = \MongoDB\generate_index_name($keys);
        }

        $indexes = iterator_to_array($this->collection->listIndexes());
        $indexCount = count($indexes);
        $collectionExists = true;
        $indexExists = false;

        // listIndexes returns 0 for non-existing collections while the legacy driver returns 1
        if ($indexCount === 0) {
            $collectionExists = false;
            $indexCount = 1;
        }

        foreach ($indexes as $index) {
            if ($index->getKey() === $keys || $index->getName() === $options['name']) {
                $indexExists = true;
                break;
            }
        }

        try {
            foreach (['w', 'wTimeoutMS', 'safe', 'timeout', 'wtimeout'] as $invalidOption) {
                if (isset($options[$invalidOption])) {
                    unset($options[$invalidOption]);
                }
            }

            $this->collection->createIndex($keys, $options);
        } catch (\MongoDB\Driver\Exception\Exception $e) {
            if (! $e instanceof CommandException || strpos($e->getMessage(), 'with a different name') === false) {
                throw ExceptionConverter::toLegacy($e, 'MongoResultException');
            }
        }

        $result = [
            'createdCollectionAutomatically' => !$collectionExists,
            'numIndexesBefore' => $indexCount,
            'numIndexesAfter' => $indexCount,
            'note' => 'all indexes already exist',
            'ok' => 1.0,
        ];

        if (! $indexExists) {
            $result['numIndexesAfter']++;
            unset($result['note']);
        }

        return $result;
    }

    /**
     * Creates an index on the given field(s), or does nothing if the index already exists
     *
     * @link http://www.php.net/manual/en/mongocollection.ensureindex.php
     * @param array $keys Field or fields to use as index.
     * @param array $options [optional] This parameter is an associative array of the form array("optionname" => <boolean>, ...).
     * @return array Returns the database response.
     * @deprecated Use MongoCollection::createIndex() instead.
     */
    public function ensureIndex(array $keys, array $options = [])
    {
        return $this->createIndex($keys, $options);
    }

    /**
     * Deletes an index from this collection
     *
     * @link http://www.php.net/manual/en/mongocollection.deleteindex.php
     * @param string|array $keys Field or fields from which to delete the index.
     * @return array Returns the database response.
     */
    public function deleteIndex($keys)
    {
        if (is_string($keys)) {
            $indexName = $keys;
            if (! preg_match('#_-?1$#', $indexName)) {
                $indexName .= '_1';
            }
        } elseif (is_array($keys)) {
            $indexName = \MongoDB\generate_index_name($keys);
        } else {
            throw new \InvalidArgumentException();
        }

        try {
            return TypeConverter::toLegacy($this->collection->dropIndex($indexName));
        } catch (\MongoDB\Driver\Exception\Exception $e) {
            return ExceptionConverter::toResultArray($e) + ['nIndexesWas' => count($this->getIndexInfo())];
        }
    }

    /**
     * Delete all indexes for this collection
     *
     * @link http://www.php.net/manual/en/mongocollection.deleteindexes.php
     * @return array Returns the database response.
     */
    public function deleteIndexes()
    {
        try {
            return TypeConverter::toLegacy($this->collection->dropIndexes());
        } catch (\MongoDB\Driver\Exception\Exception $e) {
            return ExceptionConverter::toResultArray($e);
        }
    }

    /**
     * Returns an array of index names for this collection
     *
     * @link http://www.php.net/manual/en/mongocollection.getindexinfo.php
     * @return array Returns a list of index names.
     */
    public function getIndexInfo()
    {
        $convertIndex = function (\MongoDB\Model\IndexInfo $indexInfo) {
            $infos = [
                'v' => $indexInfo->getVersion(),
                'key' => $indexInfo->getKey(),
                'name' => $indexInfo->getName(),
                'ns' => $indexInfo->getNamespace(),
            ];

            $additionalKeys = [
                'unique',
                'sparse',
                'partialFilterExpression',
                'expireAfterSeconds',
                'storageEngine',
                'weights',
                'default_language',
                'language_override',
                'textIndexVersion',
                'collation',
                '2dsphereIndexVersion',
                'bucketSize'
            ];

            foreach ($additionalKeys as $key) {
                if (! isset($indexInfo[$key])) {
                    continue;
                }

                $infos[$key] = $indexInfo[$key];
            }

            return $infos;
        };

        return array_map($convertIndex, iterator_to_array($this->collection->listIndexes()));
    }

    /**
     * Counts the number of documents in this collection
     *
     * @link http://www.php.net/manual/en/mongocollection.count.php
     * @param array|stdClass $query
     * @param array $options
     * @return int Returns the number of documents matching the query.
     */
    public function count($query = [], $options = [])
    {
        try {
            // Handle legacy mode - limit and skip as second and third parameters, respectively
            if (! is_array($options)) {
                $limit = $options;
                $options = [];

                if ($limit !== null) {
                    $options['limit'] = (int) $limit;
                }

                if (func_num_args() > 2) {
                    $options['skip'] = (int) func_get_args()[2];
                }
            }

            return $this->collection->count(TypeConverter::fromLegacy($query), $options);
        } catch (\MongoDB\Driver\Exception\Exception $e) {
            throw ExceptionConverter::toLegacy($e);
        }
    }

    /**
     * Saves an object to this collection
     *
     * @link http://www.php.net/manual/en/mongocollection.save.php
     * @param array|object $a Array to save. If an object is used, it may not have protected or private properties.
     * @param array $options Options for the save.
     * @throws MongoException if the inserted document is empty or if it contains zero-length keys. Attempting to insert an object with protected and private properties will cause a zero-length key error.
     * @throws MongoCursorException if the "w" option is set and the write fails.
     * @throws MongoCursorTimeoutException if the "w" option is set to a value greater than one and the operation takes longer than MongoCursor::$timeout milliseconds to complete. This does not kill the operation on the server, it is a client-side timeout. The operation in MongoCollection::$wtimeout is milliseconds.
     * @return array|boolean If w was set, returns an array containing the status of the save.
     * Otherwise, returns a boolean representing if the array was not empty (an empty array will not be inserted).
     */
    public function save(&$a, array $options = [])
    {
        $id = $this->ensureDocumentHasMongoId($a);

        $document = (array) $a;

        $options['upsert'] = true;

        try {
            /** @var \MongoDB\UpdateResult $result */
            $result = $this->collection->replaceOne(
                TypeConverter::fromLegacy(['_id' => $id]),
                TypeConverter::fromLegacy($document),
                $this->convertWriteConcernOptions($options)
            );

            if (! $result->isAcknowledged()) {
                return true;
            }

            $resultArray = [
                'ok' => 1.0,
                'nModified' => $result->getModifiedCount(),
                'n' => $result->getUpsertedCount() + $result->getModifiedCount(),
                'err' => null,
                'errmsg' => null,
                'updatedExisting' => $result->getUpsertedCount() == 0 && $result->getModifiedCount() > 0,
            ];
            if ($result->getUpsertedId() !== null) {
                $resultArray['upserted'] = TypeConverter::toLegacy($result->getUpsertedId());
            }

            return $resultArray;
        } catch (\MongoDB\Driver\Exception\Exception $e) {
            throw ExceptionConverter::toLegacy($e);
        }
    }

    /**
     * Creates a database reference
     *
     * @link http://www.php.net/manual/en/mongocollection.createdbref.php
     * @param array|object $document_or_id Object to which to create a reference.
     * @return array Returns a database reference array.
     */
    public function createDBRef($document_or_id)
    {
        if ($document_or_id instanceof \MongoId) {
            $id = $document_or_id;
        } elseif (is_object($document_or_id)) {
            if (! isset($document_or_id->_id)) {
                return null;
            }

            $id = $document_or_id->_id;
        } elseif (is_array($document_or_id)) {
            if (! isset($document_or_id['_id'])) {
                return null;
            }

            $id = $document_or_id['_id'];
        } else {
            $id = $document_or_id;
        }

        return MongoDBRef::create($this->name, $id);
    }

    /**
     * Fetches the document pointed to by a database reference
     *
     * @link http://www.php.net/manual/en/mongocollection.getdbref.php
     * @param array $ref A database reference.
     * @return array Returns the database document pointed to by the reference.
     */
    public function getDBRef(array $ref)
    {
        return $this->db->getDBRef($ref);
    }

    /**
     * Performs an operation similar to SQL's GROUP BY command
     *
     * @link http://www.php.net/manual/en/mongocollection.group.php
     * @param mixed $keys Fields to group by. If an array or non-code object is passed, it will be the key used to group results.
     * @param array $initial Initial value of the aggregation counter object.
     * @param MongoCode|string $reduce A function that aggregates (reduces) the objects iterated.
     * @param array $condition An condition that must be true for a row to be considered.
     * @return array
     */
    public function group($keys, array $initial, $reduce, array $condition = [])
    {
        if (is_string($reduce)) {
            $reduce = new MongoCode($reduce);
        }

        $command = [
            'group' => [
                'ns' => $this->name,
                '$reduce' => (string) $reduce,
                'initial' => $initial,
                'cond' => $condition,
            ],
        ];

        if ($keys instanceof MongoCode) {
            $command['group']['$keyf'] = (string) $keys;
        } else {
            $command['group']['key'] = $keys;
        }
        if (array_key_exists('condition', $condition)) {
            $command['group']['cond'] = $condition['condition'];
        }
        if (array_key_exists('finalize', $condition)) {
            if ($condition['finalize'] instanceof MongoCode) {
                $condition['finalize'] = (string) $condition['finalize'];
            }
            $command['group']['finalize'] = $condition['finalize'];
        }

        return $this->db->command($command);
    }

    /**
     * Returns an array of cursors to iterator over a full collection in parallel
     *
     * @link http://www.php.net/manual/en/mongocollection.parallelcollectionscan.php
     * @param int $num_cursors The number of cursors to request from the server. Please note, that the server can return less cursors than you requested.
     * @return MongoCommandCursor[]
     */
    public function parallelCollectionScan($num_cursors)
    {
        $this->notImplemented();
    }

    protected function notImplemented()
    {
        throw new \Exception('Not implemented');
    }

    /**
     * @return \MongoDB\Collection
     */
    private function createCollectionObject()
    {
        $options = [
            'readPreference' => $this->readPreference,
            'writeConcern' => $this->writeConcern,
        ];

        if ($this->collection === null) {
            $this->collection = $this->db->getDb()->selectCollection($this->name, $options);
        } else {
            $this->collection = $this->collection->withOptions($options);
        }
    }

    /**
     * Converts legacy write concern options to a WriteConcern object
     *
     * @param array $options
     * @return array
     */
    private function convertWriteConcernOptions(array $options)
    {
        if (isset($options['safe'])) {
            $options['w'] = ($options['safe']) ? 1 : 0;
        }

        if (isset($options['wtimeout']) && !isset($options['wTimeoutMS'])) {
            $options['wTimeoutMS'] = $options['wtimeout'];
        }

        if (isset($options['w']) || !isset($options['wTimeoutMS'])) {
            $collectionWriteConcern = $this->getWriteConcern();
            $writeConcern = $this->createWriteConcernFromParameters(
                isset($options['w']) ? $options['w'] : $collectionWriteConcern['w'],
                isset($options['wTimeoutMS']) ? $options['wTimeoutMS'] : $collectionWriteConcern['wtimeout']
            );

            $options['writeConcern'] = $writeConcern;
        }

        unset($options['safe']);
        unset($options['w']);
        unset($options['wTimeout']);
        unset($options['wTimeoutMS']);

        return $options;
    }

    private function checkKeys(array $array)
    {
        foreach ($array as $key => $value) {
            if (empty($key) && $key !== 0 && $key !== '0') {
                throw new \MongoException('zero-length keys are not allowed, did you use $ with double quotes?');
            }

            if (is_object($value) || is_array($value)) {
                $this->checkKeys((array) $value);
            }
        }
    }

    /**
     * @param array|object $document
     * @return MongoId
     */
    private function ensureDocumentHasMongoId(&$document)
    {
        if (is_array($document) || $document instanceof ArrayObject) {
            if (! isset($document['_id'])) {
                $document['_id'] = new \MongoId();
            }

            $this->checkKeys((array) $document);

            return $document['_id'];
        } elseif (is_object($document)) {
            $reflectionObject = new \ReflectionObject($document);
            foreach ($reflectionObject->getProperties() as $property) {
                if (! $property->isPublic()) {
                    throw new \MongoException('zero-length keys are not allowed, did you use $ with double quotes?');
                }
            }

            if (! isset($document->_id)) {
                $document->_id = new \MongoId();
            }

            $this->checkKeys((array) $document);

            return $document->_id;
        }

        return null;
    }

    private function checkCollectionName($name)
    {
        if (empty($name)) {
            throw new Exception('Collection name cannot be empty');
        } elseif (strpos($name, chr(0)) !== false) {
            throw new Exception('Collection name cannot contain null bytes');
        }
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        return ['db', 'name'];
    }

    private function mustBeArrayOrObject($a)
    {
        if (!is_array($a) && !is_object($a)) {
            throw new \MongoException('document must be an array or object');
        }
    }
}
