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

if (class_exists('MongoCommandCursor', false)) {
    return;
}

use Alcaeus\MongoDbAdapter\AbstractCursor;
use Alcaeus\MongoDbAdapter\TypeConverter;

class MongoCommandCursor extends AbstractCursor implements MongoCursorInterface
{
    /**
     * @var array
     */
    private $command;

    /**
     * MongoCommandCursor constructor.
     * @param MongoClient $connection
     * @param string $ns
     * @param array $command
     */
    public function __construct(MongoClient $connection, $ns, array $command = [])
    {
        parent::__construct($connection, $ns);

        $this->command = $command;
    }

    /**
     * @param MongoClient $connection
     * @param string $hash
     * @param array $document
     * @return MongoCommandCursor
     */
    public static function createFromDocument(MongoClient $connection, $hash, array $document)
    {
        throw new \Exception('Not implemented');
    }

    /**
     * @return \MongoDB\Driver\Cursor
     */
    protected function ensureCursor()
    {
        if ($this->cursor === null) {
            $convertedCommand = TypeConverter::fromLegacy($this->command);
            if (isset($convertedCommand->cursor)) {
                if ($convertedCommand->cursor === true || $convertedCommand->cursor === []) {
                    $convertedCommand->cursor = new \stdClass();
                }
            }

            $originalReadPreference = null;
            if (!$this->supportsReadPreference()) {
                $originalReadPreference = $this->readPreference;
                $this->setReadPreference(\MongoClient::RP_PRIMARY);
            }

            try {
                $this->cursor = $this->db->command($convertedCommand, $this->getOptions());
            } finally {
                if ($originalReadPreference) {
                    $this->readPreference = $originalReadPreference;
                }
            }
        }

        return $this->cursor;
    }

    /**
     * @return array
     */
    protected function getCursorInfo()
    {
        return [
            'ns' => $this->ns,
            'limit' => 0,
            'batchSize' => $this->batchSize,
            'skip' => 0,
            'flags' => 0,
            'query' => $this->command,
            'fields' => null,
        ];
    }

    /**
     * @return array
     */
    protected function getIterationInfo()
    {
        $iterationInfo = parent::getIterationInfo();

        if ($iterationInfo['started_iterating']) {
            $iterationInfo += [
                'firstBatchAt' => $iterationInfo['at'],
                'firstBatchNumReturned' => $iterationInfo['numReturned'],
            ];
            $iterationInfo['at'] = 0;
            $iterationInfo['numReturned'] = 0;
        }

        return $iterationInfo;
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        return ['command'] + parent::__sleep();
    }

    /**
     * @see https://github.com/mongodb/mongo-php-driver-legacy/blob/1.6.14/db.c#L51
     * @return bool
     */
    private function supportsReadPreference()
    {
        if ($this->command === []) {
            return false;
        }

        $firstKey = array_keys($this->command)[0];
        switch ($firstKey) {
            case 'count':
            case 'group':
            case 'dbStats':
            case 'geoNear':
            case 'geoWalk':
            case 'distinct':
            case 'aggregate':
            case 'collStats':
            case 'geoSearch':
            case 'parallelCollectionScan':
                return true;

            case 'mapreduce':
            case 'mapReduce':
                return (isset($this->command['out']) &&
                    is_array($this->command['out']) &&
                    array_key_exists('inline', $this->command['out']));

            default:
                return false;
        }
    }
}
