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

if (class_exists('MongoGridFSFile', false)) {
    return;
}

class MongoGridFSFile
{
    /**
     * @link http://php.net/manual/en/class.mongogridfsfile.php#mongogridfsfile.props.file
     * @var array
     */
    public $file;

    /**
     * @link http://php.net/manual/en/class.mongogridfsfile.php#mongogridfsfile.props.gridfs
     * @var $gridfs
     */
    protected $gridfs;

    /**
     * @link http://php.net/manual/en/mongogridfsfile.construct.php
     *
     * @param MongoGridFS $gridfs The parent MongoGridFS instance
     * @param array $file A file from the database
     */
    public function __construct(MongoGridFS $gridfs, array $file)
    {
        $this->gridfs = $gridfs;
        $this->file = $file;
    }

    /**
     * Returns this file's filename
     * @link http://php.net/manual/en/mongogridfsfile.getfilename.php
     * @return string Returns the filename
     */
    public function getFilename()
    {
        return isset($this->file['filename']) ? $this->file['filename'] : null;
    }

    /**
     * Returns this file's size
     * @link http://php.net/manual/en/mongogridfsfile.getsize.php
     * @return int Returns this file's size
     */
    public function getSize()
    {
        return $this->file['length'];
    }

    /**
     * Writes this file to the filesystem
     * @link http://php.net/manual/en/mongogridfsfile.write.php
     * @param string $filename The location to which to write the file (path+filename+extension). If none is given, the stored filename will be used.
     * @return int Returns the number of bytes written
     */
    public function write($filename = null)
    {
        if ($filename === null) {
            $filename = $this->getFilename();
        }
        if (empty($filename)) {
            $filename = 'file';
        }

        if (! $handle = fopen($filename, 'w')) {
            trigger_error('Can not open the destination file', E_USER_ERROR);
            return 0;
        }

        $written = $this->copyToResource($handle);
        fclose($handle);

        return $written;
    }

    /**
     * This will load the file into memory. If the file is bigger than your memory, this will cause problems!
     * @link http://php.net/manual/en/mongogridfsfile.getbytes.php
     * @return string Returns a string of the bytes in the file
     */
    public function getBytes()
    {
        $result = '';
        foreach ($this->getChunks() as $chunk) {
            $result .= $chunk['data']->bin;
        }

        return $result;
    }

    /**
     * This method returns a stream resource that can be used to read the stored file with all file functions in PHP.
     * The contents of the file are pulled out of MongoDB on the fly, so that the whole file does not have to be loaded into memory first.
     * At most two GridFSFile chunks will be loaded in memory.
     *
     * @link http://php.net/manual/en/mongogridfsfile.getresource.php
     * @return resource Returns a resource that can be used to read the file with
     */
    public function getResource()
    {
        $handle = fopen('php://temp', 'w+');
        $this->copyToResource($handle);
        rewind($handle);

        return $handle;
    }

    private function copyToResource($handle)
    {
        $written = 0;
        foreach ($this->getChunks() as $chunk) {
            $written += fwrite($handle, $chunk['data']->bin);
        }

        return $written;
    }

    private function getChunks()
    {
        return $chunks = $this->gridfs->chunks->find(
            ['files_id' => $this->file['_id']],
            ['data' => 1],
            ['n' => 1]
        );
    }
}
