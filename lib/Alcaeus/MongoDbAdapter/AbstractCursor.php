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

namespace Alcaeus\MongoDbAdapter;
use MongoDB\Collection;
use MongoDB\Driver\Cursor;
use MongoDB\Driver\ReadPreference;

/**
 * @internal
 */
abstract class AbstractCursor
{
    /**
     * @var int
     */
    protected $batchSize;

    /**
     * @var Collection
     */
    protected $collection;

    /**
     * @var \MongoClient
     */
    protected $connection;

    /**
     * @var Cursor
     */
    protected $cursor;

    /**
     * @var \MongoDB\Database
     */
    protected $db;

    /**
     * @var \IteratorIterator
     */
    protected $iterator;

    /**
     * @var string
     */
    protected $ns;

    /**
     * @var array
     */
    protected $optionNames = [
        'batchSize',
        'readPreference',
    ];

    /**
     * @var array
     */
    protected $readPreference = [];

    /**
     * @return Cursor
     */
    abstract protected function ensureCursor();

    /**
     * @return array
     */
    abstract protected function getCursorInfo();

    /**
     * Create a new cursor
     * @link http://www.php.net/manual/en/mongocursor.construct.php
     * @param \MongoClient $connection Database connection.
     * @param string $ns Full name of database and collection.
     */
    public function __construct(\MongoClient $connection, $ns)
    {
        $this->connection = $connection;
        $this->ns = $ns;

        $nsParts = explode('.', $ns);
        $dbName = array_shift($nsParts);
        $collectionName = implode('.', $nsParts);

        $this->db = $connection->selectDB($dbName)->getDb();

        if ($collectionName) {
            $this->collection = $connection->selectCollection($dbName, $collectionName)->getCollection();
        }
    }

    /**
     * Returns the current element
     * @link http://www.php.net/manual/en/mongocursor.current.php
     * @return array
     */
    public function current()
    {
        $document = $this->ensureIterator()->current();
        if ($document !== null) {
            $document = TypeConverter::convertObjectToLegacyArray($document);
        }

        return $document;
    }

    /**
     * Returns the current result's _id
     * @link http://www.php.net/manual/en/mongocursor.key.php
     * @return string The current result's _id as a string.
     */
    public function key()
    {
        return $this->ensureIterator()->key();
    }

    /**
     * Advances the cursor to the next result
     * @link http://www.php.net/manual/en/mongocursor.next.php
     * @throws \MongoConnectionException
     * @throws \MongoCursorTimeoutException
     * @return void
     */
    public function next()
    {
        $this->ensureIterator()->next();
    }

    /**
     * Returns the cursor to the beginning of the result set
     * @throws \MongoConnectionException
     * @throws \MongoCursorTimeoutException
     * @return void
     */
    public function rewind()
    {
        // We can recreate the cursor to allow it to be rewound
        $this->reset();
        $this->ensureIterator()->rewind();
    }

    /**
     * Checks if the cursor is reading a valid result.
     * @link http://www.php.net/manual/en/mongocursor.valid.php
     * @return boolean If the current result is not null.
     */
    public function valid()
    {
        return $this->ensureIterator()->valid();
    }

    /**
     * Limits the number of elements returned in one batch.
     *
     * @link http://docs.php.net/manual/en/mongocursor.batchsize.php
     * @param int $batchSize The number of results to return per batch
     * @return $this Returns this cursor.
     */
    public function batchSize($batchSize)
    {
        $this->batchSize = $batchSize;

        return $this;
    }

    /**
     * Checks if there are documents that have not been sent yet from the database for this cursor
     * @link http://www.php.net/manual/en/mongocursor.dead.php
     * @return boolean Returns if there are more results that have not been sent to the client, yet.
     */
    public function dead()
    {
        return $this->ensureCursor()->isDead();
    }

    /**
     * Get the read preference for this query
     * @link http://www.php.net/manual/en/mongocursor.getreadpreference.php
     * @return array
     */
    public function getReadPreference()
    {
        return $this->readPreference;
    }

    /**
     * @return array
     */
    public function info()
    {
        return $this->getCursorInfo() + $this->getIterationInfo();
    }

