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

/**
 * Result object for database query.
 * @link http://www.php.net/manual/en/class.mongocursor.php
 */
class MongoCursor implements Iterator, Traversable {
    /**
     * @link http://php.net/manual/en/class.mongocursor.php#mongocursor.props.slaveokay
     * @static
     * @var bool $slaveOkay
     */
    public static $slaveOkay = FALSE;

    /**
     * @var int <p>
     * Set timeout in milliseconds for all database responses. Use
     * <em>-1</em> to wait forever. Can be overridden with
     * {link http://php.net/manual/en/mongocursor.timeout.php MongoCursor::timeout()}. This does not cause the
     * MongoDB server to cancel the operation; it only instructs the driver to
     * stop waiting for a response and throw a
     * {@link http://php.net/manual/en/class.mongocursortimeoutexception.php MongoCursorTimeoutException} after a set time.
     * </p>
     */
    static $timeout = 30000;
    /**
     * Create a new cursor
     * @link http://www.php.net/manual/en/mongocursor.construct.php
     * @param MongoClient $connection Database connection.
     * @param string $ns Full name of database and collection.
     * @param array $query Database query.
     * @param array $fields Fields to return.
     * @return MongoCursor Returns the new cursor
     */
    public function __construct($connection, $ns, array $query = array(), array $fields = array()) {}

    /**
     * (PECL mongo &gt;= 1.2.11)<br/>
     * Sets whether this cursor will wait for a while for a tailable cursor to return more data
     * @param bool $wait [optional] <p>If the cursor should wait for more data to become available.</p>
     * @return MongoCursor Returns this cursor.
     */
    public function awaitData ($wait = true) {}
    /**
     * Checks if there are any more elements in this cursor
     * @link http://www.php.net/manual/en/mongocursor.hasnext.php
     * @throws MongoConnectionException
     * @throws MongoCursorTimeoutException
     * @return bool Returns true if there is another element
     */
    public function hasNext() {}

    /**
     * Return the next object to which this cursor points, and advance the cursor
     * @link http://www.php.net/manual/en/mongocursor.getnext.php
     * @throws MongoConnectionException
     * @throws MongoCursorTimeoutException
     * @return array Returns the next object
     */
    public function getNext() {}

    /**
     * (PECL mongo &gt;= 1.3.3)<br/>
     * @link http://www.php.net/manual/en/mongocursor.getreadpreference.php
     * @return array This function returns an array describing the read preference. The array contains the values <em>type</em> for the string
     * read preference mode (corresponding to the {@link http://www.php.net/manual/en/class.mongoclient.php MongoClient} constants), and <em>tagsets</em> containing a list of all tag set criteria. If no tag sets were specified, <em>tagsets</em> will not be present in the array.
     */
    public function getReadPreference () { }

    /**
     * Limits the number of results returned
     * @link http://www.php.net/manual/en/mongocursor.limit.php
     * @param int $num The number of results to return.
     * @throws MongoCursorException
     * @return MongoCursor Returns this cursor
     */
    public function limit($num) {}

    /**
     * (PECL mongo &gt;= 1.2.0)<br/>
     * @link http://www.php.net/manual/en/mongocursor.partial.php
     * @param bool $okay [optional] <p>If receiving partial results is okay.</p>
     * @return MongoCursor Returns this cursor.
     */
    public function partial ($okay = true) {}

    /**
     * (PECL mongo &gt;= 1.2.1)<br/>
     * @link http://www.php.net/manual/en/mongocursor.setflag.php
     * @param int $flag <p>
     * Which flag to set. You can not set flag 6 (EXHAUST) as the driver does
     * not know how to handle them. You will get a warning if you try to use
     * this flag. For available flags, please refer to the wire protocol
     * {@link http://www.mongodb.org/display/DOCS/Mongo+Wire+Protocol#MongoWireProtocol-OPQUERY documentation}.
     * </p>
     * @param bool $set [optional] <p>Whether the flag should be set (<b>TRUE</b>) or unset (<b>FALSE</b>).</p>
     * @return MongoCursor
     */
    public function setFlag ($flag, $set = true ) {}

