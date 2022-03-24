<?php

namespace Alcaeus\MongoDbAdapter\Tests\Constraint;

use PHPUnit\Framework\Constraint\Constraint as BaseConstraint;
use function class_exists;

if (class_exists('PHPUnit_Framework_Constraint')) {
    abstract class Constraint extends \PHPUnit_Framework_Constraint
    {
    }
} else {
    abstract class Constraint extends BaseConstraint
    {
    }
}
