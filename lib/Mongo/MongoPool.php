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

class MongoPool {
    /**
     * Returns an array of information about all connection pools.
     *
     * @link http://php.net/manual/en/mongopool.info.php
     * @static
     * @return array Each connection pool has an identifier, which starts with the host. For
     *         each pool, this function shows the following fields: $in use The number of
     *         connections currently being used by Mongo instances. $in pool The number of
     *         connections currently in the pool (not being used). $remaining The number of
     *         connections that could be created by this pool. For example, suppose a pool had
     *         5 connections remaining and 3 connections in the pool. We could create 8 new
     *         instances of Mongo before we exhausted this pool (assuming no instances of Mongo
     *         went out of scope, returning their connections to the pool). A negative number
     *         means that this pool will spawn unlimited connections. Before a pool is created,
     *         you can change the max number of connections by calling Mongo::setPoolSize. Once
     *         a pool is showing up in the output of this function, its size cannot be changed.
     *         $total The total number of connections allowed for this pool. This should be
     *         greater than or equal to "in use" + "in pool" (or -1). $timeout The socket
     *         timeout for connections in this pool. This is how long connections in this pool
     *         will attempt to connect to a server before giving up. $waiting If you have
     *         capped the pool size, workers requesting connections from the pool may block
     *         until other workers return their connections. This field shows how many
     *         milliseconds workers have blocked for connections to be released. If this number
     *         keeps increasing, you may want to use MongoPool::setSize to add more connections
     *         to your pool
     */
    public static function info() {}

    /**
     * Sets the max number of connections new pools will be able to create.
     *
     * @link http://php.net/manual/en/mongopool.setsize.php
     * @static
     * @param int $size The max number of connections future pools will be able to
     *        create. Negative numbers mean that the pool will spawn an infinite number of
     *        connections
     * @return boolean Returns the former value of pool size
     */
    public static function setSize($size) {}

    /**
     * .
     *
     * @link http://php.net/manual/en/mongopool.getsize.php
     * @static
     * @return int Returns the current pool size
     */
    public static function getSize() {}
}