    /**
     * (PECL mongo &gt;= 1.3.3)<br/>
     * @link http://www.php.net/manual/en/mongocursor.setreadpreference.php
     * @param string $read_preference <p>The read preference mode: MongoClient::RP_PRIMARY, MongoClient::RP_PRIMARY_PREFERRED, MongoClient::RP_SECONDARY, MongoClient::RP_SECONDARY_PREFERRED, or MongoClient::RP_NEAREST.</p>
     * @param array $tags [optional] <p>The read preference mode: MongoClient::RP_PRIMARY, MongoClient::RP_PRIMARY_PREFERRED, MongoClient::RP_SECONDARY, MongoClient::RP_SECONDARY_PREFERRED, or MongoClient::RP_NEAREST.</p>
     * @return MongoCursor Returns this cursor.
     */
    public function setReadPreference ($read_preference, array $tags) {}

    /**
     * Skips a number of results
     * @link http://www.php.net/manual/en/mongocursor.skip.php
     * @param int $num The number of results to skip.
     * @throws MongoCursorException
     * @return MongoCursor Returns this cursor
     */
    public function skip($num) {}

    /**
     * Sets whether this query can be done on a slave
     * This method will override the static class variable slaveOkay.
     * @link http://www.php.net/manual/en/mongocursor.slaveOkay.php
     * @param boolean $okay If it is okay to query the slave.
     * @throws MongoCursorException
     * @return MongoCursor Returns this cursor
     */
    public function slaveOkay($okay = true) {}

    /**
     * Sets whether this cursor will be left open after fetching the last results
     * @link http://www.php.net/manual/en/mongocursor.tailable.php
     * @param bool $tail If the cursor should be tailable.
     * @return MongoCursor Returns this cursor
     */
    public function tailable($tail = true) {}

    /**
     * Sets whether this cursor will timeout
     * @link http://www.php.net/manual/en/mongocursor.immortal.php
     * @param bool $liveForever If the cursor should be immortal.
     * @throws MongoCursorException
     * @return MongoCursor Returns this cursor
     */
    public function immortal($liveForever = true) {}

    /**
     * Sets a client-side timeout for this query
     * @link http://www.php.net/manual/en/mongocursor.timeout.php
     * @param int $ms The number of milliseconds for the cursor to wait for a response. By default, the cursor will wait forever.
     * @throws MongoCursorTimeoutException
     * @return MongoCursor Returns this cursor
     */
    public function timeout($ms) {}

    /**
     * Checks if there are documents that have not been sent yet from the database for this cursor
     * @link http://www.php.net/manual/en/mongocursor.dead.php
     * @return boolean Returns if there are more results that have not been sent to the client, yet.
     */
    public function dead() {}

    /**
     * Use snapshot mode for the query
     * @link http://www.php.net/manual/en/mongocursor.snapshot.php
     * @throws MongoCursorException
     * @return MongoCursor Returns this cursor
     */
    public function snapshot() {}

    /**
     * Sorts the results by given fields
     * @link http://www.php.net/manual/en/mongocursor.sort.php
     * @param array $fields An array of fields by which to sort. Each element in the array has as key the field name, and as value either 1 for ascending sort, or -1 for descending sort
     * @throws MongoCursorException
     * @return MongoCursor Returns the same cursor that this method was called on
     */
    public function sort(array $fields) {}

    /**
     * Gives the database a hint about the query
     * @link http://www.php.net/manual/en/mongocursor.hint.php
     * @param array $key_pattern Indexes to use for the query.
     * @throws MongoCursorException
     * @return MongoCursor Returns this cursor
     */
    public function hint(array $key_pattern) {}


    /**
     * Adds a top-level key/value pair to a query
     * @link http://www.php.net/manual/en/mongocursor.addoption.php
     * @param string $key Fieldname to add.
     * @param mixed $value Value to add.
     * @throws MongoCursorException
     * @return MongoCursor Returns this cursor
     */
    public function addOption($key, $value) {}

    /**
     * Execute the query
     * @link http://www.php.net/manual/en/mongocursor.doquery.php
     * @throws MongoConnectionException if it cannot reach the database.
     * @return void
     */
    protected function doQuery() {}

