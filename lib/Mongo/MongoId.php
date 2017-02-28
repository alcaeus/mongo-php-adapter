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

if (class_exists('MongoId', false)) {
    return;
}

use Alcaeus\MongoDbAdapter\TypeInterface;
use MongoDB\BSON\ObjectID;

class MongoId implements Serializable, TypeInterface, JsonSerializable
{
    /*
     * @var ObjectID
     */
    private $objectID;

    /**
     * Creates a new id
     *
     *
     * @link http://www.php.net/manual/en/mongoid.construct.php
     * @param string $id [optional] A string to use as the id. Must be 24 hexidecimal characters. If an invalid string is passed to this constructor, the constructor will ignore it and create a new id value.
     *
     * @throws MongoException
     */
    public function __construct($id = null)
    {
        $this->createObjectID($id);
    }

    /**
     * Check if a value is a valid ObjectId
     *
     * @link http://php.net/manual/en/mongoid.isvalid.php
     * @param mixed $value The value to check for validity.
     * @return bool
     */
    public static function isValid($value)
    {
        if ($value instanceof ObjectID || $value instanceof MongoId) {
            return true;
        } elseif (! is_string($value)) {
            return false;
        }

        return (bool) preg_match('#^[a-f0-9]{24}$#i', $value);
    }

    /**
     * Returns a hexidecimal representation of this id
     * @link http://www.php.net/manual/en/mongoid.tostring.php
     * @return string
     */
    public function __toString()
    {
        return (string) $this->objectID;
    }

    /**
     * Converts this MongoId to the new BSON ObjectID type
     *
     * @return ObjectID
     * @internal This method is not part of the ext-mongo API
     */
    public function toBSONType()
    {
        return $this->objectID;
    }

    /**
     * @param string $name
     *
     * @return null|string
     */
    public function __get($name)
    {
        if ($name === '$id') {
            return (string) $this->objectID;
        }

        return null;
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        if ($name === 'id') {
            trigger_error("The '\$id' property is read-only", E_USER_DEPRECATED);
            return;
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return $name === 'id';
    }

    /**
     * @param string $name
     */
    public function __unset($name)
    {
        if ($name === 'id') {
            trigger_error("The '\$id' property is read-only", E_USER_DEPRECATED);
            return;
        }
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return (string) $this->objectID;
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $this->createObjectID($serialized);
    }

    /**
     * Gets the incremented value to create this id
     * @link http://php.net/manual/en/mongoid.getinc.php
     * @return int Returns the incremented value used to create this MongoId.
     */
    public function getInc()
    {
        return hexdec(substr((string) $this->objectID, -6));
    }

    /**
     * (PECL mongo &gt;= 1.0.11)
     * Gets the process ID
     * @link http://php.net/manual/en/mongoid.getpid.php
     * @return int Returns the PID of the MongoId.
     */
    public function getPID()
    {
        $id = (string) $this->objectID;

        // PID is stored as little-endian, flip it around
        $pid = substr($id, 16, 2) . substr($id, 14, 2);
        return hexdec($pid);
    }

    /**
     * (PECL mongo &gt;= 1.0.1)
     * Gets the number of seconds since the epoch that this id was created
     * @link http://www.php.net/manual/en/mongoid.gettimestamp.php
     * @return int
     */
    public function getTimestamp()
    {
        return hexdec(substr((string) $this->objectID, 0, 8));
    }

    /**
     * Gets the hostname being used for this machine's ids
     * @link http://www.php.net/manual/en/mongoid.gethostname.php
     * @return string
     */
    public static function getHostname()
    {
        return gethostname();
    }

    /**
     * (PECL mongo &gt;= 1.0.8)
     * Create a dummy MongoId
     * @link http://php.net/manual/en/mongoid.set-state.php
     * @param array $props <p>Theoretically, an array of properties used to create the new id. However, as MongoId instances have no properties, this is not used.</p>
     * @return MongoId A new id with the value "000000000000000000000000".
     */
    public static function __set_state(array $props)
    {
    }

    /**
     * @return stdClass
     */
    public function jsonSerialize()
    {
        $object = new stdClass();
        $object->{'$id'} = (string) $this->objectID;
        return $object;
    }

    /**
     * @param $id
     * @throws MongoException
     */
    private function createObjectID($id)
    {
        try {
            if (is_string($id)) {
                $this->objectID = new ObjectID($id);
            } elseif ($id instanceof self || $id instanceof ObjectID) {
                $this->objectID = new ObjectID((string) $id);
            } else {
                $this->objectID = new ObjectID();
            }
        } catch (\Exception $e) {
            throw new MongoException('Invalid object ID', 19);
        }
    }
}
