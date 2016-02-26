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

if (class_exists('MongoDBRef', false)) {
    return;
}

class MongoDBRef
{
    /**
     * @static
     * @var $refKey
     */
    protected static $refKey = '$ref';

    /**
     * @static
     * @var $idKey
     */
    protected static $idKey = '$id';

    /**
     * If no database is given, the current database is used.
     *
     * @link http://php.net/manual/en/mongodbref.create.php
     * @static
     * @param string $collection Collection name (without the database name)
     * @param mixed $id The _id field of the object to which to link
     * @param string $database Database name
     * @return array Returns the reference
     */
    public static function create($collection, $id, $database = null)
    {
        $ref = [
            static::$refKey => $collection,
            static::$idKey => $id
        ];

        if ($database !== null) {
            $ref['$db'] = $database;
        }

        return $ref;
    }

    /**
     * This not actually follow the reference, so it does not determine if it is broken or not.
     * It merely checks that $ref is in valid database reference format (in that it is an object or array with $ref and $id fields).
     *
     * @link http://php.net/manual/en/mongodbref.isref.php
     * @static
     * @param mixed $ref Array or object to check
     * @return boolean Returns true if $ref is a reference
     */
    public static function isRef($ref)
    {
        $check = (array) $ref;

        return array_key_exists(static::$refKey, $check) && array_key_exists(static::$idKey, $check);
    }

    /**
     * Fetches the object pointed to by a reference
     * @link http://php.net/manual/en/mongodbref.get.php
     * @static
     * @param MongoDB $db Database to use
     * @param array $ref Reference to fetch
     * @return array|null Returns the document to which the reference refers or null if the document does not exist (the reference is broken)
     */
    public static function get($db, $ref)
    {
        if (! static::isRef($ref)) {
            return null;
        }

        return $db->selectCollection($ref[static::$refKey])->findOne(['_id' => $ref[static::$idKey]]);
    }
}
