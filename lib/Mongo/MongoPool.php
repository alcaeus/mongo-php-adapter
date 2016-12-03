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

if (class_exists('MongoPool', false)) {
    return;
}

/**
 * @deprecated The current (1.3.0+) releases of the driver no longer implements pooling. This class and its methods are therefore deprecated and should not be used.
 */
class MongoPool
{
    /**
     * Returns an array of information about all connection pools.
     *
     * @link http://php.net/manual/en/mongopool.info.php
     * @return array
     */
    public static function info()
    {
        trigger_error('Function MongoPool::info() is deprecated', E_USER_DEPRECATED);
        return [];
    }

    /**
     * Sets the max number of connections new pools will be able to create.
     *
     * @link http://php.net/manual/en/mongopool.setsize.php
     * @param int $size
     * @return boolean Returns the former value of pool size
     */
    public static function setSize($size)
    {
        trigger_error('Function MongoPool::info() is deprecated', E_USER_DEPRECATED);
        return 1;
    }

    /**
     * Get pool size for connection pools
     *
     * @link http://php.net/manual/en/mongopool.getsize.php
     * @return int Returns the current pool size
     */
    public static function getSize()
    {
        trigger_error('Function MongoPool::info() is deprecated', E_USER_DEPRECATED);
        return 1;
    }
}
