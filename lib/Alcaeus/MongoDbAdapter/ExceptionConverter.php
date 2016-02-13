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
        $message = $e->getMessage();
        $code = $e->getCode();

        switch (get_class($e)) {
            case Exception\AuthenticationException::class:
            case Exception\ConnectionException::class:
            case Exception\ConnectionTimeoutException::class:
            case Exception\SSLConnectionException::class:
                $class = 'MongoConnectionException';
                break;

            case Exception\BulkWriteException::class:
            case Exception\WriteException::class:
                $writeResult = $e->getWriteResult();

                if ($writeResult) {
                    $writeError = $writeResult->getWriteErrors()[0];

                    $message = $writeError->getMessage();
                    $code = $writeError->getCode();
                }

                switch ($code) {
                    // see https://github.com/mongodb/mongo-php-driver-legacy/blob/ad3ed45739e9702ae48e53ddfadc482d9c4c7e1c/cursor_shared.c#L540
                    case 11000:
                    case 11001:
                    case 12582:
                        $class = 'MongoDuplicateKeyException';
                        break;
                    default:
                        $class = 'MongoCursorException';
                }
                break;

            case Exception\ExecutionTimeoutException::class:
                $class = 'MongoExecutionTimeoutException';
                break;

            default:
                $class = $fallbackClass;
        }

        if (strpos($message, 'No suitable servers found') !== false) {
            return new \MongoConnectionException($message, $code, $e);
        }

        if ($message === "cannot use 'w' > 1 when a host is not replicated") {
            return new \MongoWriteConcernException($message, $code, $e);
        }

        return new $class($message, $code, $e);
    }

    /**
     * Converts an exception to
     *
     * @param Exception\Exception $e
     * @return array
     */
    public static function toResultArray(Exception\Exception $e)
    {
        return [
            'ok' => 0.0,
            'errmsg' => $e->getMessage(),
            'code' => $e->getCode(),
        ];
    }
}
