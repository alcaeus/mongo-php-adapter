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

use Alcaeus\MongoDbAdapter\Helper;
use Alcaeus\MongoDbAdapter\TypeConverter;

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
    public $db = NULL;

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
     * @link http://www.php.net/manual/en/mongocollection.construct.php
     * @param MongoDB $db Parent database.
     * @param string $name Name for this collection.
     * @throws Exception
     * @return MongoCollection
     */
    public function __construct(MongoDB $db, $name)
    {
        $this->db = $db;
        $this->name = $name;

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
     * @link http://www.php.net/manual/en/mongocollection.--tostring.php
     * @return string Returns the full name of this collection.
     */
    public function __toString()
    {
        return (string) $this->db . '.' . $this->name;
    }

    /**
     * Gets a collection
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

        return $this->db->selectCollection($this->name . '.' . $name);
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
     * @link http://www.php.net/manual/en/mongocollection.aggregate.php
     * @param array $pipeline
     * @param array $op
     * @return array
     */
    public function aggregate(array $pipeline, array $op = [])
    {
        if (! TypeConverter::isNumericArray($pipeline)) {
            $pipeline = [];
            $options = [];

            $i = 0;
            foreach (func_get_args() as $operator) {
                $i++;
                if (! is_array($operator)) {
                    trigger_error("Argument $i is not an array", E_WARNING);
                    return;
                }

                $pipeline[] = $operator;
            }
        } else {
            $options = $op;
        }

        $command = [
            'aggregate' => $this->name,
            'pipeline' => $pipeline
        ];

        $command += $options;

        return $this->db->command($command, [], $hash);
    }

    /**
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
        if (! isset($options['cursor']) || $options['cursor'] === true || $options['cursor'] === []) {
            // Cursor option needs to be an object convert bools and empty arrays since those won't be handled by TypeConverter
            $options['cursor'] = new \stdClass;
        }

        $command += $options;

        $cursor = new MongoCommandCursor($this->db->getConnection(), (string)$this, $command);
        $cursor->setReadPreference($this->getReadPreference());

        return $cursor;
    }

    /**
     * Returns this collection's name
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
     * @link http://www.php.net/manual/en/mongocollection.drop.php
     * @return array Returns the database response.
     */
    public function drop()
    {
        return $this->collection->drop();
    }

    /**
     * Validates this collection
     * @link http://www.php.net/manual/en/mongocollection.validate.php
     * @param bool $scan_data Only validate indices, not the base collection.
     * @return array Returns the database's evaluation of this object.
     */
    public function validate($scan_data = FALSE)
    {
        $command = [
            'validate' => $this->name,
            'full'     => $scan_data,
        ];

        return $this->db->command($command, [], $hash);
    }

    /**
     * Inserts an array into the collection
     * @link http://www.php.net/manual/en/mongocollection.insert.php
     * @param array|object $a
     * @param array $options
     * @throws MongoException if the inserted document is empty or if it contains zero-length keys. Attempting to insert an object with protected and private properties will cause a zero-length key error.
     * @throws MongoCursorException if the "w" option is set and the write fails.
     * @throws MongoCursorTimeoutException if the "w" option is set to a value greater than one and the operation takes longer than MongoCursor::$timeout milliseconds to complete. This does not kill the operation on the server, it is a client-side timeout. The operation in MongoCollection::$wtimeout is milliseconds.
     * @return bool|array Returns an array containing the status of the insertion if the "w" option is set.
     */
    public function insert($a, array $options = array())
    {
        return $this->collection->insertOne(TypeConverter::convertLegacyArrayToObject($a), $options);
    }

    /**
     * Inserts multiple documents into this collection
     * @link http://www.php.net/manual/en/mongocollection.batchinsert.php
     * @param array $a An array of arrays.
     * @param array $options Options for the inserts.
     * @throws MongoCursorException
     * @return mixed f "safe" is set, returns an associative array with the status of the inserts ("ok") and any error that may have occured ("err"). Otherwise, returns TRUE if the batch insert was successfully sent, FALSE otherwise.
     */
    public function batchInsert(array $a, array $options = array())
    {
        return $this->collection->insertMany($a, $options);
    }

    /**
     * Update records based on a given criteria
     * @link http://www.php.net/manual/en/mongocollection.update.php
     * @param array $criteria Description of the objects to update.
     * @param array $newobj The object with which to update the matching records.
     * @param array $options This parameter is an associative array of the form
     *        array("optionname" => boolean, ...).
     *
     *        Currently supported options are:
     *          "upsert": If no document matches $$criteria, a new document will be created from $$criteria and $$new_object (see upsert example).
     *
     *          "multiple": All documents matching $criteria will be updated. MongoCollection::update has exactly the opposite behavior of MongoCollection::remove- it updates one document by
     *          default, not all matching documents. It is recommended that you always specify whether you want to update multiple documents or a single document, as the
     *          database may change its default behavior at some point in the future.
     *
     *          "safe" Can be a boolean or integer, defaults to false. If false, the program continues executing without waiting for a database response. If true, the program will wait for
     *          the database response and throw a MongoCursorException if the update did not succeed. If you are using replication and the master has changed, using "safe" will make the driver
     *          disconnect from the master, throw and exception, and attempt to find a new master on the next operation (your application must decide whether or not to retry the operation on the new master).
     *          If you do not use "safe" with a replica set and the master changes, there will be no way for the driver to know about the change so it will continuously and silently fail to write.
     *          If safe is an integer, will replicate the update to that many machines before returning success (or throw an exception if the replication times out, see wtimeout).
     *          This overrides the w variable set on the collection.
     *
     *         "fsync": Boolean, defaults to false. Forces the update to be synced to disk before returning success. If true, a safe update is implied and will override setting safe to false.
     *
     *         "timeout" Integer, defaults to MongoCursor::$timeout. If "safe" is set, this sets how long (in milliseconds) for the client to wait for a database response. If the database does
     *         not respond within the timeout period, a MongoCursorTimeoutException will be thrown
     * @throws MongoCursorException
     * @return boolean
     */
    public function update(array $criteria , array $newobj, array $options = array())
    {
        $multiple = ($options['multiple']) ? $options['multiple'] : false;
//        $multiple = $options['multiple'] ?? false;
        $method = $multiple ? 'updateMany' : 'updateOne';

        return $this->collection->$method($criteria, $newobj, $options);
    }

    /**
     * (PECL mongo &gt;= 0.9.0)<br/>
     * Remove records from this collection
     * @link http://www.php.net/manual/en/mongocollection.remove.php
     * @param array $criteria [optional] <p>Query criteria for the documents to delete.</p>
     * @param array $options [optional] <p>An array of options for the remove operation. Currently available options
     * include:
     * </p><ul>
     * <li><p><em>"w"</em></p><p>See {@link http://www.php.net/manual/en/mongo.writeconcerns.php Write Concerns}. The default value for <b>MongoClient</b> is <em>1</em>.</p></li>
     * <li>
     * <p>
     * <em>"justOne"</em>
     * </p>
     * <p>
     * Specify <strong><code>TRUE</code></strong> to limit deletion to just one document. If <strong><code>FALSE</code></strong> or
     * omitted, all documents matching the criteria will be deleted.
     * </p>
     * </li>
     * <li><p><em>"fsync"</em></p><p>Boolean, defaults to <b>FALSE</b>. If journaling is enabled, it works exactly like <em>"j"</em>. If journaling is not enabled, the write operation blocks until it is synced to database files on disk. If <strong><code>TRUE</code></strong>, an acknowledged insert is implied and this option will override setting <em>"w"</em> to <em>0</em>.</p><blockquote class="note"><p><strong class="note">Note</strong>: <span class="simpara">If journaling is enabled, users are strongly encouraged to use the <em>"j"</em> option instead of <em>"fsync"</em>. Do not use <em>"fsync"</em> and <em>"j"</em> simultaneously, as that will result in an error.</p></blockquote></li>
     * <li><p><em>"j"</em></p><p>Boolean, defaults to <b>FALSE</b>. Forces the write operation to block until it is synced to the journal on disk. If <strong><code>TRUE</code></strong>, an acknowledged write is implied and this option will override setting <em>"w"</em> to <em>0</em>.</p><blockquote class="note"><p><strong class="note">Note</strong>: <span class="simpara">If this option is used and journaling is disabled, MongoDB 2.6+ will raise an error and the write will fail; older server versions will simply ignore the option.</p></blockquote></li>
     * <li><p><em>"socketTimeoutMS"</em></p><p>This option specifies the time limit, in milliseconds, for socket communication. If the server does not respond within the timeout period, a <b>MongoCursorTimeoutException</b> will be thrown and there will be no way to determine if the server actually handled the write or not. A value of <em>-1</em> may be specified to block indefinitely. The default value for <b>MongoClient</b> is <em>30000</em> (30 seconds).</p></li>
     * <li><p><em>"w"</em></p><p>See {@link http://www.php.net/manual/en/mongo.writeconcerns.php Write Concerns }. The default value for <b>MongoClient</b> is <em>1</em>.</p></li>
     * <li><p><em>"wTimeoutMS"</em></p><p>This option specifies the time limit, in milliseconds, for {@link http://www.php.net/manual/en/mongo.writeconcerns.php write concern} acknowledgement. It is only applicable when <em>"w"</em> is greater than <em>1</em>, as the timeout pertains to replication. If the write concern is not satisfied within the time limit, a <a href="class.mongocursorexception.php" class="classname">MongoCursorException</a> will be thrown. A value of <em>0</em> may be specified to block indefinitely. The default value for {@link http://www.php.net/manual/en/class.mongoclient.php MongoClient} is <em>10000</em> (ten seconds).</p></li>
     * </ul>
     *
     * <p>
     * The following options are deprecated and should no longer be used:
     * </p><ul>
     * <li><p><em>"safe"</em></p><p>Deprecated. Please use the {@link http://www.php.net/manual/en/mongo.writeconcerns.php write concern} <em>"w"</em> option.</p></li>
     * <li><p><em>"timeout"</em></p><p>Deprecated alias for <em>"socketTimeoutMS"</em>.</p></li>
     * <li><p><b>"wtimeout"</b></p><p>Deprecated alias for <em>"wTimeoutMS"</em>.</p></p>
     * @throws MongoCursorException
     * @throws MongoCursorTimeoutException
     * @return bool|array <p>Returns an array containing the status of the removal if the
     * <em>"w"</em> option is set. Otherwise, returns <b>TRUE</b>.
     * </p>
     * <p>
     * Fields in the status array are described in the documentation for
     * <b>MongoCollection::insert()</b>.
     * </p>
     */
    public function remove(array $criteria = array(), array $options = array())
    {
        $multiple = isset($options['justOne']) ? !$options['justOne'] : false;
//        $multiple = !$options['justOne'] ?? false;
        $method = $multiple ? 'deleteMany' : 'deleteOne';

        return $this->collection->$method($criteria, $options);
    }

    /**
     * Querys this collection
     * @link http://www.php.net/manual/en/mongocollection.find.php
     * @param array $query The fields for which to search.
     * @param array $fields Fields of the results to return.
     * @return MongoCursor
     */
    public function find(array $query = array(), array $fields = array())
    {
        $cursor = new MongoCursor($this->db->getConnection(), (string)$this, $query, $fields);
        $cursor->setReadPreference($this->getReadPreference());

        return $cursor;
    }

    /**
     * Retrieve a list of distinct values for the given key across a collection
     * @link http://www.php.net/manual/ru/mongocollection.distinct.php
     * @param string $key The key to use.
     * @param array $query An optional query parameters
     * @return array|bool Returns an array of distinct values, or <b>FALSE</b> on failure
     */
    public function distinct($key, array $query = [])
    {
        return array_map([TypeConverter::class, 'convertToLegacyType'], $this->collection->distinct($key, $query));
    }

    /**
     * Update a document and return it
     * @link http://www.php.net/manual/ru/mongocollection.findandmodify.php
     * @param array $query The query criteria to search for.
     * @param array $update The update criteria.
     * @param array $fields Optionally only return these fields.
     * @param array $options An array of options to apply, such as remove the match document from the DB and return it.
     * @return array Returns the original document, or the modified document when new is set.
     */
    public function findAndModify(array $query, array $update = NULL, array $fields = NULL, array $options = [])
    {
        $query = TypeConverter::convertLegacyArrayToObject($query);
        if (isset($options['remove'])) {
            unset($options['remove']);
            $document = $this->collection->findOneAndDelete($query, $options);
        } else {
            if (is_array($update)) {
                $update = TypeConverter::convertLegacyArrayToObject($update);
            }
            if (is_array($fields)) {
                $fields = TypeConverter::convertLegacyArrayToObject($fields);
            }
            if (isset($options['new'])) {
                $options['returnDocument'] = \MongoDB\Operation\FindOneAndUpdate::RETURN_DOCUMENT_AFTER;
                unset($options['new']);
            }
            if ($fields) {
                $options['projection'] = $fields;
            }
            $document = $this->collection->findOneAndUpdate($query, $update, $options);
        }
        if ($document) {
            $document = TypeConverter::convertObjectToLegacyArray($document);
        }
        return $document;
    }

    /**
     * Querys this collection, returning a single element
     * @link http://www.php.net/manual/en/mongocollection.findone.php
     * @param array $query The fields for which to search.
     * @param array $fields Fields of the results to return.
     * @return array|null
     */
    public function findOne(array $query = array(), array $fields = array())
    {
        $document = $this->collection->findOne(TypeConverter::convertLegacyArrayToObject($query), ['projection' => $fields]);
        if ($document !== null) {
            $document = TypeConverter::convertObjectToLegacyArray($document);
        }

        return $document;
    }

    /**
     * Creates an index on the given field(s), or does nothing if the index already exists
     * @link http://www.php.net/manual/en/mongocollection.createindex.php
     * @param array $keys Field or fields to use as index.
     * @param array $options [optional] This parameter is an associative array of the form array("optionname" => <boolean>, ...).
     * @return array Returns the database response.
     *
     * @todo This method does not yet return the correct result
     */
    public function createIndex(array $keys, array $options = [])
    {
        // Note: this is what the result array should look like
//        $expected = [
//            'createdCollectionAutomatically' => true,
//            'numIndexesBefore' => 1,
//            'numIndexesAfter' => 2,
//            'ok' => 1.0
//        ];

        return $this->collection->createIndex($keys, $options);
    }

    /**
     * @deprecated Use MongoCollection::createIndex() instead.
     * Creates an index on the given field(s), or does nothing if the index already exists
     * @link http://www.php.net/manual/en/mongocollection.ensureindex.php
     * @param array $keys Field or fields to use as index.
     * @param array $options [optional] This parameter is an associative array of the form array("optionname" => <boolean>, ...).
     * @return boolean always true
     */
    public function ensureIndex(array $keys, array $options = array())
    {
        $this->createIndex($keys, $options);
        return true;
    }

    /**
     * Deletes an index from this collection
     * @link http://www.php.net/manual/en/mongocollection.deleteindex.php
     * @param string|array $keys Field or fields from which to delete the index.
     * @return array Returns the database response.
     */
    public function deleteIndex($keys)
    {
        if (is_string($keys)) {
            $indexName = $keys;
        } elseif (is_array($keys)) {
            $indexName = self::toIndexString($keys);
        } else {
            throw new \InvalidArgumentException();
        }

        return TypeConverter::convertObjectToLegacyArray($this->collection->dropIndex($indexName));
    }

    /**
     * Delete all indexes for this collection
     * @link http://www.php.net/manual/en/mongocollection.deleteindexes.php
     * @return array Returns the database response.
     */
    public function deleteIndexes()
    {
        return TypeConverter::convertObjectToLegacyArray($this->collection->dropIndexes());
    }

    /**
     * Returns an array of index names for this collection
     * @link http://www.php.net/manual/en/mongocollection.getindexinfo.php
     * @return array Returns a list of index names.
     */
    public function getIndexInfo()
    {
        $convertIndex = function($indexInfo) {
            return $indexInfo->__debugInfo();
        };
        return array_map($convertIndex, iterator_to_array($this->collection->listIndexes()));
    }

    /**
     * Counts the number of documents in this collection
     * @link http://www.php.net/manual/en/mongocollection.count.php
     * @param array|stdClass $query
     * @return int Returns the number of documents matching the query.
     */
    public function count($query = array())
    {
        return $this->collection->count($query);
    }

    /**
     * Saves an object to this collection
     * @link http://www.php.net/manual/en/mongocollection.save.php
     * @param array|object $a Array to save. If an object is used, it may not have protected or private properties.
     * Note: If the parameter does not have an _id key or property, a new MongoId instance will be created and assigned to it.
     * See MongoCollection::insert() for additional information on this behavior.
     * @param array $options Options for the save.
     * <dl>
     * <dt>"w"
     * <dd>See WriteConcerns. The default value for MongoClient is 1.
     * <dt>"fsync"
     * <dd>Boolean, defaults to FALSE. Forces the insert to be synced to disk before returning success. If TRUE, an acknowledged insert is implied and will override setting w to 0.
     * <dt>"timeout"
     * <dd>Integer, defaults to MongoCursor::$timeout. If "safe" is set, this sets how long (in milliseconds) for the client to wait for a database response. If the database does not respond within the timeout period, a MongoCursorTimeoutException will be thrown.
     * <dt>"safe"
     * <dd>Deprecated. Please use the WriteConcern w option.
     * </dl>
     * @throws MongoException if the inserted document is empty or if it contains zero-length keys. Attempting to insert an object with protected and private properties will cause a zero-length key error.
     * @throws MongoCursorException if the "w" option is set and the write fails.
     * @throws MongoCursorTimeoutException if the "w" option is set to a value greater than one and the operation takes longer than MongoCursor::$timeout milliseconds to complete. This does not kill the operation on the server, it is a client-side timeout. The operation in MongoCollection::$wtimeout is milliseconds.
     * @return array|boolean If w was set, returns an array containing the status of the save.
     * Otherwise, returns a boolean representing if the array was not empty (an empty array will not be inserted).
     */
    public function save($a, array $options = array())
    {
        if (is_object($a)) {
            $a = (array)$a;
        }
        if ( ! array_key_exists('_id', $a)) {
            $id = new \MongoId();
        } else {
            $id = $a['_id'];
            unset($a['_id']);
        }
        $filter = ['_id' => $id];
        $filter = TypeConverter::convertLegacyArrayToObject($filter);
        $a = TypeConverter::convertLegacyArrayToObject($a);
        return $this->collection->updateOne($filter, ['$set' => $a], ['upsert' => true]);
    }

    /**
     * Creates a database reference
     * @link http://www.php.net/manual/en/mongocollection.createdbref.php
     * @param array $a Object to which to create a reference.
     * @return array Returns a database reference array.
     */
    public function createDBRef(array $a)
    {
        return \MongoDBRef::create($this->name, $a['_id']);
    }

    /**
     * Fetches the document pointed to by a database reference
     * @link http://www.php.net/manual/en/mongocollection.getdbref.php
     * @param array $ref A database reference.
     * @return array Returns the database document pointed to by the reference.
     */
    public function getDBRef(array $ref)
    {
        return \MongoDBRef::get($this->db, $ref);
    }

    /**
     * @param  mixed $keys
     * @static
     * @return string
     */
    protected static function toIndexString($keys)
    {
        $result = '';
        foreach ($keys as $name => $direction) {
            $result .= sprintf('%s_%d', $name, $direction);
        }
        return $result;
    }

    /**
     * Performs an operation similar to SQL's GROUP BY command
     * @link http://www.php.net/manual/en/mongocollection.group.php
     * @param mixed $keys Fields to group by. If an array or non-code object is passed, it will be the key used to group results.
     * @param array $initial Initial value of the aggregation counter object.
     * @param MongoCode $reduce A function that aggregates (reduces) the objects iterated.
     * @param array $condition An condition that must be true for a row to be considered.
     * @return array
     */
    public function group($keys, array $initial, $reduce, array $condition = [])
    {
        if (is_string($reduce)) {
            $reduce = new MongoCode($reduce);
        }
        if ( ! $reduce instanceof MongoCode) {
            throw new \InvalidArgumentExcption('reduce parameter should be a string or MongoCode instance.');
        }
        $command = [
            'group' => [
                'ns'      => $this->name,
                '$reduce' => (string)$reduce,
                'initial' => $initial,
                'cond'    => $condition,
            ],
        ];

        if ($keys instanceof MongoCode) {
            $command['group']['$keyf'] = (string)$keys;
        } else {
            $command['group']['key'] = $keys;
        }
        if (array_key_exists('condition', $condition)) {
            $command['group']['cond'] = $condition['condition'];
        }
        if (array_key_exists('finalize', $condition)) {
            if ($condition['finalize'] instanceof MongoCode) {
                $condition['finalize'] = (string)$condition['finalize'];
            }
            $command['group']['finalize'] = $condition['finalize'];
        }

        return $this->db->command($command, [], $hash);
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
}

