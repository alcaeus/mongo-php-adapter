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

use MongoDB\Model\CollectionInfo;

/**
 * Instances of this class are used to interact with a database.
 * @link http://www.php.net/manual/en/class.mongodb.php
 */
class MongoDB
{
    const PROFILING_OFF = 0;
    const PROFILING_SLOW = 1;
    const PROFILING_ON = 2;

    /**
     * @var int
     */
    public $w = 1;

    /**
     * @var int
     */
    public $wtimeout = 10000;

    /**
     * @var \MongoDB\Database
     */
    protected $db;

    /**
     * Creates a new database
     *
     * This method is not meant to be called directly. The preferred way to create an instance of MongoDB is through {@see Mongo::__get()} or {@see Mongo::selectDB()}.
     * @link http://www.php.net/manual/en/mongodb.construct.php
     * @param MongoClient $conn Database connection.
     * @param string $name Database name.
     * @throws Exception
     * @return MongoDB Returns the database.
     */
    public function __construct($conn, $name)
    {
        $this->connection = $conn;
        $this->name = $name;
        $this->db = $this->connection->getClient()->selectDatabase($name);
    }

    /**
     * @return \MongoDB\Database
     * @internal
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * The name of this database
     * @link http://www.php.net/manual/en/mongodb.--tostring.php
     * @return string Returns this database's name.
     */
    public function __toString()
    {
        return $this->name;
    }

    /**
     * (PECL mongo &gt;= 1.0.2)<br/>
     * Gets a collection
     * @link http://www.php.net/manual/en/mongodb.get.php
     * @param string $name The name of the collection.
     * @return MongoCollection
     */
    public function __get($name)
    {
        return $this->selectCollection($name);
    }

    /**
     * (PECL mongo &gt;= 1.3.0)<br/>
     * @link http://www.php.net/manual/en/mongodb.getcollectionnames.php
     * Get all collections from this database
     * @return array Returns the names of the all the collections in the database as an
     * {@link http://www.php.net/manual/en/language.types.array.php array}.
     */
    public function getCollectionNames(array $options = [])
    {
        if (is_bool($options)) {
            $options = ['includeSystemCollections' => $options];
        }

        $collections = $this->db->listCollections($options);

        $getCollectionName = function (CollectionInfo $collectionInfo) {
            return $collectionInfo->getName();
        };

        return array_map($getCollectionName, (array) $collections);
    }

    /**
     * @return MongoClient
     * @internal This method is not part of the ext-mongo API
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * (PECL mongo &gt;= 0.9.0)<br/>
     * Fetches toolkit for dealing with files stored in this database
     * @link http://www.php.net/manual/en/mongodb.getgridfs.php
     * @param string $prefix [optional] The prefix for the files and chunks collections.
     * @return MongoGridFS Returns a new gridfs object for this database.
     */
    public function getGridFS($prefix = "fs")
    {
        return new \MongoGridFS($this, $prefix, $prefix);
    }

    /**
     * (PECL mongo &gt;= 0.9.0)<br/>
     * Gets this database's profiling level
     * @link http://www.php.net/manual/en/mongodb.getprofilinglevel.php
     * @return int Returns the profiling level.
     */
    public function getProfilingLevel()
    {
        return static::PROFILING_OFF;
    }

    /**
     * (PECL mongo &gt;= 1.1.0)<br/>
     * Get slaveOkay setting for this database
     * @link http://www.php.net/manual/en/mongodb.getslaveokay.php
     * @return bool Returns the value of slaveOkay for this instance.
     */
    public function getSlaveOkay()
    {
        return false;
    }

    /**
     * (PECL mongo &gt;= 0.9.0)<br/>
     * Sets this database's profiling level
     * @link http://www.php.net/manual/en/mongodb.setprofilinglevel.php
     * @param int $level Profiling level.
     * @return int Returns the previous profiling level.
     */
    public function setProfilingLevel($level)
    {
        return static::PROFILING_OFF;
    }

    /**
     * (PECL mongo &gt;= 0.9.0)<br/>
     * Drops this database
     * @link http://www.php.net/manual/en/mongodb.drop.php
     * @return array Returns the database response.
     */
    public function drop()
    {
        return $this->db->drop();
    }

