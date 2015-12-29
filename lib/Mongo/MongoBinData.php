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

class MongoBinData {
    /**
     * Generic binary data.
     * @link http://php.net/manual/en/class.mongobindata.php#mongobindata.constants.custom
     */
    const GENERIC = 0x0;

    /**
     * Function
     * @link http://php.net/manual/en/class.mongobindata.php#mongobindata.constants.func
     */
    const FUNC = 0x1;

    /**
     * Generic binary data (deprecated in favor of MongoBinData::GENERIC)
     * @link http://php.net/manual/en/class.mongobindata.php#mongobindata.constants.byte-array
     */
    const BYTE_ARRAY = 0x2;

    /**
     * Universally unique identifier (deprecated in favor of MongoBinData::UUID_RFC4122)
     * @link http://php.net/manual/en/class.mongobindata.php#mongobindata.constants.uuid
     */
    const UUID = 0x3;

    /**
     * Universally unique identifier (according to » RFC 4122)
     * @link http://php.net/manual/en/class.mongobindata.php#mongobindata.constants.custom
     */
    const UUID_RFC4122 = 0x4;


    /**
     * MD5
     * @link http://php.net/manual/en/class.mongobindata.php#mongobindata.constants.md5
     */
    const MD5 = 0x5;

    /**
     * User-defined type
     * @link http://php.net/manual/en/class.mongobindata.php#mongobindata.constants.custom
     */
    const CUSTOM = 0x80;


    /**
     * @link http://php.net/manual/en/class.mongobindata.php#mongobindata.props.bin
     * @var $bin
     */
    public $bin;

    /**
     * @link http://php.net/manual/en/class.mongobindata.php#mongobindata.props.type
     * @var $type
     */
    public $type;


    /**
     * Creates a new binary data object.
     *
     * @link http://php.net/manual/en/mongobindata.construct.php
     * @param string $data Binary data
     * @param int $type Data type
     * @return MongoBinData Returns a new binary data object
     */
    public function __construct($data, $type = 2) {}

    /**
     * Returns the string representation of this binary data object.
     * @return string
     */
    public function __toString() {}
}
