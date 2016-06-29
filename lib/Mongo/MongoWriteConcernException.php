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

if (class_exists('MongoWriteConcernException', false)) {
    return;
}

/**
 * <p>(PECL mongo &gt;= 1.5.0)</p>
 * @link http://php.net/manual/en/class.mongowriteconcernexception.php#class.mongowriteconcernexception
 */
class MongoWriteConcernException extends MongoCursorException
{
    private $document;

    /**
     * MongoWriteConcernException constructor.
     *
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     * @param null $document
     *
     * @internal The $document parameter is not part of the ext-mongo API
     */
    public function __construct($message = '', $code = 0, Exception $previous = null, $document = null)
    {
        parent::__construct($message, $code, $previous);

        $this->document = $document;
    }

    /**
     * Get the error document
     * @link http://php.net/manual/en/mongowriteconcernexception.getdocument.php
     * @return array <p>A MongoDB document, if available, as an array.</p>
     */
    public function getDocument()
    {
        return $this->document;
    }
}
