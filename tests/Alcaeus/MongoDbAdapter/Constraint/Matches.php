<?php

namespace Alcaeus\MongoDbAdapter\Tests\Constraint;

use LogicException;
use MongoDB\BSON\Int64;
use MongoDB\BSON\Serializable;
use MongoDB\BSON\Type;
use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\ExpectationFailedException;
use RuntimeException;
use SebastianBergmann\Comparator\ComparisonFailure;
use SebastianBergmann\Comparator\Factory;
use function array_keys;
use function count;
use function get_class;
use function gettype;
use function is_array;
use function is_float;
use function is_int;
use function is_object;
use function range;
use function sprintf;
use function strpos;
use const PHP_INT_SIZE;

/**
 * Constraint that checks if one value matches another.
 *
 * The expected value is passed in the constructor. Behavior for allowing extra
 * keys in root documents and processing operators is also configurable.
 */
class Matches extends Constraint
{
    use ConstraintTrait;

    /** @var mixed */
    private $value;

    /** @var bool */
    private $allowExtraRootKeys;

    /** @var bool */
    private $allowExtraKeys;

    /** @var bool */
    private $allowOperators;

    /** @var ComparisonFailure|null */
    private $lastFailure;

    public function __construct($value, $allowExtraRootKeys = true, $allowExtraKeys = false, $allowOperators = true)
    {
        $this->value = self::prepare($value);
        $this->allowExtraRootKeys = $allowExtraRootKeys;
        $this->allowExtraKeys = $allowExtraKeys;
        $this->allowOperators = $allowOperators;
        $this->comparatorFactory = Factory::getInstance();
    }

    private function doEvaluate($other, $description = '', $returnResult = false)
    {
        $other = self::prepare($other);
        $success = false;
        $this->lastFailure = null;

        try {
            $this->assertMatches($this->value, $other);
            $success = true;
        } catch (ExpectationFailedException $e) {
            /* Rethrow internal assertion failures (e.g. operator type checks,
             * EntityMap errors), which are logical errors in the code/test. */
            throw $e;
        } catch (RuntimeException $e) {
            /* This will generally catch internal errors from failAt(), which
             * include a key path to pinpoint the failure. */
            $this->lastFailure = new ComparisonFailure(
                $this->value,
                $other,
                /* TODO: Improve the exporter to canonicalize documents by
                 * sorting keys and remove spl_object_hash from output. */
                $this->exporter()->export($this->value),
                $this->exporter()->export($other),
                false,
                $e->getMessage()
            );
        }

        if ($returnResult) {
            return $success;
        }

        if (! $success) {
            $this->fail($other, $description, $this->lastFailure);
        }
    }

    private function assertEquals($expected, $actual, $keyPath)
    {
        $expectedType = is_object($expected) ? get_class($expected) : gettype($expected);
        $actualType = is_object($actual) ? get_class($actual) : gettype($actual);

        /* Early check to work around ObjectComparator printing the entire value
         * for a failed type comparison. Avoid doing this if either value is
         * numeric to allow for flexible numeric comparisons (e.g. 1 == 1.0). */
        if ($expectedType !== $actualType && ! (self::isNumeric($expected) || self::isNumeric($actual))) {
            self::failAt(sprintf('%s is not expected type "%s"', $actualType, $expectedType), $keyPath);
        }

        try {
            $this->comparatorFactory->getComparatorFor($expected, $actual)->assertEquals($expected, $actual);
        } catch (ComparisonFailure $e) {
            /* Disregard other ComparisonFailure fields, as evaluate() only uses
             * the message when creating its own ComparisonFailure. */
            self::failAt($e->getMessage(), $keyPath);
        }
    }

    private function assertMatches($expected, $actual, $keyPath = '')
    {
        if ($expected instanceof BSONArray) {
            $this->assertMatchesArray($expected, $actual, $keyPath);

            return;
        }

        if ($expected instanceof BSONDocument) {
            $this->assertMatchesDocument($expected, $actual, $keyPath);

            return;
        }

        $this->assertEquals($expected, $actual, $keyPath);
    }

    private function assertMatchesArray(BSONArray $expected, $actual, $keyPath)
    {
        if (! $actual instanceof BSONArray) {
            $actualType = is_object($actual) ? get_class($actual) : gettype($actual);
            self::failAt(sprintf('%s is not instance of expected class "%s"', $actualType, BSONArray::class), $keyPath);
        }

        if (count($expected) !== count($actual)) {
            self::failAt(sprintf('$actual count is %d, expected %d', count($actual), count($expected)), $keyPath);
        }

        foreach ($expected as $key => $expectedValue) {
            $this->assertMatches(
                $expectedValue,
                $actual[$key],
                (empty($keyPath) ? $key : $keyPath . '.' . $key)
            );
        }
    }

