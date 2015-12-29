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

class MongoTimestamp {
    /**
     * @link http://php.net/manual/en/class.mongotimestamp.php#mongotimestamp.props.sec
     * @var $sec
     */
    public $sec;

    /**
     * @link http://php.net/manual/en/class.mongotimestamp.php#mongotimestamp.props.inc
     * @var $inc
     */
    public $inc;

    /**
     * Creates a new timestamp. If no parameters are given, the current time is used
     * and the increment is automatically provided. The increment is set to 0 when the
     * module is loaded and is incremented every time this constructor is called
     * (without the $inc parameter passed in).
     *
     * @link http://php.net/manual/en/mongotimestamp.construct.php
     * @param int $sec [optional] Number of seconds since January 1st, 1970
     * @param int $inc [optional] Increment
     */
    public function __construct($sec = 0, $inc) {}

    /**
     * @return string
     */
    public function __toString() {}
}
