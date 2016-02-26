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

if (class_exists('MongoUpdateBatch', false)) {
    return;
}

/**
 * Constructs a batch of UPDATE operations
 *
 * @see http://php.net/manual/en/class.mongoupdatebatch.php
 * @see http://php.net/manual/en/class.mongowritebatch.php
 */
class MongoUpdateBatch extends MongoWriteBatch
{
    /**
     * Creates a new batch of update operations
     *
     * @see http://php.net/manual/en/mongoupdatebatch.construct.php
     * @param MongoCollection $collection
     * @param array $writeOptions
     */
    public function __construct(MongoCollection $collection, array $writeOptions = [])
    {
        parent::__construct($collection, self::COMMAND_UPDATE, $writeOptions);
    }
}
