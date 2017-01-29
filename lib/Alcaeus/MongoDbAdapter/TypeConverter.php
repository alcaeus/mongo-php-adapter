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

namespace Alcaeus\MongoDbAdapter;

use MongoDB\BSON;
use MongoDB\Model;

/**
 * @internal
 */
class TypeConverter
{
    /**
     * Converts a legacy type to the new BSON type
     *
     * This method handles type conversion from ext-mongo to ext-mongodb:
     *  - For all types (MongoId, MongoDate, etc.) it returns the correct BSON
     *    object instance
     *  - For arrays and objects it iterates over properties and converts each
     *    item individually
     *  - For other types it returns the value unconverted
     *
     * @param mixed $value
     * @return mixed
     */
    public static function fromLegacy($value)
    {
        switch (true) {
            case $value instanceof TypeInterface:
                return $value->toBSONType();
            case $value instanceof BSON\Type:
                return $value;
            case is_array($value):
            case is_object($value);
                $result = [];

                foreach ($value as $key => $item) {
                    $result[$key] = self::fromLegacy($item);
                }

                return self::ensureCorrectType($result, is_object($value));
            default:
                return $value;
        }
    }

    /**
     * Converts a BSON type to the legacy types
     *
     * This method handles type conversion from ext-mongodb to ext-mongo:
     *  - For all instances of BSON\Type it returns an object of the
     *    corresponding legacy type (MongoId, MongoDate, etc.)
     *  - For arrays and objects it iterates over properties and converts each
     *    item individually
     *  - For other types it returns the value unconverted
     *
     * @param mixed $value
     * @return mixed
     */
    public static function toLegacy($value)
    {
        switch (true) {
            case $value instanceof BSON\Type:
                return self::convertBSONObjectToLegacy($value);
            case is_array($value):
            case is_object($value):
                $result = [];

                foreach ($value as $key => $item) {
                    $result[$key] = self::toLegacy($item);
                }

                return $result;
            default:
                return $value;
        }
    }

    /**
     * Converts a projection used in find queries.
     *
     * This method handles conversion from the legacy syntax (e.g. ['x', 'y', 'z'])
     * to the new syntax (e.g. ['x' => true, 'y' => true, 'z' => true]). While
     * this was never documented, the legacy driver applied the same conversion.
     *
     * @param array $fields
     * @return array
     *
     * @throws \MongoException
     */
    public static function convertProjection($fields)
    {
        if (! is_array($fields) || $fields === []) {
            return [];
        }

        if (! TypeConverter::isNumericArray($fields)) {
            $projection = TypeConverter::fromLegacy($fields);
        } else {
            $projection = array_fill_keys(
                array_map(function ($field) {
                    if (!is_string($field)) {
                        throw new \MongoException('field names must be strings', 8);
                    }

                    return $field;
                }, $fields),
                true
            );
        }

        return TypeConverter::fromLegacy($projection);
    }

    /**
     * Helper method to find out if an array has numerical indexes
     *
     * For performance reason, this method checks the first array index only.
     * More thorough inspection of the array might be needed.
     * Note: Returns true for empty arrays to preserve compatibility with empty
     * lists.
     *
     * @param array $array
     * @return bool
     */
    public static function isNumericArray(array $array)
    {
        if ($array === []) {
            return true;
        }

        $keys = array_keys($array);
        // array_keys gives us a clean numeric array with keys, so we expect an
        // array like [0 => 0, 1 => 1, 2 => 2, ..., n => n]
        return array_values($keys) === array_keys($keys);
    }

    /**
     * Converter method to convert a BSON object to its legacy type
     *
     * @param BSON\Type $value
     * @return mixed
     */
    private static function convertBSONObjectToLegacy(BSON\Type $value)
    {
        switch (true) {
            case $value instanceof BSON\ObjectID:
                return new \MongoId($value);
            case $value instanceof BSON\Binary:
                return new \MongoBinData($value);
            case $value instanceof BSON\Javascript:
                return new \MongoCode($value);
            case $value instanceof BSON\MaxKey:
                return new \MongoMaxKey();
            case $value instanceof BSON\MinKey:
                return new \MongoMinKey();
            case $value instanceof BSON\Regex:
                return new \MongoRegex($value);
            case $value instanceof BSON\Timestamp:
                return new \MongoTimestamp($value);
            case $value instanceof BSON\UTCDatetime:
                return new \MongoDate($value);
            case $value instanceof Model\BSONDocument:
            case $value instanceof Model\BSONArray:
                return array_map(
                    ['self', 'toLegacy'],
                    $value->getArrayCopy()
                );
            default:
                return $value;
        }
    }

    /**
     * Converts all arrays with non-numeric keys to stdClass
     *
     * @param array $array
     * @param bool $wasObject
     * @return array|Model\BSONArray|Model\BSONDocument
     */
    private static function ensureCorrectType(array $array, $wasObject = false)
    {
        if ($wasObject || ! static::isNumericArray($array)) {
            return new Model\BSONDocument($array);
        }

        return $array;
    }
}