    /**
     * @link http://www.php.net/manual/en/mongocursor.setreadpreference.php
     * @param string $readPreference
     * @param array $tags
     * @return $this Returns this cursor.
     */
    public function setReadPreference($readPreference, $tags = null)
    {
        $availableReadPreferences = [
            \MongoClient::RP_PRIMARY,
            \MongoClient::RP_PRIMARY_PREFERRED,
            \MongoClient::RP_SECONDARY,
            \MongoClient::RP_SECONDARY_PREFERRED,
            \MongoClient::RP_NEAREST
        ];
        if (! in_array($readPreference, $availableReadPreferences)) {
            trigger_error("The value '$readPreference' is not valid as read preference type", E_WARNING);
            return $this;
        }

        if ($readPreference == \MongoClient::RP_PRIMARY && count($tags)) {
            trigger_error("You can't use read preference tags with a read preference of PRIMARY", E_WARNING);
            return $this;
        }

        $this->readPreference = [
            'type' => $readPreference,
            'tagsets' => $tags
        ];

        return $this;
    }

    /**
     * Sets a client-side timeout for this query
     * @link http://www.php.net/manual/en/mongocursor.timeout.php
     * @param int $ms The number of milliseconds for the cursor to wait for a response. By default, the cursor will wait forever.
     * @return $this Returns this cursor
     */
    public function timeout($ms)
    {
        $this->notImplemented();
    }

    /**
     * Applies all options set on the cursor, overwriting any options that have already been set
     *
     * @param array $optionNames Array of option names to be applied (will be read from properties)
     * @return array
     */
    protected function getOptions($optionNames = null)
    {
        $options = [];

        if ($optionNames === null) {
            $optionNames = $this->optionNames;
        }

        foreach ($optionNames as $option) {
            $converter = 'convert' . ucfirst($option);
            $value = method_exists($this, $converter) ? $this->$converter() : $this->$option;

            if ($value === null) {
                continue;
            }

            $options[$option] = $value;
        }

        return $options;
    }

    /**
     * @return ReadPreference|null
     */
    protected function convertReadPreference()
    {
        $type = array_key_exists('type', $this->readPreference) ? $this->readPreference['type'] : null;
        if ($type === null) {
            return null;
        }

        switch ($type) {
            case \MongoClient::RP_PRIMARY_PREFERRED:
                $mode = ReadPreference::RP_PRIMARY_PREFERRED;
                break;
            case \MongoClient::RP_SECONDARY:
                $mode = ReadPreference::RP_SECONDARY;
                break;
            case \MongoClient::RP_SECONDARY_PREFERRED:
                $mode = ReadPreference::RP_SECONDARY_PREFERRED;
                break;
            case \MongoClient::RP_NEAREST:
                $mode = ReadPreference::RP_NEAREST;
                break;
            default:
                $mode = ReadPreference::RP_PRIMARY;
        }

        $tagSets = array_key_exists('tagsets', $this->readPreference) ? $this->readPreference['tagsets'] : [];

        return new ReadPreference($mode, $tagSets);
    }

    /**
     * @return \IteratorIterator
     */
    protected function ensureIterator()
    {
        if ($this->iterator === null) {
            $this->iterator = new \IteratorIterator($this->ensureCursor());
        }

        return $this->iterator;
    }

    /**
     * @throws \MongoCursorException
     */
    protected function errorIfOpened()
    {
        if ($this->cursor === null) {
            return;
        }

        throw new \MongoCursorException('cannot modify cursor after beginning iteration.');
    }

    /**
     * @return array
     */
    protected function getIterationInfo()
    {
        $iterationInfo = [
            'started_iterating' => $this->cursor !== null,
        ];

        if ($this->cursor !== null) {
            switch ($this->cursor->getServer()->getType()) {
                case \MongoDB\Driver\Server::TYPE_ARBITER:
                    $typeString = 'ARBITER';
                    break;
                case \MongoDB\Driver\Server::TYPE_MONGOS:
                    $typeString = 'MONGOS';
                    break;
                case \MongoDB\Driver\Server::TYPE_PRIMARY:
                    $typeString = 'PRIMARY';
                    break;
                case \MongoDB\Driver\Server::TYPE_SECONDARY:
                    $typeString = 'SECONDARY';
                    break;
                default:
                    $typeString = 'STANDALONE';
            }

            $iterationInfo += [
                'id' => (string)$this->cursor->getId(),
                'at' => null, // @todo Complete info for cursor that is iterating
                'numReturned' => null, // @todo Complete info for cursor that is iterating
                'server' => null, // @todo Complete info for cursor that is iterating
                'host' => $this->cursor->getServer()->getHost(),
                'port' => $this->cursor->getServer()->getPort(),
                'connection_type_desc' => $typeString,
            ];
        }

        return $iterationInfo;
    }

    /**
     * @throws \Exception
     */
    protected function notImplemented()
    {
        throw new \Exception('Not implemented');
    }

    /**
     * Clears the cursor
     *
     * This is generic but implemented as protected since it's only exposed in MongoCursor
     */
    protected function reset()
    {
        $this->cursor = null;
        $this->iterator = null;
    }
}
