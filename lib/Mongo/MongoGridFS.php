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

class MongoGridFS extends MongoCollection {
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
     * Files as stored across two collections, the first containing file meta
     * information, the second containing chunks of the actual file. By default,
     * fs.files and fs.chunks are the collection names used.
     *
     * @link http://php.net/manual/en/mongogridfs.construct.php
     * @param MongoDB $db Database
     * @param string $prefix [optional] <p>Optional collection name prefix.</p>
     * @param mixed $chunks  [optional]
     * @return MongoGridFS
     */
    public function __construct($db, $prefix = "fs", $chunks = "fs") {}

    /**
     * Drops the files and chunks collections
     * @link http://php.net/manual/en/mongogridfs.drop.php
     * @return array The database response
     */
    public function drop() {}

    /**
     * @link http://php.net/manual/en/mongogridfs.find.php
     * @param array $query The query
     * @param array $fields Fields to return
     * @return MongoGridFSCursor A MongoGridFSCursor
     */
    public function find(array $query = array(), array $fields = array()) {}

    /**
     * Stores a file in the database
     * @link http://php.net/manual/en/mongogridfs.storefile.php
     * @param string $filename The name of the file
     * @param array $extra Other metadata to add to the file saved
     * @param array $options Options for the store. "safe": Check that this store succeeded
     * @return mixed Returns the _id of the saved object
     */
    public function storeFile($filename, $extra = array(), $options = array()) {}

    /**
     * Chunkifies and stores bytes in the database
     * @link http://php.net/manual/en/mongogridfs.storebytes.php
     * @param string $bytes A string of bytes to store
     * @param array $extra Other metadata to add to the file saved
     * @param array $options Options for the store. "safe": Check that this store succeeded
     * @return mixed The _id of the object saved
     */
    public function storeBytes($bytes, $extra = array(), $options = array()) {}

    /**
     * Returns a single file matching the criteria
     * @link http://www.php.net/manual/en/mongogridfs.findone.php
     * @param array $query The fields for which to search.
     * @param array $fields Fields of the results to return.
     * @return MongoGridFSFile|null
     */
    public function findOne(array $query = array(), array $fields = array()) {}

    /**
     * Removes files from the collections
     * @link http://www.php.net/manual/en/mongogridfs.remove.php
     * @param array $criteria Description of records to remove.
     * @param array $options Options for remove. Valid options are: "safe"- Check that the remove succeeded.
     * @throws MongoCursorException
     * @return boolean
     */
    public function remove(array $criteria = array(), array $options = array()) {}

    /**
     * Delete a file from the database
     * @link http://php.net/manual/en/mongogridfs.delete.php
     * @param mixed $id _id of the file to remove
     * @return boolean Returns true if the remove was successfully sent to the database.
     */
    public function delete($id) {}

    /**
     * Saves an uploaded file directly from a POST to the database
     * @link http://www.php.net/manual/en/mongogridfs.storeupload.php
     * @param string $name The name attribute of the uploaded file, from <input type="file" name="something"/>.
     * @param array $metadata An array of extra fields for the uploaded file.
     * @return mixed Returns the _id of the uploaded file.
     */
    public function storeUpload($name, array $metadata = array()) {}


    /**
     * Retrieve a file from the database
     * @link http://www.php.net/manual/en/mongogridfs.get.php
     * @param mixed $id _id of the file to find.
     * @return MongoGridFSFile|null Returns the file, if found, or NULL.
     */
    public function __get($id) {}

    /**
     * Stores a file in the database
     * @link http://php.net/manual/en/mongogridfs.put.php
     * @param string $filename The name of the file
     * @param array $extra Other metadata to add to the file saved
     * @return mixed Returns the _id of the saved object
     */
    public function put($filename, array $extra = array()) {}

}