    /**
     * Repairs and compacts this database
     * @link http://www.php.net/manual/en/mongodb.repair.php
     * @param bool $preserve_cloned_files [optional] <p>If cloned files should be kept if the repair fails.</p>
     * @param bool $backup_original_files [optional] <p>If original files should be backed up.</p>
     * @return array <p>Returns db response.</p>
     */
    public function repair($preserve_cloned_files = FALSE, $backup_original_files = FALSE)
    {
        return [];
    }

    /**
     * (PECL mongo &gt;= 0.9.0)<br/>
     * Gets a collection
     * @link http://www.php.net/manual/en/mongodb.selectcollection.php
     * @param string $name <b>The collection name.</b>
     * @throws Exception if the collection name is invalid.
     * @return MongoCollection <p>
     * Returns a new collection object.
     * </p>
     */
    public function selectCollection($name)
    {
        return new MongoCollection($this, $name);
    }

    /**
     * (PECL mongo &gt;= 1.1.0)<br/>
     * Change slaveOkay setting for this database
     * @link http://php.net/manual/en/mongodb.setslaveokay.php
     * @param bool $ok [optional] <p>
     * If reads should be sent to secondary members of a replica set for all
     * possible queries using this {@link http://www.php.net/manual/en/class.mongodb.php MongoDB} instance.
     * </p>
     * @return bool Returns the former value of slaveOkay for this instance.
     */
    public function setSlaveOkay ($ok = true)
    {
        return false;
    }

    /**
     * Creates a collection
     * @link http://www.php.net/manual/en/mongodb.createcollection.php
     * @param string $name The name of the collection.
     * @param array $options [optional] <p>
     * <p>
     * An array containing options for the collections. Each option is its own
     * element in the options array, with the option name listed below being
     * the key of the element. The supported options depend on the MongoDB
     * server version. At the moment, the following options are supported:
     * </p>
     * <p>
     * <b>capped</b>
     * <p>
     * If the collection should be a fixed size.
     * </p>
     * </p>
     * <p>
     * <b>size</b>
     * <p>
     * If the collection is fixed size, its size in bytes.</p></p>
     * <p><b>max</b>
     * <p>If the collection is fixed size, the maximum number of elements to store in the collection.</p></p>
     * <i>autoIndexId</i>
     *
     * <p>
     * If capped is <b>TRUE</b> you can specify <b>FALSE</b> to disable the
     * automatic index created on the <em>_id</em> field.
     * Before MongoDB 2.2, the default value for
     * <em>autoIndexId</em> was <b>FALSE</b>.
     * </p>
     * </p>
     * @return MongoCollection <p>Returns a collection object representing the new collection.</p>
     */
    public function createCollection($name, $options)
    {
        $this->db->createCollection($name, $options);
        return $this->selectCollection($name);
    }

    /**
     * (PECL mongo &gt;= 0.9.0)<br/>
     * @deprecated Use MongoCollection::drop() instead.
     * Drops a collection
     * @link http://www.php.net/manual/en/mongodb.dropcollection.php
     * @param MongoCollection|string $coll MongoCollection or name of collection to drop.
     * @return array Returns the database response.
     */
    public function dropCollection($coll)
    {
        return $this->db->dropCollection((string) $coll);
    }

    /**
     * (PECL mongo &gt;= 0.9.0)<br/>
     * Get a list of collections in this database
     * @link http://www.php.net/manual/en/mongodb.listcollections.php
     * @param bool $includeSystemCollections [optional] <p>Include system collections.</p>
     * @return array Returns a list of MongoCollections.
     */
    public function listCollections(array $options = [])
    {
        return array_map([$this, 'selectCollection'], $this->getCollectionNames($options));
    }

