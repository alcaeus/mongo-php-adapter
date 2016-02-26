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

if (class_exists('MongoCursorInterface', false)) {
    return;
}

interface MongoCursorInterface extends Iterator
{
    /**
     * @param int $batchSize
     * @return MongoCursorInterface
     */
    public function batchSize($batchSize);

    /**
     * @return bool
     */
    public function dead();

    /**
     * @return array
     */
    public function info();

    /**
     * @return array
     */
    public function getReadPreference();

    /**
     * @param string $read_preference
     * @param array|null $tags
     * @return MongoCursorInterface
     */
    public function setReadPreference($read_preference, $tags = null);

    /**
     * @param int $ms
     * @return MongoCursorInterface
     */
    public function timeout($ms);
}
