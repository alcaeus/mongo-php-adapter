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

trait WriteConcernConverter
{
    /**
     * @param string|int|bool $wstring
     * @param int $wtimeout
     * @return \MongoDB\Driver\WriteConcern
     */
    protected function createWriteConcernFromParameters($wstring, $wtimeout)
    {
        // Convert legacy write concern
        if (is_bool($wstring)) {
            $wstring = (int) $wstring;
        }

        if (! is_string($wstring) && ! is_int($wstring)) {
            trigger_error("w for WriteConcern must be a string or integer", E_USER_WARNING);
            return false;
        }

        // Ensure wtimeout is not < 0
        return new \MongoDB\Driver\WriteConcern($wstring, max($wtimeout, 0));
    }

    /**
     * @param array $writeConcernArray
     * @return \MongoDB\Driver\WriteConcern
     */
    protected function createWriteConcernFromArray($writeConcernArray)
    {
        $wstring = isset($writeConcernArray['w']) ? $writeConcernArray['w'] : 1;
        $wtimeout = isset($writeConcernArray['wtimeout']) ? $writeConcernArray['wtimeout'] : 0;

        return $this->createWriteConcernFromParameters($wstring, $wtimeout);
    }
}
