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
        // TODO: provide actual class
        $result = new \stdClass();

        foreach ($array as $key => $value) {
            $result->$key = (is_array($value)) ? static::convertLegacyArrayToObject($value) : static::convertToModernType($value);
        }

        return $result;
    }

    public static function convertObjectToLegacyArray($object)
    {
        $result = [];

        foreach ($object as $key => $value) {
            // TODO: maybe add a more meaningful check instead of stdClass?
            $result[$key] = ($value instanceof \stdClass) ? static::convertObjectToLegacyArray($value) : static::convertToLegacyType($value);
        }

        return $result;
    }

    public static function convertToLegacyType($value)
    {
        switch (true) {
            case $value instanceof \MongoDB\BSON\ObjectID:
                return new \MongoId($value);

            default:
                return $value;
        }
    }

    public static function convertToModernType($value)
    {
        switch (true) {
            case $value instanceof \MongoId:
                return $value->getObjectID();

            default:
                return $value;
        }
    }
}
