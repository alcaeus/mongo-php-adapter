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

class MongoLog {
    /**
     * @link http://php.net/manual/en/class.mongolog.php#mongolog.constants.none
     */
    const NONE = 0;

    /**
     * @link http://php.net/manual/en/class.mongolog.php#mongolog.constants.all
     */
    const ALL = 0;

    /**
     * @link http://php.net/manual/en/class.mongolog.php#mongolog.constants.warning
     */
    const WARNING = 0;

    /**
     * @link http://php.net/manual/en/class.mongolog.php#mongolog.constants.info
     */
    const INFO = 0;

    /**
     * @link http://php.net/manual/en/class.mongolog.php#mongolog.constants.fine
     */
    const FINE = 0;

    /**
     * @link http://php.net/manual/en/class.mongolog.php#mongolog.constants.rs
     */
    const RS = 0;

    /**
     * @link http://php.net/manual/en/class.mongolog.php#mongolog.constants.pool
     */
    const POOL = 0;

    /**
     * @link http://php.net/manual/en/class.mongolog.php#mongolog.constants.io
     */
    const IO = 0;

    /**
     * @link http://php.net/manual/en/class.mongolog.php#mongolog.constants.server
     */
    const SERVER = 0;

    /**
     * @link http://php.net/manual/en/class.mongolog.php#mongolog.constants.parse
     */
    const PARSE = 0;

    const CON = 2;

    /**
     * (PECL mongo &gt;= 1.3.0)<br/>
     * <p>
     * This function will set a callback function to be called for {@link http://www.php.net/manual/en/class.mongolog.php MongoLog} events
     * instead of triggering warnings.
     * </p>
     * @link http://www.php.net/manual/en/mongolog.setcallback.php
     * @param callable $log_function   <p>
     * The function to be called on events.
     * </p>
     * <p>
     * The function should have the following prototype
     * </p>
     *
     * <em>log_function</em> ( <em>int</em> <em>$module</em> , <em>int</em> <em>$level</em>, <em>string</em> <em>$message</em>)
     * <ul>
     * <li>
     * <b><i>module</i></b>
     *
     * <p>One of the {@link http://www.php.net/manual/en/class.mongolog.php#mongolog.constants.module MongoLog module constants}.</p>
     * </li>
     * <li>
     * <b><i>level</i></b>
     *
     * <p>One of the {@link http://www.php.net/manual/en/class.mongolog.php#mongolog.constants.level MongoLog level constants}.</p>
     * </li
     * <li>
     * <b><i>message</i></b>
     *
     * <p>The log message itself.</p></li>
     * <ul>
     * @return boolean Returns <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public static function setCallback ( callable $log_function ) {}

    /**
     * This function can be used to set how verbose logging should be and the types of
     * activities that should be logged. Use the constants described in the MongoLog
     * section with bitwise operators to specify levels.
     *
     * @link http://php.net/manual/en/mongolog.setlevel.php
     * @static
     * @param int $level The levels you would like to log
     * @return void
     */
    public static function setLevel($level) {}

    /**
     * This can be used to see the log level. Use the constants described in the
     * MongoLog section with bitwise operators to check the level.
     *
     * @link http://php.net/manual/en/mongolog.getlevel.php
     * @static
     * @return int Returns the current level
     */
    public static function getLevel() {}

    /**
     * This function can be used to set which parts of the driver's functionality
     * should be logged. Use the constants described in the MongoLog section with
     * bitwise operators to specify modules.
     *
     * @link http://php.net/manual/en/mongolog.setmodule.php
     * @static
     * @param int $module The module(s) you would like to log
     * @return void
     */
    public static function setModule($module) {}

    /**
     * This function can be used to see which parts of the driver's functionality are
     * being logged. Use the constants described in the MongoLog section with bitwise
     * operators to check if specific modules are being logged.
     *
     * @link http://php.net/manual/en/mongolog.getmodule.php
     * @static
     * @return int Returns the modules currently being logged
     */
    public static function getModule() {}
}
