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

if (class_exists('MongoDB', false)) {
    return;
}

use Alcaeus\MongoDbAdapter\Helper;
use Alcaeus\MongoDbAdapter\TypeConverter;
use Alcaeus\MongoDbAdapter\ExceptionConverter;
use MongoDB\Model\CollectionInfo;

/**
 * Instances of this class are used to interact with a database.
 * @link http://www.php.net/manual/en/class.mongodb.php
 */
class MongoDB
{
    use Helper\ReadPreference;
    use Helper\SlaveOkay;
    use Helper\WriteConcern;

    const PROFILING_OFF = 0;
    const PROFILING_SLOW = 1;
    const PROFILING_ON = 2;

    /**
     * @var MongoClient
     */
    protected $connection;

    /**
     * @var \MongoDB\Database
     */
    protected $db;

    /**
     * @var string
     */
    protected $name;

    /**
     * Creates a new database
     *
     * This method is not meant to be called directly. The preferred way to create an instance of MongoDB is through {@see Mongo::__get()} or {@see Mongo::selectDB()}.
     * @link http://www.php.net/manual/en/mongodb.construct.php
     * @param MongoClient $conn Database connection.
     * @param string $name Database name.
     * @throws Exception
     */
    public function __construct(MongoClient $conn, $name)
    {
        $this->checkDatabaseName($name);
        $this->connection = $conn;
        $this->name = (string) $name;

        $this->setReadPreferenceFromArray($conn->getReadPreference());
        $this->setWriteConcernFromArray($conn->getWriteConcern());

        $this->createDatabaseObject();
    }

    /**
     * @return \MongoDB\Database
     * @internal This method is not part of the ext-mongo API
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * The name of this database
     *
     * @link http://www.php.net/manual/en/mongodb.--tostring.php
     * @return string Returns this database's name.
     */
    public function __toString()
    {
        return $this->name;
    }

    /**
     * Gets a collection
     *
     * @link http://www.php.net/manual/en/mongodb.get.php
     * @param string $name The name of the collection.
     * @return MongoCollection
     */
    public function __get($name)
    {
        // Handle w and wtimeout properties that replicate data stored in $readPreference
        if ($name === 'w' || $name === 'wtimeout') {
            return $this->getWriteConcern()[$name];
        }

        return $this->selectCollection($name);
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        if ($name === 'w' || $name === 'wtimeout') {
            trigger_error("The '{$name}' property is read-only", E_USER_DEPRECATED);
        }
    }

    /**
     * Returns information about collections in this database
     *
     * @link http://www.php.net/manual/en/mongodb.getcollectioninfo.php
     * @param array $options An array of options for listing the collections.
     * @return array
     */
    public function getCollectionInfo(array $options = [])
    {
        $includeSystemCollections = false;
        // The includeSystemCollections option is no longer supported in the command
        if (isset($options['includeSystemCollections'])) {
            $includeSystemCollections = $options['includeSystemCollections'];
            unset($options['includeSystemCollections']);
        }

        try {
            $collections = $this->db->listCollections($options);
        } catch (\MongoDB\Driver\Exception\Exception $e) {
            throw ExceptionConverter::toLegacy($e);
        }

        $getCollectionInfo = function (CollectionInfo $collectionInfo) {
            // @todo do away with __debugInfo once https://jira.mongodb.org/browse/PHPLIB-226 is fixed
            $info = $collectionInfo->__debugInfo();

            return array_filter(
                [
                    'name' => $collectionInfo->getName(),
                    'type' => isset($info['type']) ? $info['type'] : null,
                    'options' => $collectionInfo->getOptions(),
                    'info' => isset($info['info']) ? (array) $info['info'] : null,
                    'idIndex' => isset($info['idIndex']) ? (array) $info['idIndex'] : null,
                ],
                function ($item) {
                    return $item !== null;
                }
            );
        };

        $eligibleCollections = array_filter(
            iterator_to_array($collections),
            $this->getSystemCollectionFilterClosure($includeSystemCollections)
        );

        return array_map($getCollectionInfo, $eligibleCollections);
    }

