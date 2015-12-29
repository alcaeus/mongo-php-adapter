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

class MongoDate {
    /**
     * @link http://php.net/manual/en/class.mongodate.php#mongodate.props.sec
     * @var int $sec
     */
    public $sec;

    /**
     * @link http://php.net/manual/en/class.mongodate.php#mongodate.props.usec
     * @var int $usec
     */
    public $usec;

    /**
     * Creates a new date. If no parameters are given, the current time is used.
     *
     * @link http://php.net/manual/en/mongodate.construct.php
     * @param int $sec Number of seconds since January 1st, 1970
     * @param int $usec Microseconds
     * @return MongoDate Returns this new date
     */
    public function __construct($sec = 0, $usec = 0) {}

    /**
     * Returns a DateTime object representing this date
     * @link http://php.net/manual/en/mongodate.todatetime.php
     * @return DateTime
     */
    public function toDateTime() {}

    /**
     * Returns a string representation of this date
     * @return string
     */
    public function __toString() {}
}
