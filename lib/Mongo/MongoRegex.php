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

if (class_exists('MongoRegex', false)) {
    return;
}

use Alcaeus\MongoDbAdapter\TypeInterface;
use MongoDB\BSON\Regex;

class MongoRegex implements TypeInterface
{
    /**
     * @var string
     */
    public $regex;

    /**
     * @var string
     */
    public $flags;

    /**
     * Creates a new regular expression.
     *
     * @link http://php.net/manual/en/mongoregex.construct.php
     * @param string|Regex $regex Regular expression string of the form /expr/flags
     */
    public function __construct($regex)
    {
        if ($regex instanceof Regex) {
            $this->regex = $regex->getPattern();
            $this->flags = $regex->getFlags();
            return;
        }

        if (! preg_match('#^/(.*)/([imxslu]*)$#', $regex, $matches)) {
            throw new MongoException('invalid regex', 9);
        }

        $this->regex = $matches[1];
        $this->flags = $matches[2];
    }

    /**
     * Returns a string representation of this regular expression.
     * @return string This regular expression in the form "/expr/flags".
     */
    public function __toString()
    {
        return '/' . $this->regex . '/' . $this->flags;
    }

    /**
     * Converts this MongoRegex to the new BSON Regex type
     *
     * @return Regex
     * @internal This method is not part of the ext-mongo API
     */
    public function toBSONType()
    {
        return new Regex($this->regex, $this->flags);
    }
}