    /**
     * Returns the current element
     * @link http://www.php.net/manual/en/mongocursor.current.php
     * @return array
     */
    public function current() {}

    /**
     * Returns the current result's _id
     * @link http://www.php.net/manual/en/mongocursor.key.php
     * @return string The current result's _id as a string.
     */
    public function key() {}

    /**
     * Advances the cursor to the next result
     * @link http://www.php.net/manual/en/mongocursor.next.php
     * @throws MongoConnectionException
     * @throws MongoCursorTimeoutException
     * @return void
     */
    public function next() {}

    /**
     * Returns the cursor to the beginning of the result set
     * @throws MongoConnectionException
     * @throws MongoCursorTimeoutException
     * @return void
     */
    public function rewind() {}

    /**
     * Checks if the cursor is reading a valid result.
     * @link http://www.php.net/manual/en/mongocursor.valid.php
     * @return boolean If the current result is not null.
     */
    public function valid() {}

    /**
     * Clears the cursor
     * @link http://www.php.net/manual/en/mongocursor.reset.php
     * @return void
     */
    public function reset() {}

    /**
     * Return an explanation of the query, often useful for optimization and debugging
     * @link http://www.php.net/manual/en/mongocursor.explain.php
     * @return array Returns an explanation of the query.
     */
    public function explain() {}

    /**
     * Counts the number of results for this query
     * @link http://www.php.net/manual/en/mongocursor.count.php
     * @param bool $all Send cursor limit and skip information to the count function, if applicable.
     * @return int The number of documents returned by this cursor's query.
     */
    public function count($all = FALSE) {}

    /**
     * Sets the fields for a query
     * @link http://www.php.net/manual/en/mongocursor.fields.php
     * @param array $f Fields to return (or not return).
     * @throws MongoCursorException
     * @return MongoCursor
     */
    public function fields(array $f){}

    /**
     * Gets the query, fields, limit, and skip for this cursor
     * @link http://www.php.net/manual/en/mongocursor.info.php
     * @return array The query, fields, limit, and skip for this cursor as an associative array.
     */
    public function info(){}

    /**
     * PECL mongo >=1.0.11
     * Limits the number of elements returned in one batch.
     * <p>A cursor typically fetches a batch of result objects and store them locally.
     * This method sets the batchSize value to configure the amount of documents retrieved from the server in one data packet.
     * However, it will never return more documents than fit in the max batch size limit (usually 4MB).
     *
     * @param int $batchSize The number of results to return per batch. Each batch requires a round-trip to the server.
     * <p>If batchSize is 2 or more, it represents the size of each batch of objects retrieved.
     * It can be adjusted to optimize performance and limit data transfer.
     *
     * <p>If batchSize is 1 or negative, it will limit of number returned documents to the absolute value of batchSize,
     * and the cursor will be closed. For example if batchSize is -10, then the server will return a maximum of 10
     * documents and as many as can fit in 4MB, then close the cursor.
     * <b>Warning</b>
     * <p>A batchSize of 1 is special, and means the same as -1, i.e. a value of 1 makes the cursor only capable of returning one document.
     * <p>Note that this feature is different from MongoCursor::limit() in that documents must fit within a maximum size,
     * and it removes the need to send a request to close the cursor server-side.
     * The batch size can be changed even after a cursor is iterated, in which case the setting will apply on the next batch retrieval.
     * <p>This cannot override MongoDB's limit on the amount of data it will return to the client (i.e.,
     * if you set batch size to 1,000,000,000, MongoDB will still only return 4-16MB of results per batch).
     * <p>To ensure consistent behavior, the rules of MongoCursor::batchSize() and MongoCursor::limit() behave a little complex
     * but work "as expected". The rules are: hard limits override soft limits with preference given to MongoCursor::limit() over
     * MongoCursor::batchSize(). After that, whichever is set and lower than the other will take precedence.
     * See below. section for some examples.
     * @return MongoCursor Returns this cursor.
     * @link http://docs.php.net/manual/en/mongocursor.batchsize.php
     */
    public function batchSize($batchSize){}
}

