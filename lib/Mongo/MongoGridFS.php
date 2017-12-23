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

if (class_exists('MongoGridFS', false)) {
    return;
}

class MongoGridFS extends MongoCollection
{
    const ASCENDING = 1;
    const DESCENDING = -1;

    /**
     * @link http://php.net/manual/en/class.mongogridfs.php#mongogridfs.props.chunks
     * @var $chunks MongoCollection
     */
    public $chunks;

    /**
     * @link http://php.net/manual/en/class.mongogridfs.php#mongogridfs.props.filesname
     * @var $filesName string
     */
    protected $filesName;

    /**
     * @link http://php.net/manual/en/class.mongogridfs.php#mongogridfs.props.chunksname
     * @var $chunksName string
     */
    protected $chunksName;

    /**
     * @var MongoDB
     */
    private $database;

    private $prefix;

    private $defaultChunkSize = 261120;

    /**
     * Files as stored across two collections, the first containing file meta
     * information, the second containing chunks of the actual file. By default,
     * fs.files and fs.chunks are the collection names used.
     *
     * @link http://php.net/manual/en/mongogridfs.construct.php
     * @param MongoDB $db Database
     * @param string $prefix [optional] <p>Optional collection name prefix.</p>
     * @param mixed $chunks  [optional]
     * @throws \Exception
     */
    public function __construct(MongoDB $db, $prefix = "fs", $chunks = null)
    {
        if ($chunks) {
            trigger_error("The 'chunks' argument is deprecated and ignored", E_USER_DEPRECATED);
        }
        if (empty($prefix)) {
            throw new \Exception('MongoGridFS::__construct(): invalid prefix');
        }

        $this->database = $db;
        $this->prefix = (string) $prefix;
        $this->filesName = $prefix . '.files';
        $this->chunksName = $prefix . '.chunks';

        $this->chunks = $db->selectCollection($this->chunksName);

        parent::__construct($db, $this->filesName);
    }

    /**
     * Delete a file from the database
     *
     * @link http://php.net/manual/en/mongogridfs.delete.php
     * @param mixed $id _id of the file to remove
     * @return boolean Returns true if the remove was successfully sent to the database.
     */
    public function delete($id)
    {
        $this->createChunksIndex();

        $this->chunks->remove(['files_id' => $id], ['justOne' => false]);
        return parent::remove(['_id' => $id]);
    }

    /**
     * Drops the files and chunks collections
     * @link http://php.net/manual/en/mongogridfs.drop.php
     * @return array The database response
     */
    public function drop()
    {
        $this->chunks->drop();
        return parent::drop();
    }

    /**
     * @link http://php.net/manual/en/mongogridfs.find.php
     * @param array $query The query
     * @param array $fields Fields to return
     * @param array $options Options for the find command
     * @return MongoGridFSCursor A MongoGridFSCursor
     */
    public function find(array $query = [], array $fields = [])
    {
        $cursor = new MongoGridFSCursor($this, $this->db->getConnection(), (string) $this, $query, $fields);
        $cursor->setReadPreference($this->getReadPreference());

        return $cursor;
    }

    /**
     * Returns a single file matching the criteria
     *
     * @link http://www.php.net/manual/en/mongogridfs.findone.php
     * @param mixed $query The fields for which to search or a filename to search for.
     * @param array $fields Fields of the results to return.
     * @param array $options Options for the find command
     * @return MongoGridFSFile|null
     */
    public function findOne($query = [], array $fields = [], array $options = [])
    {
        if (! is_array($query)) {
            $query = ['filename' => (string) $query];
        }

        $items = iterator_to_array($this->find($query, $fields)->limit(1));
        return count($items) ? current($items) : null;
    }

    /**
     * Retrieve a file from the database
     *
     * @link http://www.php.net/manual/en/mongogridfs.get.php
     * @param mixed $id _id of the file to find.
     * @return MongoGridFSFile|null
     */
    public function get($id)
    {
        return $this->findOne(['_id' => $id]);
    }

    /**
     * Stores a file in the database
     *
     * @link http://php.net/manual/en/mongogridfs.put.php
     * @param string $filename The name of the file
     * @param array $extra Other metadata to add to the file saved
     * @param array $options An array of options for the insert operations executed against the chunks and files collections.
     * @return mixed Returns the _id of the saved object
     */
    public function put($filename, array $extra = [], array $options = [])
    {
        return $this->storeFile($filename, $extra, $options);
    }