    private function assertMatchesDocument(BSONDocument $expected, $actual, $keyPath)
    {
        if ($this->allowOperators && self::isOperator($expected)) {
            $this->assertMatchesOperator($expected, $actual, $keyPath);

            return;
        }

        if (! $actual instanceof BSONDocument) {
            $actualType = is_object($actual) ? get_class($actual) : gettype($actual);
            self::failAt(sprintf('%s is not instance of expected class "%s"', $actualType, BSONDocument::class), $keyPath);
        }

        foreach ($expected as $key => $expectedValue) {
            $actualKeyExists = $actual->offsetExists($key);

            if ($this->allowOperators && $expectedValue instanceof BSONDocument && self::isOperator($expectedValue)) {
                $operatorName = self::getOperatorName($expectedValue);

                if ($operatorName === '$$exists') {
                    Assert::assertIsBool($expectedValue['$$exists'], '$$exists requires bool');

                    if ($expectedValue['$$exists'] && ! $actualKeyExists) {
                        self::failAt(sprintf('$actual does not have expected key "%s"', $key), $keyPath);
                    }

                    if (! $expectedValue['$$exists'] && $actualKeyExists) {
                        self::failAt(sprintf('$actual has unexpected key "%s"', $key), $keyPath);
                    }

                    continue;
                }

                if ($operatorName === '$$unsetOrMatches') {
                    if (! $actualKeyExists) {
                        continue;
                    }

                    $expectedValue = $expectedValue['$$unsetOrMatches'];
                }
            }

            if (! $actualKeyExists) {
                self::failAt(sprintf('$actual does not have expected key "%s"', $key), $keyPath);
            }

            $this->assertMatches(
                $expectedValue,
                $actual[$key],
                (empty($keyPath) ? $key : $keyPath . '.' . $key)
            );
        }

        // Ignore extra keys in root documents
        if ($this->allowExtraKeys || ($this->allowExtraRootKeys && empty($keyPath))) {
            return;
        }

        foreach ($actual as $key => $_) {
            if (! $expected->offsetExists($key)) {
                self::failAt(sprintf('$actual has unexpected key "%s"', $key), $keyPath);
            }
        }
    }

    private function assertMatchesOperator(BSONDocument $operator, $actual, $keyPath)
    {
        $name = self::getOperatorName($operator);

        if ($name === '$$unsetOrMatches') {
            /* If the operator is used at the top level, consider null values
             * for $actual to be unset. If the operator is nested, this check is
             * done later during document iteration. */
            if ($keyPath === '' && $actual === null) {
                return;
            }

            $this->assertMatches(
                self::prepare($operator['$$unsetOrMatches']),
                $actual,
                $keyPath
            );

            return;
        }

        throw new LogicException('unsupported operator: ' . $name);
    }

    /** @see ConstraintTrait */
    private function doAdditionalFailureDescription($other)
    {
        if ($this->lastFailure === null) {
            return '';
        }

        return $this->lastFailure->getMessage();
    }

    /** @see ConstraintTrait */
    private function doFailureDescription($other)
    {
        return 'expected value matches actual value';
    }

    /** @see ConstraintTrait */
    private function doMatches($other)
    {
        $other = self::prepare($other);

        try {
            $this->assertMatches($this->value, $other);
        } catch (RuntimeException $e) {
            return false;
        }

        return true;
    }

    /** @see ConstraintTrait */
    private function doToString()
    {
        return 'matches ' . $this->exporter()->export($this->value);
    }

    private static function failAt(string $message, string $keyPath)
    {
        $prefix = empty($keyPath) ? '' : sprintf('Field path "%s": ', $keyPath);

        throw new RuntimeException($prefix . $message);
    }

    private static function getOperatorName(BSONDocument $document)
    {
        foreach ($document as $key => $_) {
            if (strpos((string) $key, '$$') === 0) {
                return $key;
            }
        }

        throw new LogicException('should not reach this point');
    }

    private static function isNumeric($value)
    {
        return is_int($value) || is_float($value) || $value instanceof Int64;
    }

    private static function isOperator(BSONDocument $document)
    {
        if (count($document) !== 1) {
            return false;
        }

        foreach ($document as $key => $_) {
            return strpos((string) $key, '$$') === 0;
        }

        throw new LogicException('should not reach this point');
    }

    /**
     * Prepare a value for comparison.
     *
     * If the value is an array or object, it will be converted to a BSONArray
     * or BSONDocument. If $value is an array and $isRoot is true, it will be
     * converted to a BSONDocument; otherwise, it will be converted to a
     * BSONArray or BSONDocument based on its keys. Each value within an array
     * or document will then be prepared recursively.
     *
     * @param mixed $bson
     * @return mixed
     */
    private static function prepare($bson)
    {
        if (! is_array($bson) && ! is_object($bson)) {
            return $bson;
        }

        /* Convert Int64 objects to integers on 64-bit platforms for
         * compatibility reasons. */
        if ($bson instanceof Int64 && PHP_INT_SIZE != 4) {
            return (int) ((string) $bson);
        }

        /* TODO: Convert Int64 objects to integers on 32-bit platforms if they
         * can be expressed as such. This is necessary to handle flexible
         * numeric comparisons if the server returns 32-bit value as a 64-bit
         * integer (e.g. cursor ID). */

        // Serializable can produce an array or object, so recurse on its output
        if ($bson instanceof Serializable) {
            return self::prepare($bson->bsonSerialize());
        }

        /* Serializable has already been handled, so any remaining instances of
         * Type will not serialize as BSON arrays or objects */
        if ($bson instanceof Type) {
            return $bson;
        }

        if (is_array($bson) && self::isArrayEmptyOrIndexed($bson)) {
            $bson = new BSONArray($bson);
        }

        if (! $bson instanceof BSONArray && ! $bson instanceof BSONDocument) {
            /* If $bson is an object, any numeric keys may become inaccessible.
             * We can work around this by casting back to an array. */
            $bson = new BSONDocument((array) $bson);
        }

        foreach ($bson as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $bson[$key] = self::prepare($value);
            }
        }

        return $bson;
    }

    private static function isArrayEmptyOrIndexed(array $a)
    {
        if (empty($a)) {
            return true;
        }

        return array_keys($a) === range(0, count($a) - 1);
    }
}
