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

use Alcaeus\MongoDbAdapter\Helper;


/**
 * The connection point between MongoDB and PHP.
 * This class is used to initiate a connection and for database server commands.
 * @link http://www.php.net/manual/en/class.mongo.php
 * @deprecated This class has been DEPRECATED as of version 1.3.0.
 * Relying on this feature is highly discouraged. Please use MongoClient instead.
 * @see MongoClient
 */
class Mongo extends MongoClient {
    use Helper\Slave;

    /**
     * @deprecated This feature has been DEPRECATED as of version 1.2.3. Relying on this feature is highly discouraged. Please use MongoPool::getSize() instead.
     * (PECL mongo &gt;= 1.2.0)<br/>
     * Get pool size for connection pools
     * @link http://php.net/manual/en/mongo.getpoolsize.php
     * @return int Returns the current pool size.
     */
    public function getPoolSize() {}
    
    /**
     * Connects to paired database server
     * @deprecated Pass a string of the form "mongodb://server1,server2" to the constructor instead of using this method.
     * @link http://www.php.net/manual/en/mongo.pairconnect.php
     * @throws MongoConnectionException
     * @return boolean
     */
    public function pairConnect() {}

    /**
     * (PECL mongo &gt;= 1.2.0)<br/>
     * @deprecated This feature has been DEPRECATED as of version 1.2.3. Relying on this feature is highly discouraged. Please use MongoPool::info() instead.
     * Returns information about all connection pools.
     * @link http://php.net/manual/en/mongo.pooldebug.php
     * @return array  Each connection pool has an identifier, which starts with the host. For each pool, this function shows the following fields:
     * <p><b>in use</b></p>
     * <p>The number of connections currently being used by MongoClient instances.
     * in pool
     * The number of connections currently in the pool (not being used).</p>
     * <p><b>remaining</b></p>
     *
     * <p>The number of connections that could be created by this pool. For example, suppose a pool had 5 connections remaining and 3 connections in the pool. We could create 8 new instances of MongoClient before we exhausted this pool (assuming no instances of MongoClient went out of scope, returning their connections to the pool).
     *
     * A negative number means that this pool will spawn unlimited connections.
     *
     * Before a pool is created, you can change the max number of connections by calling Mongo::setPoolSize(). Once a pool is showing up in the output of this function, its size cannot be changed.</p>
     * <p><b>timeout</b></p>
     *
     * <p>The socket timeout for connections in this pool. This is how long connections in this pool will attempt to connect to a server before giving up.</p>
     *
     */
    public function poolDebug() {}

    /**
     * (PECL mongo &gt;= 1.1.0)<br/>
     * Change slaveOkay setting for this connection
     * @link http://php.net/manual/en/mongo.setslaveokay.php
     * @param bool $ok [optional] <p class="para">
     * If reads should be sent to secondary members of a replica set for all
     * possible queries using this {@see MongoClient} instance.
     * </p>
     * @return bool returns the former value of slaveOkay for this instance.
     */
    public function setSlaveOkay($ok = true)
    {
        $result = $this->setSlaveOkayFromParameter($ok);
        return $result;
    }

    /**
     * @deprecated Relying on this feature is highly discouraged. Please use MongoPool::setSize() instead.
     *(PECL mongo &gt;= 1.2.0)<br/>
     * Set the size for future connection pools.
     * @link http://php.net/manual/en/mongo.setpoolsize.php
     * @param $size <p>The max number of connections future pools will be able to create. Negative numbers mean that the pool will spawn an infinite number of connections.</p>
     * @return bool Returns the former value of pool size.
     */
    public function setPoolSize($size) {}
    /**
     * Creates a persistent connection with a database server
     * @link http://www.php.net/manual/en/mongo.persistconnect.php
     * @deprecated Pass array("persist" => $id) to the constructor instead of using this method.
     * @param string $username A username used to identify the connection.
     * @param string $password A password used to identify the connection.
     * @throws MongoConnectionException
     * @return boolean If the connection was successful.
     */
    public function persistConnect($username = "", $password = "") {}

    /**
     * Creates a persistent connection with paired database servers
     * @deprecated Pass "mongodb://server1,server2" and array("persist" => $id) to the constructor instead of using this method.
     * @link http://www.php.net/manual/en/mongo.pairpersistconnect.php
     * @param string $username A username used to identify the connection.
     * @param string $password A password used to identify the connection.
     * @throws MongoConnectionException
     * @return boolean If the connection was successful.
     */
    public function pairPersistConnect($username = "", $password = "") {}

    /**
     * Connects with a database server
     *
     * @link http://www.php.net/manual/en/mongo.connectutil.php
     * @throws MongoConnectionException
     * @return boolean If the connection was successful.
     */
    protected function connectUtil() {}

    /**
     * Check if there was an error on the most recent db operation performed
     * @deprecated Use MongoDB::lastError() instead.
     * @link http://www.php.net/manual/en/mongo.lasterror.php
     * @return array|null Returns the error, if there was one, or NULL.
     */
    public function lastError() {}

    /**
     * Checks for the last error thrown during a database operation
     * @deprecated Use MongoDB::prevError() instead.
     * @link http://www.php.net/manual/en/mongo.preverror.php
     * @return array Returns the error and the number of operations ago it occurred.
     */
    public function prevError() {}

    /**
     * Clears any flagged errors on the connection
     * @deprecated Use MongoDB::resetError() instead.
     * @link http://www.php.net/manual/en/mongo.reseterror.php
     * @return array Returns the database response.
     */
    public function resetError() {}

    /**
     * Creates a database error on the database.
     * @deprecated Use MongoDB::forceError() instead.
     * @link http://www.php.net/manual/en/mongo.forceerror.php
     * @return boolean The database response.
     */
    public function forceError() {}

    /**
     * Choose a new secondary for slaveOkay reads
     *
     * @link www.php.net/manual/en/mongo.switchslave.php
     * @return string The address of the secondary this connection is using for reads. This may be the same as the previous address as addresses are randomly chosen. It may return only one address if only one secondary (or only the primary) is available.
     * @throws MongoException (error code 15) if it is called on a non-replica-set connection. It will also throw MongoExceptions if it cannot find anyone (primary or secondary) to read from (error code 16).
     */
    public function switchSlave()
    {
        return $this->server;
    }
}
