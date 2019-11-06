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

if (class_exists('MongoGridFSCursor', false)) {
    return;
}

class MongoGridFSCursor extends MongoCursor implements Countable
{
    /**
     * @static
     * @var $slaveOkay
     */
    public static $slaveOkay;

    /**
     * @link http://php.net/manual/en/class.mongogridfscursor.php#mongogridfscursor.props.gridfs
     * @var $gridfs
     */
    protected $gridfs;

    /**
     * Create a new cursor
     *
     * @link http://php.net/manual/en/mongogridfscursor.construct.php
     * @param MongoGridFS $gridfs Related GridFS collection
     * @param MongoClient $connection Database connection
     * @param string $ns Full name of database and collection
     * @param array $query Database query
     * @param array $fields Fields to return
     */
    public function __construct(MongoGridFS $gridfs, MongoClient $connection, $ns, array $query = array(), array $fields = array())
    {
        $this->gridfs = $gridfs;
        parent::__construct($connection, $ns, $query, $fields);
    }

    /**
     * Returns the current file
     *
     * @link http://php.net/manual/en/mongogridfscursor.current.php
     * @return MongoGridFSFile The current file
     */
    public function current()
    {
        $file = parent::current();
        return ($file !== null) ? new MongoGridFSFile($this->gridfs, $file) : null;
    }

    /**
     * Returns the current result's filename
     *
     * @link http://php.net/manual/en/mongogridfscursor.key.php
     * @return string The current results filename
     */
    public function key()
    {
        $file = $this->current();
        return ($file !== null) ? (string) $file->file['_id'] : null;
    }
}