    /**
     * (PECL mongo &gt;= 0.9.0)<br/>
     * Creates a database reference
     * @link http://www.php.net/manual/en/mongodb.createdbref.php
     * @param string $collection The collection to which the database reference will point.
     * @param mixed $document_or_id <p>
     * If an array or object is given, its <em>_id</em> field will be
     * used as the reference ID. If a {@see MongoId} or scalar
     * is given, it will be used as the reference ID.
     * </p>
     * @return array <p>Returns a database reference array.</p>
     * <p>
     * If an array without an <em>_id</em> field was provided as the
     * <em>document_or_id</em> parameter, <b>NULL</b> will be returned.
     * </p>
     */
    public function createDBRef($collection, $document_or_id)
    {
        if (is_object($document_or_id)) {
            $id = isset($document_or_id->_id) ? $document_or_id->_id : null;
//            $id = $document_or_id->_id ?? null;
        } elseif (is_array($document_or_id)) {
            if (! isset($document_or_id['_id'])) {
                return null;
            }

            $id = $document_or_id['_id'];
        } else {
            $id = $document_or_id;
        }

        return [
            '$ref' => $collection,
            '$id' => $id,
            '$db' => $this->name,
        ];
    }


    /**
     * (PECL mongo &gt;= 0.9.0)<br/>
     * Fetches the document pointed to by a database reference
     * @link http://www.php.net/manual/en/mongodb.getdbref.php
     * @param array $ref A database reference.
     * @return array Returns the document pointed to by the reference.
     */
    public function getDBRef(array $ref)
    {
        $this->notImplemented();
    }

    /**
     * (PECL mongo &gt;= 1.5.0)<br/>
     * Get the write concern for this database
     * @link http://php.net/manual/en/mongodb.getwriteconcern.php
     * @return array <p>This function returns an array describing the write concern.
     * The array contains the values w for an integer acknowledgement level or string mode,
     * and wtimeout denoting the maximum number of milliseconds to wait for the server to satisfy the write concern.</p>
     */
    public function getWriteConcern()
    {
        $this->notImplemented();
    }

    /**
     * (PECL mongo &gt;= 0.9.3)<br/>
     * Runs JavaScript code on the database server.
     * @link http://www.php.net/manual/en/mongodb.execute.php
     * @param MongoCode|string $code Code to execute.
     * @param array $args [optional] Arguments to be passed to code.
     * @return array Returns the result of the evaluation.
     */
    public function execute($code, array $args = array())
    {
        $this->notImplemented();
    }

