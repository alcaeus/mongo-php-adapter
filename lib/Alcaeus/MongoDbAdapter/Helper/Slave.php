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
trait Slave
{

    /**
     * @link http://www.php.net/manual/en/mongocollection.setslaveokay.php
     * @param bool $ok
     * @return bool
     */
    abstract public function setSlaveOkay($ok = true);


	/**
     * @link http://www.php.net/manual/en/mongocollection.getslaveokay.php
     * @return bool
     */
    public function getSlaveOkay()
    {
        return $this->readPreference->getMode() != \MongoDB\Driver\ReadPreference::RP_PRIMARY;
    }

    private function setSlaveOkayFromParameter($ok = true)
    {
    	$result = $this->getSlaveOkay();
        $this->readPreference = new \MongoDB\Driver\ReadPreference(
            $ok ? \MongoDB\Driver\ReadPreference::RP_SECONDARY_PREFERRED : \MongoDB\Driver\ReadPreference::RP_PRIMARY,
            $this->readPreference->getTagSets()
        );
        return $result;
    }
}
