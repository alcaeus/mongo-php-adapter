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

class MongoGridFS extends MongoCollection
{
    const DEFAULT_CHUNK_SIZE = 262144; // 256 kb

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
    protected $database;

    protected $ensureIndexes = false;

    /**
     * Files as stored across two collections, the first containing file meta
     * information, the second containing chunks of the actual file. By default,
     * fs.files and fs.chunks are the collection names used.
     *
     * @link http://php.net/manual/en/mongogridfs.construct.php
     * @param MongoDB $db Database
     * @param string $prefix [optional] <p>Optional collection name prefix.</p>
     * @param mixed $chunks  [optional]
     * @return MongoGridFS
     * @throws \Exception
     */
    public function __construct(MongoDB $db, $prefix = "fs", $chunks = null)
    {
        if ($chunks) {
            trigger_error("The 'chunks' argument is deprecated and ignored", E_DEPRECATED);
        }
        if (empty($prefix)) {
            throw new \Exception('MongoGridFS::__construct(): invalid prefix');
        }

        $this->database = $db;
        $this->filesName = $prefix . '.files';
        $this->chunksName = $prefix . '.chunks';

        $this->chunks = $db->selectCollection($this->chunksName);

        parent::__construct($db, $this->filesName);
    }

    /**
     * Drops the files and chunks collections
     * @link http://php.net/manual/en/mongogridfs.drop.php
     * @return array The database response
     */
    public function drop()
    {
        $this->chunks->drop();
        parent::drop();
    }

    /**
     * @link http://php.net/manual/en/mongogridfs.find.php
     * @param array $query The query
     * @param array $fields Fields to return
     * @return MongoGridFSCursor A MongoGridFSCursor
     */
    public function find(array $query = array(), array $fields = array())
    {
        $cursor = new MongoGridFSCursor($this, $this->db->getConnection(), (string)$this, $query, $fields);
        $cursor->setReadPreference($this->getReadPreference());

        return $cursor;
    }

    /**
     * Stores a file in the database
     * @link http://php.net/manual/en/mongogridfs.storefile.php
     * @param string $filename The name of the file
     * @param array $extra Other metadata to add to the file saved
     * @param array $options Options for the store. "safe": Check that this store succeeded
     * @return mixed Returns the _id of the saved object
     */
    public function storeFile($filename, array $extra = array(), array $options = array())
    {
        if (is_string($filename)) {
            $md5 = md5_file($filename);
            $shortName = basename($filename);
            $filename = fopen($filename, 'r');
        }
        if (! is_resource($filename)) {
            throw new \InvalidArgumentException();
        }
        $length = fstat($filename)['size'];
        $extra['chunkSize'] = isset($extra['chunkSize']) ? $extra['chunkSize']: self::DEFAULT_CHUNK_SIZE;
        $extra['_id'] = isset($extra['_id']) ?: new MongoId();
        $extra['length'] = $length;
        $extra['md5'] = isset($md5) ? $md5 : $this->calculateMD5($filename);
        $extra['filename'] = isset($extra['filename']) ? $extra['filename'] : $shortName;

        $fileDocument = $this->insertFile($extra);
        $this->insertChunksFromFile($filename, $fileDocument);

        return $fileDocument['_id'];
    }

    /**
     * Chunkifies and stores bytes in the database
     * @link http://php.net/manual/en/mongogridfs.storebytes.php
     * @param string $bytes A string of bytes to store
     * @param array $extra Other metadata to add to the file saved
     * @param array $options Options for the store. "safe": Check that this store succeeded
     * @return mixed The _id of the object saved
     */
    public function storeBytes($bytes, array $extra = array(), array $options = array())
    {
        $length = mb_strlen($bytes, '8bit');
        $extra['chunkSize'] = isset($extra['chunkSize']) ? $extra['chunkSize'] : self::DEFAULT_CHUNK_SIZE;
        $extra['_id'] = isset($extra['_id']) ?: new MongoId();
        $extra['length'] = $length;
        $extra['md5'] = md5($bytes);

        $file = $this->insertFile($extra);
        $this->insertChunksFromBytes($bytes, $file);

        return $file['_id'];
    }

    /**
     * Returns a single file matching the criteria
     * @link http://www.php.net/manual/en/mongogridfs.findone.php
     * @param array $query The fields for which to search.
     * @param array $fields Fields of the results to return.
     * @return MongoGridFSFile|null
     */
    public function findOne(array $query = [], array $fields = [], array $options = [])
    {
        $file = parent::findOne($query, $fields);
        if (! $file) {
            return;
        }
        return new MongoGridFSFile($this, $file);
    }

