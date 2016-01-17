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

use MongoDB\Driver\Exception;

/**
 * @internal
 */
class ExceptionConverter
{
    /**
     * @param Exception\Exception $e
     * @param string $fallbackClass
     *
     * @return \MongoException
     */
    public static function toLegacy(Exception\Exception $e, $fallbackClass = 'MongoException')
    {
        switch (get_class($e)) {
            case Exception\AuthenticationException::class:
            case Exception\ConnectionException::class:
            case Exception\ConnectionTimeoutException::class:
            case Exception\SSLConnectionException::class:
                $class = 'MongoConnectionException';
                break;

            case Exception\BulkWriteException::class:
            case Exception\WriteException::class:
                $class = 'MongoCursorException';
                break;

            case Exception\ExecutionTimeoutException::class:
                $class = 'MongoExecutionTimeoutException';
                break;

            default:
                $class = $fallbackClass;
        }

        if (strpos($e->getMessage(), 'No suitable servers found') !== false) {
            return new \MongoConnectionException($e->getMessage(), $e->getCode(), $e);
        }

        return new $class($e->getMessage(), $e->getCode(), $e);
    }
}
