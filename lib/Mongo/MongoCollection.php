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

use Alcaeus\MongoDbAdapter\TypeConverter;

/**
 * Represents a database collection.
 * @link http://www.php.net/manual/en/class.mongocollection.php
 */
class MongoCollection
{
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
     * @var int<p>
     */
    public $w;

    /**
     * @var int <p>
     */
    public $wtimeout;

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
        $this->collection = $this->db->getDb()->selectCollection($name);
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
        return $this->db->selectCollection($this->name . '.' . $name);
    }

    /**
     * (PECL mongo &gt;= 1.3.0)<br/>
     * <p>
     * The MongoDB
     * {@link http://docs.mongodb.org/manual/applications/aggregation/ aggregation framework}
     * provides a means to calculate aggregated values without having to use
     * MapReduce. While MapReduce is powerful, it is often more difficult than
     * necessary for many simple aggregation tasks, such as totaling or averaging
     * field values.
     * </p>
     * <p>
     * This method accepts either a variable amount of pipeline operators, or a
     * single array of operators constituting the pipeline.
     * </p>
     * @link http://www.php.net/manual/en/mongocollection.aggregate.php
     * @param array $pipeline <p> An array of pipeline operators, or just the first operator. </p>
     * @param array $op [optional] <p> The second pipeline operator.</p>
     * @param array $pipelineOperators [optional] <p> Additional pipeline operators. </p>
     * @return array The result of the aggregation as an array. The ok will be set to 1 on success, 0 on failure.
     */
    public function aggregate(array $pipeline, array $op, array $pipelineOperators)
    {
//        return $this->collection
    }

    /**
     * (PECL mongo &gt;= 1.5.0)<br/>
     *
     * <p>
     * With this method you can execute Aggregation Framework pipelines and retrieve the results
     * through a cursor, instead of getting just one document back as you would with
     * {@link http://php.net/manual/en/mongocollection.aggregate.php MongoCollection::aggregate()}.
     * This method returns a {@link http://php.net/manual/en/class.mongocommandcursor.php MongoCommandCursor} object.
     * This cursor object implements the {@link http://php.net/manual/en/class.iterator.php Iterator} interface
     * just like the {@link http://php.net/manual/en/class.mongocursor.php MongoCursor} objects that are returned
     * by the {@link http://php.net/manual/en/mongocollection.find.php MongoCollection::find()} method
     * </p>
     *
     * @link http://php.net/manual/en/mongocollection.aggregatecursor.php
     *
     * @param array $pipeline          <p> The Aggregation Framework pipeline to execute. </p>
     * @param array $options            [optional] <p> Options for the aggregation command </p>
     *
     * @return MongoCommandCursor Returns a {@link http://php.net/manual/en/class.mongocommandcursor.php MongoCommandCursor} object
     */
    public function aggregateCursor(array $pipeline, array $options)
    {
        return $this->collection->aggregate($pipeline, $options);
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
     * (PECL mongo &gt;= 1.1.0)<br/>
     * <p>
     * See {@link http://www.php.net/manual/en/mongo.queries.php the query section} of this manual for
     * information on distributing reads to secondaries.
     * </p>
     * @link http://www.php.net/manual/en/mongocollection.getslaveokay.php
     * @return bool Returns the value of slaveOkay for this instance.
     */
    public function getSlaveOkay()
    {
        $this->notImplemented();
    }

    /**
     * (PECL mongo &gt;= 1.1.0)<br/>
     * <p>
     * See {@link http://www.php.net/manual/en/mongo.queries.php the query section} of this manual for
     * information on distributing reads to secondaries.
     * </p>
     * @link http://www.php.net/manual/en/mongocollection.setslaveokay.php
     * @param bool $ok [optional] <p>
     * If reads should be sent to secondary members of a replica set for all
     * possible queries using this {@link http://www.php.net/manual/en/class.mongocollection.php MongoCollection}
     * instance.
     * @return bool Returns the former value of slaveOkay for this instance.
     * </p>
     */
    public function setSlaveOkay($ok = true)
    {
        $this->notImplemented();
    }

    /**
     * (PECL mongo &gt;= 1.3.0)<br/>
     * @link http://www.php.net/manual/en/mongocollection.getreadpreference.php
     * @return array This function returns an array describing the read preference. The array contains the values <em>type</em> for the string read preference mode
     * (corresponding to the {@link http://www.php.net/manual/en/class.mongoclient.php MongoClient} constants), and <em>tagsets</em> containing a list of all tag set criteria. If no tag sets were specified, <em>tagsets</em> will not be present in the array.
     */
    public function getReadPreference()
    {
        $this->notImplemented();
    }

    /**
     * (PECL mongo &gt;= 1.3.0)<br/>
     * @param string $read_preference <p>The read preference mode: <b>MongoClient::RP_PRIMARY</b>, <b>MongoClient::RP_PRIMARY_PREFERRED</b>, <b>MongoClient::RP_SECONDARY</b>, <b>MongoClient::RP_SECONDARY_PREFERRED</b>, or <b>MongoClient::RP_NEAREST</b>.</p>
     * @param array $tags [optional] <p>An array of zero or more tag sets, where each tag set is itself an array of criteria used to match tags on replica set members.<p>
     * @return bool Returns <b>TRUE</b> on success, or <b>FALSE</b> otherwise.
     */
    public function setReadPreference($read_preference, array $tags)
    {
        $this->notImplemented();
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
        $this->notImplemented();
    }

    /**
     * Inserts an array into the collection
     * @link http://www.php.net/manual/en/mongocollection.insert.php
     * @param array|object $a An array or object. If an object is used, it may not have protected or private properties.
     * Note: If the parameter does not have an _id key or property, a new MongoId instance will be created and assigned to it.
     * This special behavior does not mean that the parameter is passed by reference.
     * @param array $options Options for the insert.
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
     * @return bool|array Returns an array containing the status of the insertion if the "w" option is set.
     * Otherwise, returns TRUE if the inserted array is not empty (a MongoException will be thrown if the inserted array is empty).
     * If an array is returned, the following keys may be present:
     * <dl>
     * <dt>ok
     * <dd>This should almost be 1 (unless last_error itself failed).
     * <dt>err
     * <dd>If this field is non-null, an error occurred on the previous operation. If this field is set, it will be a string describing the error that occurred.
     * <dt>code
     * <dd>If a database error occurred, the relevant error code will be passed back to the client.
     * <dt>errmsg
     * <dd>This field is set if something goes wrong with a database command. It is coupled with ok being 0. For example, if w is set and times out, errmsg will be set to "timed out waiting for slaves" and ok will be 0. If this field is set, it will be a string describing the error that occurred.
     * <dt>n
     * <dd>If the last operation was an update, upsert, or a remove, the number of documents affected will be returned. For insert operations, this value is always 0.
     * <dt>wtimeout
     * <dd>If the previous option timed out waiting for replication.
     * <dt>waited
     * <dd>How long the operation waited before timing out.
     * <dt>wtime
     * <dd>If w was set and the operation succeeded, how long it took to replicate to w servers.
     * <dt>upserted
     * <dd>If an upsert occurred, this field will contain the new record's _id field. For upserts, either this field or updatedExisting will be present (unless an error occurred).
     * <dt>updatedExisting
     * <dd>If an upsert updated an existing element, this field will be true. For upserts, either this field or upserted will be present (unless an error occurred).
     * </dl>
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
    public function find(array $query = array(), array $fields = array()) {}

    /**
     * Retrieve a list of distinct values for the given key across a collection
     * @link http://www.php.net/manual/ru/mongocollection.distinct.php
     * @param string $key The key to use.
     * @param array $query An optional query parameters
     * @return array|bool Returns an array of distinct values, or <b>FALSE</b> on failure
     */
    public function distinct ($key, array $query = NULL) {}

    /**
     * Update a document and return it
     * @link http://www.php.net/manual/ru/mongocollection.findandmodify.php
     * @param array $query The query criteria to search for.
     * @param array $update The update criteria.
     * @param array $fields Optionally only return these fields.
     * @param array $options An array of options to apply, such as remove the match document from the DB and return it.
     * @return array Returns the original document, or the modified document when new is set.
     */
    public function findAndModify (array $query, array $update = NULL, array $fields = NULL, array $options = NULL) {}

    /**
     * Querys this collection, returning a single element
     * @link http://www.php.net/manual/en/mongocollection.findone.php
     * @param array $query The fields for which to search.
     * @param array $fields Fields of the results to return.
     * @return array|null
     */
    public function findOne(array $query = array(), array $fields = array()) {}

    /**
     * Creates an index on the given field(s), or does nothing if the index already exists
     * @link http://www.php.net/manual/en/mongocollection.createindex.php
     * @param array $keys Field or fields to use as index.
     * @param array $options [optional] This parameter is an associative array of the form array("optionname" => <boolean>, ...).
     * @return array Returns the database response.
     */
    public function createIndex(array $keys, array $options = array()) {}

    /**
     * @deprecated Use MongoCollection::createIndex() instead.
     * Creates an index on the given field(s), or does nothing if the index already exists
     * @link http://www.php.net/manual/en/mongocollection.ensureindex.php
     * @param array $keys Field or fields to use as index.
     * @param array $options [optional] This parameter is an associative array of the form array("optionname" => <boolean>, ...).
     * @return boolean always true
     */
    public function ensureIndex(array $keys, array $options = array()) {}

    /**
     * Deletes an index from this collection
     * @link http://www.php.net/manual/en/mongocollection.deleteindex.php
     * @param string|array $keys Field or fields from which to delete the index.
     * @return array Returns the database response.
     */
    public function deleteIndex($keys) {}

    /**
     * Delete all indexes for this collection
     * @link http://www.php.net/manual/en/mongocollection.deleteindexes.php
     * @return array Returns the database response.
     */
    public function deleteIndexes() {}

    /**
     * Returns an array of index names for this collection
     * @link http://www.php.net/manual/en/mongocollection.getindexinfo.php
     * @return array Returns a list of index names.
     */
    public function getIndexInfo() {}

    /**
     * Counts the number of documents in this collection
     * @link http://www.php.net/manual/en/mongocollection.count.php
     * @param array|stdClass $query
     * @return int Returns the number of documents matching the query.
     */
    public function count($query = array()) {}

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
    public function save($a, array $options = array()) {}

    /**
     * Creates a database reference
     * @link http://www.php.net/manual/en/mongocollection.createdbref.php
     * @param array $a Object to which to create a reference.
     * @return array Returns a database reference array.
     */
    public function createDBRef(array $a) {}

    /**
     * Fetches the document pointed to by a database reference
     * @link http://www.php.net/manual/en/mongocollection.getdbref.php
     * @param array $ref A database reference.
     * @return array Returns the database document pointed to by the reference.
     */
    public function getDBRef(array $ref) {}

    /**
     * @param  mixed $keys
     * @static
     * @return string
     */
    protected static function toIndexString($keys) {}

    /**
     * Performs an operation similar to SQL's GROUP BY command
     * @link http://www.php.net/manual/en/mongocollection.group.php
     * @param mixed $keys Fields to group by. If an array or non-code object is passed, it will be the key used to group results.
     * @param array $initial Initial value of the aggregation counter object.
     * @param MongoCode $reduce A function that aggregates (reduces) the objects iterated.
     * @param array $condition An condition that must be true for a row to be considered.
     * @return array
     */
    public function group($keys, array $initial, MongoCode $reduce, array $condition = array()) {}

    protected function notImplemented()
    {
        throw new \Exception('Not implemented');
    }
}

