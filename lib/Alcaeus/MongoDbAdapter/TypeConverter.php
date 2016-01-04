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

/**
 * @internal
 */
class TypeConverter
{
    public static function convertLegacyArrayToObject($array)
    {
        // TODO: provide actual class once mongodb/mongo-php-library#78 has been merged
        $result = [];

        foreach ($array as $key => $value) {
            $result[$key] = (is_array($value)) ? static::convertLegacyArrayToObject($value) : static::convertToBSONType($value);
        }

        return self::ensureCorrectType($result);
    }

    public static function convertObjectToLegacyArray($object)
    {
        $result = [];

        foreach ($object as $key => $value) {
            // TODO: use actual class instead of \stdClass once mongodb/mongo-php-library#78 has been merged
            $result[$key] = ($value instanceof \stdClass || is_array($value)) ? static::convertObjectToLegacyArray($value) : static::convertToLegacyType($value);
        }

        return $result;
    }

    public static function convertToLegacyType($value)
    {
        switch (true) {
            case $value instanceof \MongoDB\BSON\ObjectID:
                return new \MongoId($value);
            case $value instanceof \MongoDB\BSON\Binary:
                return new \MongoBinData($value);
            case $value instanceof \MongoDB\BSON\Javascript:
                return new \MongoCode($value);
            case $value instanceof \MongoDB\BSON\MaxKey:
                return new \MongoMaxKey();
            case $value instanceof \MongoDB\BSON\MinKey:
                return new \MongoMinKey();
            case $value instanceof \MongoDB\BSON\Regex:
                return new \MongoRegex($value);
            case $value instanceof \MongoDB\BSON\Timestamp:
                return new \MongoTimestamp($value);
            case $value instanceof \MongoDB\BSON\UTCDatetime:
                return new \MongoDate($value);
            default:
                return $value;
        }
    }

    public static function convertToBSONType($value)
    {
        switch (true) {
            case $value instanceof TypeInterface:
                return $value->toBSONType();

            default:
                return $value;
        }
    }

    /**
     * @param array $array
     * @return bool
     */
    public static function isNumericArray(array $array)
    {
        return $array === [] || is_numeric(array_keys($array)[0]);
    }

    /**
     * Converts all arrays with non-numeric keys to stdClass
     *
     * @param array $array
     * @return array|\stdClass
     */
    private static function ensureCorrectType(array $array)
    {
        // Empty arrays are left untouched since they may be an empty list or empty document
        if (static::isNumericArray($array)) {
            return $array;
        }

        // Can convert array to stdClass
        $object = new \stdClass();
        foreach ($array as $key => $value) {
            $object->$key = $value;
        }

        return $object;
    }
}