    /**
     * Execute a database command
     * @link http://www.php.net/manual/en/mongodb.command.php
     * @param array $data The query to send.
     * @param array() $options [optional] <p>
     * This parameter is an associative array of the form
     * <em>array("optionname" =&gt; &lt;boolean&gt;, ...)</em>. Currently
     * supported options are:
     * </p><ul>
     * <li><p><em>"timeout"</em></p><p>Deprecated alias for <em>"socketTimeoutMS"</em>.</p></li>
     * </ul>
     * @return array Returns database response.
     * Every database response is always maximum one document,
     * which means that the result of a database command can never exceed 16MB.
     * The resulting document's structure depends on the command,
     * but most results will have the ok field to indicate success or failure and results containing an array of each of the resulting documents.
     */
    public function command(array $data, $options, &$hash)
    {
        try {
            $cursor = new \MongoCommandCursor($this->connection, $this->name, $data);

            return iterator_to_array($cursor)[0];
        } catch (\MongoDB\Driver\Exception\RuntimeException $e) {
            return [
                'ok' => 0,
                'errmsg' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }

    /**
     * (PECL mongo &gt;= 0.9.5)<br/>
     * Check if there was an error on the most recent db operation performed
     * @link http://www.php.net/manual/en/mongodb.lasterror.php
     * @return array Returns the error, if there was one.
     */
    public function lastError()
    {
        $this->notImplemented();
    }

    /**
     * (PECL mongo &gt;= 0.9.5)<br/>
     * Checks for the last error thrown during a database operation
     * @link http://www.php.net/manual/en/mongodb.preverror.php
     * @return array Returns the error and the number of operations ago it occurred.
     */
    public function prevError()
    {
        $this->notImplemented();
    }

    /**
     * (PECL mongo &gt;= 0.9.5)<br/>
     * Clears any flagged errors on the database
     * @link http://www.php.net/manual/en/mongodb.reseterror.php
     * @return array Returns the database response.
     */
    public function resetError()
    {
        $this->notImplemented();
    }

    /**
     * (PECL mongo &gt;= 0.9.5)<br/>
     * Creates a database error
     * @link http://www.php.net/manual/en/mongodb.forceerror.php
     * @return boolean Returns the database response.
     */
    public function forceError()
    {
        $this->notImplemented();
    }

    /**
     * (PECL mongo &gt;= 1.0.1)<br/>
     * Log in to this database
     * @link http://www.php.net/manual/en/mongodb.authenticate.php
     * @param string $username The username.
     * @param string $password The password (in plaintext).
     * @return array <p>Returns database response. If the login was successful, it will return 1.</p>
     * <p>
     * <span style="color: #0000BB">&lt;?php<br></span><span style="color: #007700">array(</span><span style="color: #DD0000">"ok"&nbsp;</span><span style="color: #007700">=&gt;&nbsp;</span><span style="color: #0000BB">1</span><span style="color: #007700">);<br></span><span style="color: #0000BB">?&gt;</span>
     * </span>
     * </code></div>
     * </div>
     * </p>
     * <p> If something went wrong, it will return </p>
     * <p>
     * <div class="example-contents">
     * <div class="phpcode"><code><span style="color: #000000">
     * <span style="color: #0000BB">&lt;?php<br></span><span style="color: #007700">array(</span><span style="color: #DD0000">"ok"&nbsp;</span><span style="color: #007700">=&gt;&nbsp;</span><span style="color: #0000BB">0</span><span style="color: #007700">,&nbsp;</span><span style="color: #DD0000">"errmsg"&nbsp;</span><span style="color: #007700">=&gt;&nbsp;</span><span style="color: #DD0000">"auth&nbsp;fails"</span><span style="color: #007700">);<br></span><span style="color: #0000BB">?&gt;</span></p>
     *         <p>("auth fails" could be another message, depending on database version and
     *         what went wrong)</p>
     */
    public function authenticate($username, $password)
    {
        $this->notImplemented();
    }

    /**
     * (PECL mongo &gt;= 1.3.0)<br/>
     * Get the read preference for this database
     * @link http://www.php.net/manual/en/mongodb.getreadpreference.php
     * @return array This function returns an array describing the read preference. The array contains the values type for the string read preference mode (corresponding to the MongoClient constants), and tagsets containing a list of all tag set criteria. If no tag sets were specified, tagsets will not be present in the array.
     */
    public function getReadPreference()
    {
        $this->notImplemented();
    }

    /**
     * (PECL mongo &gt;= 1.3.0)<br/>
     * Set the read preference for this database
     * @link http://www.php.net/manual/en/mongodb.setreadpreference.php
     * @param string $read_preference <p>The read preference mode: <b>MongoClient::RP_PRIMARY</b>, <b>MongoClient::RP_PRIMARY_PREFERRED</b>, <b>MongoClient::RP_SECONDARY</b>, <b>MongoClient::RP_SECONDARY_PREFERRED</b>, or <b>MongoClient::RP_NEAREST</b>.</p>
     * @param array $tags [optional] <p>An array of zero or more tag sets, where each tag set is itself an array of criteria used to match tags on replica set members.</p>
     * @return boolean Returns <b>TRUE</b> on success, or <b>FALSE</b> otherwise.
     */
    public function setReadPreference($read_preference, array $tags)
    {
        $this->notImplemented();
    }

    /**
     * (PECL mongo &gt;= 1.5.0)<br/>
     * @link http://php.net/manual/en/mongodb.setwriteconcern.php
     * Set the write concern for this database
     * @param mixed $w <p>The write concern. This may be an integer denoting the number of servers required to acknowledge the write, or a string mode (e.g. "majority").</p>
     * @param int $wtimeout[optional] <p>The maximum number of milliseconds to wait for the server to satisfy the write concern.</p>
     * @return boolean Returns <b>TRUE</b> on success, or <b>FALSE</b> otherwise.
     */
    public function setWriteConcern($w, $wtimeout)
    {
        $this->notImplemented();
    }

    protected function notImplemented()
    {
        throw new \Exception('Not implemented');
    }
}
