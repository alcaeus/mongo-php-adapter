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

class MongoGridFSCursor extends MongoCursor implements Traversable, Iterator {
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
     * @link http://php.net/manual/en/mongogridfscursor.construct.php
     * @param MongoGridFS $gridfs Related GridFS collection
     * @param resource $connection Database connection
     * @param string $ns Full name of database and collection
     * @param array $query Database query
     * @param array $fields Fields to return
     * @return MongoGridFSCursor Returns the new cursor
     */
    public function __construct($gridfs, $connection, $ns, $query, $fields) {}

    /**
     * Return the next file to which this cursor points, and advance the cursor
     * @link http://php.net/manual/en/mongogridfscursor.getnext.php
     * @return MongoGridFSFile Returns the next file
     */
    public function getNext() {}

    /**
     * Returns the current file
     * @link http://php.net/manual/en/mongogridfscursor.current.php
     * @return MongoGridFSFile The current file
     */
    public function current() {}

    /**
     * Returns the current result's filename
     * @link http://php.net/manual/en/mongogridfscursor.key.php
     * @return string The current results filename
     */
    public function key() {}

}