    /**
     * Removes files from the collections
     *
     * @link http://www.php.net/manual/en/mongogridfs.remove.php
     * @param array $criteria Description of records to remove.
     * @param array $options Options for remove.
     * @throws MongoCursorException
     * @return boolean
     */
    public function remove(array $criteria = [], array $options = [])
    {
        $this->createChunksIndex();

        $matchingFiles = parent::find($criteria, ['_id' => 1]);
        $ids = [];
        foreach ($matchingFiles as $file) {
            $ids[] = $file['_id'];
        }
        $this->chunks->remove(['files_id' => ['$in' => $ids]], ['justOne' => false] + $options);
        return parent::remove(['_id' => ['$in' => $ids]], ['justOne' => false] + $options);
    }

    /**
     * Chunkifies and stores bytes in the database
     * @link http://php.net/manual/en/mongogridfs.storebytes.php
     * @param string $bytes A string of bytes to store
     * @param array $extra Other metadata to add to the file saved
     * @param array $options Options for the store. "safe": Check that this store succeeded
     * @return mixed The _id of the object saved
     */
    public function storeBytes($bytes, array $extra = [], array $options = [])
    {
        $this->createChunksIndex();

        $record = $extra + [
            'length' => mb_strlen($bytes, '8bit'),
            'md5' => md5($bytes),
        ];

        try {
            $file = $this->insertFile($record, $options);
        } catch (MongoException $e) {
            throw new MongoGridFSException('Could not store file: ' . $e->getMessage(), $e->getCode(), $e);
        }

        try {
            $this->insertChunksFromBytes($bytes, $file);
        } catch (MongoException $e) {
            $this->delete($file['_id']);
            throw new MongoGridFSException('Could not store file: ' . $e->getMessage(), $e->getCode(), $e);
        }

        return $file['_id'];
    }

    /**
     * Stores a file in the database
     *
     * @link http://php.net/manual/en/mongogridfs.storefile.php
     * @param string $filename The name of the file
     * @param array $extra Other metadata to add to the file saved
     * @param array $options Options for the store. "safe": Check that this store succeeded
     * @return mixed Returns the _id of the saved object
     * @throws MongoGridFSException
     * @throws Exception
     */
    public function storeFile($filename, array $extra = [], array $options = [])
    {
        $this->createChunksIndex();

        $record = $extra;
        if (is_string($filename)) {
            $record += [
                'md5' => md5_file($filename),
                'length' => filesize($filename),
                'filename' => $filename,
            ];

            $handle = fopen($filename, 'r');
            if (! $handle) {
                throw new MongoGridFSException('could not open file: ' . $filename);
            }
        } elseif (! is_resource($filename)) {
            throw new \Exception('first argument must be a string or stream resource');
        } else {
            $handle = $filename;
        }

        $md5 = null;
        try {
            $file = $this->insertFile($record, $options);
        } catch (MongoException $e) {
            throw new MongoGridFSException('Could not store file: ' . $e->getMessage(), $e->getCode(), $e);
        }

        try {
            $length = $this->insertChunksFromFile($handle, $file, $md5);
        } catch (MongoException $e) {
            $this->delete($file['_id']);
            throw new MongoGridFSException('Could not store file: ' . $e->getMessage(), $e->getCode(), $e);
        }


        // Add length and MD5 if they were not present before
        $update = [];
        if (! isset($record['length'])) {
            $update['length'] = $length;
        }
        if (! isset($record['md5'])) {
            try {
                $update['md5'] = $md5;
            } catch (MongoException $e) {
                throw new MongoGridFSException('Could not store file: ' . $e->getMessage(), $e->getCode(), $e);
            }
        }

        if (count($update)) {
            try {
                $result = $this->update(['_id' => $file['_id']], ['$set' => $update]);
                if (! $this->isOKResult($result)) {
                    throw new MongoGridFSException('Could not store file');
                }
            } catch (MongoException $e) {
                $this->delete($file['_id']);
                throw new MongoGridFSException('Could not store file: ' . $e->getMessage(), $e->getCode(), $e);
            }
        }

        return $file['_id'];
    }