    /**
     * Get all collections from this database
     *
     * @link http://www.php.net/manual/en/mongodb.getcollectionnames.php
     * @param array $options An array of options for listing the collections.
     * @return array Returns the names of the all the collections in the database as an array
     */
    public function getCollectionNames(array $options = [])
    {
        $includeSystemCollections = false;
        // The includeSystemCollections option is no longer supported in the command
        if (isset($options['includeSystemCollections'])) {
            $includeSystemCollections = $options['includeSystemCollections'];
            unset($options['includeSystemCollections']);
        }

        try {
            $collections = $this->db->listCollections($options);
        } catch (\MongoDB\Driver\Exception\Exception $e) {
            throw ExceptionConverter::toLegacy($e);
        }

        $getCollectionName = function (CollectionInfo $collectionInfo) {
            return $collectionInfo->getName();
        };

        $eligibleCollections = array_filter(
            iterator_to_array($collections),
            $this->getSystemCollectionFilterClosure($includeSystemCollections)
        );

        return array_map($getCollectionName, $eligibleCollections);
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
     * Fetches toolkit for dealing with files stored in this database
     *
     * @link http://www.php.net/manual/en/mongodb.getgridfs.php
     * @param string $prefix The prefix for the files and chunks collections.
     * @return MongoGridFS Returns a new gridfs object for this database.
     */
    public function getGridFS($prefix = "fs")
    {
        return new \MongoGridFS($this, $prefix);
    }

    /**
     * Gets this database's profiling level
     *
     * @link http://www.php.net/manual/en/mongodb.getprofilinglevel.php
     * @return int Returns the profiling level.
     */
    public function getProfilingLevel()
    {
        $result = $this->command(['profile' => -1]);

        return ($result['ok'] && isset($result['was'])) ? $result['was'] : 0;
    }

    /**
     * Sets this database's profiling level
     *
     * @link http://www.php.net/manual/en/mongodb.setprofilinglevel.php
     * @param int $level Profiling level.
     * @return int Returns the previous profiling level.
     */
    public function setProfilingLevel($level)
    {
        $result = $this->command(['profile' => $level]);

        return ($result['ok'] && isset($result['was'])) ? $result['was'] : 0;
    }

    /**
     * Drops this database
     *
     * @link http://www.php.net/manual/en/mongodb.drop.php
     * @return array Returns the database response.
     */
    public function drop()
    {
        return TypeConverter::toLegacy($this->db->drop());
    }

    /**
     * Repairs and compacts this database
     *
     * @link http://www.php.net/manual/en/mongodb.repair.php
     * @param bool $preserve_cloned_files [optional] <p>If cloned files should be kept if the repair fails.</p>
     * @param bool $backup_original_files [optional] <p>If original files should be backed up.</p>
     * @return array <p>Returns db response.</p>
     */
    public function repair($preserve_cloned_files = false, $backup_original_files = false)
    {
        $command = [
            'repairDatabase' => 1,
            'preserveClonedFilesOnFailure' => $preserve_cloned_files,
            'backupOriginalFiles' => $backup_original_files,
        ];

        return $this->command($command);
    }

    /**
     * Gets a collection
     *
     * @link http://www.php.net/manual/en/mongodb.selectcollection.php
     * @param string $name <b>The collection name.</b>
     * @throws Exception if the collection name is invalid.
     * @return MongoCollection Returns a new collection object.
     */
    public function selectCollection($name)
    {
        return new MongoCollection($this, $name);
    }

    /**
     * Creates a collection
     *
     * @link http://www.php.net/manual/en/mongodb.createcollection.php
     * @param string $name The name of the collection.
     * @param array $options
     * @return MongoCollection Returns a collection object representing the new collection.
     */
    public function createCollection($name, $options = [])
    {
        try {
            if (isset($options['capped'])) {
                $options['capped'] = (bool) $options['capped'];
            }

            $this->db->createCollection($name, $options);
        } catch (\MongoDB\Driver\Exception\Exception $e) {
            return false;
        }

        return $this->selectCollection($name);
    }

    /**
     * Drops a collection
     *
     * @link http://www.php.net/manual/en/mongodb.dropcollection.php
     * @param MongoCollection|string $coll MongoCollection or name of collection to drop.
     * @return array Returns the database response.
     *
     * @deprecated Use MongoCollection::drop() instead.
     */
    public function dropCollection($coll)
    {
        if ($coll instanceof MongoCollection) {
            $coll = $coll->getName();
        }

        return TypeConverter::toLegacy($this->db->dropCollection((string) $coll));
    }

    /**
     * Get a list of collections in this database
     *
     * @link http://www.php.net/manual/en/mongodb.listcollections.php
     * @param array $options
     * @return MongoCollection[] Returns a list of MongoCollections.
     */
    public function listCollections(array $options = [])
    {
        return array_map([$this, 'selectCollection'], $this->getCollectionNames($options));
    }

    /**
     * Creates a database reference
     *
     * @link http://www.php.net/manual/en/mongodb.createdbref.php
     * @param string $collection The collection to which the database reference will point.
     * @param mixed $document_or_id
     * @return array Returns a database reference array.
     */
    public function createDBRef($collection, $document_or_id)
    {
        if ($document_or_id instanceof \MongoId) {
            $id = $document_or_id;
        } elseif (is_object($document_or_id)) {
            if (! isset($document_or_id->_id)) {
                $id = $document_or_id;
            } else {
                $id = $document_or_id->_id;
            }
        } elseif (is_array($document_or_id)) {
            if (! isset($document_or_id['_id'])) {
                return null;
            }

            $id = $document_or_id['_id'];
        } else {
            $id = $document_or_id;
        }

        return MongoDBRef::create($collection, $id);
    }


    /**
     * Fetches the document pointed to by a database reference
     *
     * @link http://www.php.net/manual/en/mongodb.getdbref.php
     * @param array $ref A database reference.
     * @return array Returns the document pointed to by the reference.
     */
    public function getDBRef(array $ref)
    {
        $db = (isset($ref['$db']) && $ref['$db'] !== $this->name) ? $this->connection->selectDB($ref['$db']) : $this;
        return MongoDBRef::get($db, $ref);
    }

    /**
     * Runs JavaScript code on the database server.
     *
     * @link http://www.php.net/manual/en/mongodb.execute.php
     * @param MongoCode|string $code Code to execute.
     * @param array $args [optional] Arguments to be passed to code.
     * @return array Returns the result of the evaluation.
     */
    public function execute($code, array $args = [])
    {
        return $this->command(['eval' => $code, 'args' => $args]);
    }

    /**
     * Execute a database command
     *
     * @link http://www.php.net/manual/en/mongodb.command.php
     * @param array $data The query to send.
     * @param array $options
     * @return array Returns database response.
     */
    public function command(array $data, $options = [], &$hash = null)
    {
        try {
            $cursor = new \MongoCommandCursor($this->connection, $this->name, $data);
            $cursor->setReadPreference($this->getReadPreference());

            return iterator_to_array($cursor)[0];
        } catch (\MongoDB\Driver\Exception\Exception $e) {
            return ExceptionConverter::toResultArray($e);
        }
    }

    /**
     * Check if there was an error on the most recent db operation performed
     *
     * @link http://www.php.net/manual/en/mongodb.lasterror.php
     * @return array Returns the error, if there was one.
     */
    public function lastError()
    {
        return $this->command(array('getLastError' => 1));
    }

    /**
     * Checks for the last error thrown during a database operation
     *
     * @link http://www.php.net/manual/en/mongodb.preverror.php
     * @return array Returns the error and the number of operations ago it occurred.
     */
    public function prevError()
    {
        return $this->command(array('getPrevError' => 1));
    }

    /**
     * Clears any flagged errors on the database
     *
     * @link http://www.php.net/manual/en/mongodb.reseterror.php
     * @return array Returns the database response.
     */
    public function resetError()
    {
        return $this->command(array('resetError' => 1));
    }

    /**
     * Creates a database error
     *
     * @link http://www.php.net/manual/en/mongodb.forceerror.php
     * @return boolean Returns the database response.
     */
    public function forceError()
    {
        return $this->command(array('forceerror' => 1));
    }

    /**
     * Log in to this database
     *
     * @link http://www.php.net/manual/en/mongodb.authenticate.php
     * @param string $username The username.
     * @param string $password The password (in plaintext).
     * @return array Returns database response. If the login was successful, it will return 1.
     *
     * @deprecated This method is not implemented, supply authentication credentials through the connection string instead.
     */
    public function authenticate($username, $password)
    {
        throw new \Exception('The MongoDB::authenticate method is not supported. Please supply authentication credentials through the connection string');
    }

    /**
     * {@inheritdoc}
     */
    public function setReadPreference($readPreference, $tags = null)
    {
        $result = $this->setReadPreferenceFromParameters($readPreference, $tags);
        $this->createDatabaseObject();

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function setWriteConcern($wstring, $wtimeout = 0)
    {
        $result = $this->setWriteConcernFromParameters($wstring, $wtimeout);
        $this->createDatabaseObject();

        return $result;
    }

    protected function notImplemented()
    {
        throw new \Exception('Not implemented');
    }

    /**
     * @return \MongoDB\Database
     */
    private function createDatabaseObject()
    {
        $options = [
            'readPreference' => $this->readPreference,
            'writeConcern' => $this->writeConcern,
        ];

        if ($this->db === null) {
            $this->db = $this->connection->getClient()->selectDatabase($this->name, $options);
        } else {
            $this->db = $this->db->withOptions($options);
        }
    }

    private function checkDatabaseName($name)
    {
        if (empty($name)) {
            throw new \Exception('Database name cannot be empty');
        }
        if (strlen($name) >= 64) {
            throw new \Exception('Database name cannot exceed 63 characters');
        }
        if (strpos($name, chr(0)) !== false) {
            throw new \Exception('Database name cannot contain null bytes');
        }

        $invalidCharacters = ['.', '$', '/', ' ', '\\'];
        foreach ($invalidCharacters as $char) {
            if (strchr($name, $char) !== false) {
                throw new \Exception('Database name contains invalid characters');
            }
        }
    }

    /**
     * @param bool $includeSystemCollections
     * @return Closure
     */
    private function getSystemCollectionFilterClosure($includeSystemCollections = false)
    {
        return function (CollectionInfo $collectionInfo) use ($includeSystemCollections) {
            return $includeSystemCollections || ! preg_match('#^system\.#', $collectionInfo->getName());
        };
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        return ['connection', 'name'];
    }
}
