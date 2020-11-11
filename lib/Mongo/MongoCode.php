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

use Alcaeus\MongoDbAdapter\TypeConverter;

if (class_exists('MongoCode', false)) {
    return;
}

class MongoCode implements \Alcaeus\MongoDbAdapter\TypeInterface
{
    /**
     * @var string
     */
    private $code;

    /**
     * @var array|null
     */
    private $scope;

    /**
     * @link http://php.net/manual/en/mongocode.construct.php
     * @param string $code A string of code
     * @param array $scope The scope to use for the code
     */
    public function __construct($code, array $scope = [])
    {
        if ($code instanceof \MongoDB\BSON\Javascript) {
            $javascript = $code;
            $code = $javascript->getCode();
            $scope = TypeConverter::toLegacy($javascript->getScope());
        }

        $this->code = $code;
        $this->scope = $scope;
    }

    /**
     * Returns this code as a string
     * @return string
     */
    public function __toString()
    {
        return $this->code;
    }

    /**
     * Converts this MongoCode to the new BSON JavaScript type
     *
     * @return \MongoDB\BSON\Javascript
     * @internal This method is not part of the ext-mongo API
     */
    public function toBSONType()
    {
        return new \MongoDB\BSON\Javascript($this->code, !empty($this->scope) ? $this->scope : null);
    }
}