    /**
     * Saves an uploaded file directly from a POST to the database
     *
     * @link http://www.php.net/manual/en/mongogridfs.storeupload.php
     * @param string $name The name attribute of the uploaded file, from <input type="file" name="something"/>.
     * @param array $metadata An array of extra fields for the uploaded file.
     * @return mixed Returns the _id of the uploaded file.
     * @throws MongoGridFSException
     */
    public function storeUpload($name, array $metadata = [])
    {
        if (! isset($_FILES[$name]) || $_FILES[$name]['error'] !== UPLOAD_ERR_OK) {
            throw new MongoGridFSException("Could not find uploaded file $name");
        }
        if (! isset($_FILES[$name]['tmp_name'])) {
            throw new MongoGridFSException("Couldn't find tmp_name in the \$_FILES array. Are you sure the upload worked?");
        }

        $uploadedFile = $_FILES[$name];
        $uploadedFile['tmp_name'] = (array) $uploadedFile['tmp_name'];
        $uploadedFile['name'] = (array) $uploadedFile['name'];

        if (count($uploadedFile['tmp_name']) > 1) {
            foreach ($uploadedFile['tmp_name'] as $key => $file) {
                $metadata['filename'] = $uploadedFile['name'][$key];
                $this->storeFile($file, $metadata);
            }

            return null;
        } else {
            $metadata += ['filename' => array_pop($uploadedFile['name'])];
            return $this->storeFile(array_pop($uploadedFile['tmp_name']), $metadata);
        }
    }

    /**
     * Creates the index on the chunks collection
     */
    private function createChunksIndex()
    {
        try {
            $this->chunks->createIndex(['files_id' => 1, 'n' => 1], ['unique' => true]);
        } catch (MongoDuplicateKeyException $e) {
        }
    }

    /**
     * Inserts a single chunk into the database
     *
     * @param mixed $fileId
     * @param string $data
     * @param int $chunkNumber
     * @return array|bool
     */
    private function insertChunk($fileId, $data, $chunkNumber)
    {
        $chunk = [
            'files_id' => $fileId,
            'n' => $chunkNumber,
            'data' => new MongoBinData($data),
        ];

        $result = $this->chunks->insert($chunk);

        if (! $this->isOKResult($result)) {
            throw new \MongoException('error inserting chunk');
        }

        return $result;
    }

    /**
     * Splits a string into chunks and writes them to the database
     *
     * @param string $bytes
     * @param array $record
     */
    private function insertChunksFromBytes($bytes, $record)
    {
        $chunkSize = $record['chunkSize'];
        $fileId = $record['_id'];
        $i = 0;

        $chunks = str_split($bytes, $chunkSize);
        foreach ($chunks as $chunk) {
            $this->insertChunk($fileId, $chunk, $i++);
        }
    }

    /**
     * Reads chunks from a file and writes them to the database
     *
     * @param resource $handle
     * @param array $record
     * @param string $md5
     * @return int Returns the number of bytes written to the database
     */
    private function insertChunksFromFile($handle, $record, &$md5)
    {
        $written = 0;
        $offset = 0;
        $i = 0;

        $fileId = $record['_id'];
        $chunkSize = $record['chunkSize'];

        $hash = hash_init('md5');

        rewind($handle);
        while (! feof($handle)) {
            $data = stream_get_contents($handle, $chunkSize);
            hash_update($hash, $data);
            $this->insertChunk($fileId, $data, $i++);
            $written += strlen($data);
            $offset += $chunkSize;
        }

        $md5 = hash_final($hash);

        return $written;
    }

    /**
     * Writes a file record to the database
     *
     * @param $record
     * @param array $options
     * @return array
     */
    private function insertFile($record, array $options = [])
    {
        $record += [
            '_id' => new MongoId(),
            'uploadDate' => new MongoDate(),
            'chunkSize' => $this->defaultChunkSize,
        ];

        $result = $this->insert($record, $options);

        if (! $this->isOKResult($result)) {
            throw new \MongoException('error inserting file');
        }

        return $record;
    }

    private function isOKResult($result)
    {
        return (is_array($result) && $result['ok'] == 1.0) ||
               (is_bool($result) && $result);
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        return ['chunks', 'chunksName', 'database', 'defaultChunkSize', 'filesName', 'prefix'] + parent::__sleep();
    }
}