    /**
     * Removes files from the collections
     * @link http://www.php.net/manual/en/mongogridfs.remove.php
     * @param array $criteria Description of records to remove.
     * @param array $options Options for remove. Valid options are: "safe"- Check that the remove succeeded.
     * @throws MongoCursorException
     * @return boolean
     */
    public function remove(array $criteria = [], array $options = [])
    {
        $matchingFiles = parent::find($criteria, ['_id' => 1]);
        $ids = [];
        foreach ($matchingFiles as $file) {
            $ids[] = $file['_id'];
        }
        $this->chunks->remove(['files_id' => ['$in' => $ids]], ['justOne' => false]);
        return parent::remove($criteria, ['justOne' => false] + $options);
    }

    /**
     * Delete a file from the database
     * @link http://php.net/manual/en/mongogridfs.delete.php
     * @param mixed $id _id of the file to remove
     * @return boolean Returns true if the remove was successfully sent to the database.
     */
    public function delete($id)
    {
        if (is_string($id)) {
            $id = new MongoId($id);
        }
        if (! $id instanceof MongoId) {
            return false;
        }
        $this->chunks->remove(['files_id' => $id], ['justOne' => false]);
        return parent::remove(['_id' => $id]);
    }

    /**
     * Saves an uploaded file directly from a POST to the database
     * @link http://www.php.net/manual/en/mongogridfs.storeupload.php
     * @param string $name The name attribute of the uploaded file, from <input type="file" name="something"/>.
     * @param array $metadata An array of extra fields for the uploaded file.
     * @return mixed Returns the _id of the uploaded file.
     */
    public function storeUpload($name, array $metadata = array())
    {
        if (! isset($_FILES[$name]) || $_FILES[$name]['error'] !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException();
        }
        $metadata += ['filename' => $_FILES[$name]['name']];
        return $this->storeFile($_FILES[$name]['tmp_name'], $metadata);
    }

    /**
     * Retrieve a file from the database
     * @link http://www.php.net/manual/en/mongogridfs.get.php
     * @param mixed $id _id of the file to find.
     * @return MongoGridFSFile|null Returns the file, if found, or NULL.
     */
    public function __get($id)
    {
        if (is_string($id)) {
            $id = new MongoId($id);
        }
        if (! $id instanceof MongoId) {
            return false;
        }
        return $this->findOne(['_id' => $id]);
    }

    /**
     * Stores a file in the database
     * @link http://php.net/manual/en/mongogridfs.put.php
     * @param string $filename The name of the file
     * @param array $extra Other metadata to add to the file saved
     * @return mixed Returns the _id of the saved object
     */
    public function put($filename, array $extra = array())
    {
        return $this->storeFile($filename, $extra);
    }

    private function ensureIndexes()
    {
        if ($this->ensureIndexes) {
            return;
        }
        $this->ensureFilesIndex();
        $this->ensureChunksIndex();
        $this->ensuredIndexes = true;
    }

    private function ensureChunksIndex()
    {
        foreach ($this->chunks->getIndexInfo() as $index) {
            if (isset($index['unique']) && $index['unique'] && $index['key'] === ['files_id' => 1, 'n' => 1]) {
                return;
            }
        }
        $this->chunks->createIndex(['files_id' => 1, 'n' => 1], ['unique' => true]);
    }

    private function ensureFilesIndex()
    {
        foreach ($this->getIndexInfo() as $index) {
            if ($index['key'] === ['filename' => 1, 'uploadDate' => 1]) {
                return;
            }
        }
        $this->createIndex(['filename' => 1, 'uploadDate' => 1]);
    }

    private function insertChunksFromFile($file, $fileInfo)
    {
        $length = $fileInfo['length'];
        $chunkSize = $fileInfo['chunkSize'];
        $fileId = $fileInfo['_id'];
        $offset = 0;
        $i = 0;

        rewind($file);

        while ($offset < $length) {
            $data = stream_get_contents($file, $chunkSize);
            $this->insertChunk($fileId, $data, $i++);
            $offset += $chunkSize;
        }
    }

    private function calculateMD5($file)
    {
        // XXX: this could be really a bad idea with big files...
        rewind($file);
        $data = stream_get_contents($file);

        return md5($data);
    }

    private function insertChunksFromBytes($bytes, $fileInfo)
    {
        $length = $fileInfo['length'];
        $chunkSize = $fileInfo['chunkSize'];
        $fileId = $fileInfo['_id'];
        $i = 0;

        $chunks = str_split($bytes, $chunkSize);
        foreach ($chunks as $chunk) {
            $this->insertChunk($fileId, $chunk, $i++);
        }
    }

    private function insertChunk($id, $data, $chunkNumber)
    {
        $chunk = [
            'files_id' => $id,
            'n' => $chunkNumber,
            'data' => new MongoBinData($data),
        ];
        return $this->chunks->insert($chunk);
    }

    private function insertFile($metadata)
    {
        $this->ensureIndexes();
        $metadata['uploadDate'] = new MongoDate();
        $this->insert($metadata);
        return $metadata;
    }

}
