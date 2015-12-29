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

class MongoRegex {
    /**
     * @link http://php.net/manual/en/class.mongoregex.php#mongoregex.props.regex
     * @var $regex
     */
    public $regex;

    /**
     * @link http://php.net/manual/en/class.mongoregex.php#mongoregex.props.flags
     * @var $flags
     */
    public $flags;

    /**
     * Creates a new regular expression.
     *
     * @link http://php.net/manual/en/mongoregex.construct.php
     * @param string $regex Regular expression string of the form /expr/flags
     * @return MongoRegex Returns a new regular expression
     */
    public function __construct($regex) {}

    /**
     * Returns a string representation of this regular expression.
     * @return string This regular expression in the form "/expr/flags".
     */
    public function __toString() {}
}
