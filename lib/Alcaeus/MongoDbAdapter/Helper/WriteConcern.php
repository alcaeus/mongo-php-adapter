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

namespace Alcaeus\MongoDbAdapter\Helper;

/**
 * @internal
 */
trait WriteConcern
{
    use WriteConcernConverter;

    /**
     * @var \MongoDB\Driver\WriteConcern
     */
    protected $writeConcern;

    /**
     * @param $wstring
     * @param int $wtimeout
     * @return bool
     */
    abstract public function setWriteConcern($wstring, $wtimeout = 0);

    /**
     * @return array
     */
    public function getWriteConcern()
    {
        if ($this->writeConcern === null) {
            $this->writeConcern = new \MongoDB\Driver\WriteConcern(1);
        }

        return [
            'w' => $this->writeConcern->getW(),
            'wtimeout' => $this->writeConcern->getWtimeout(),
        ];
    }

    /**
     * @param string|int $wstring
     * @param int $wtimeout
     * @return bool
     */
    protected function setWriteConcernFromParameters($wstring, $wtimeout = 0)
    {
        $this->writeConcern = $this->createWriteConcernFromParameters($wstring, $wtimeout);

        return true;
    }

    /**
     * @param array $writeConcernArray
     * @return bool
     */
    protected function setWriteConcernFromArray($writeConcernArray)
    {
        $this->writeConcern = $this->createWriteConcernFromArray($writeConcernArray);

        return true;
    }
}
