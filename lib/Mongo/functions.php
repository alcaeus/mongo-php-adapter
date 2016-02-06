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

use Alcaeus\MongoDbAdapter\TypeConverter;

if (! function_exists('bson_decode')) {
    /**
     * Deserializes a BSON object into a PHP array
     *
     * @param string $bson The BSON to be deserialized.
     * @return array Returns the deserialized BSON object.
     */
    function bson_decode($bson)
    {
        return TypeConverter::toLegacy(\MongoDB\BSON\toPHP($bson));
    }
}

if (! function_exists('bson_encode')) {
    /**
     * Serializes a PHP variable into a BSON string
     *
     * @param mixed $anything The variable to be serialized.
     * @return string Returns the serialized string.
     */
    function bson_encode($anything)
    {
        return \MongoDB\BSON\fromPHP(TypeConverter::fromLegacy($anything));
    }
}
