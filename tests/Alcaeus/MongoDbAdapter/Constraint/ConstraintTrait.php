<?php

namespace Alcaeus\MongoDbAdapter\Tests\Constraint;

use Symfony\Bridge\PhpUnit\ConstraintTrait as BaseConstraintTrait;
use Symfony\Bridge\PhpUnit\Legacy\ConstraintTraitForV6;

if (class_exists('PHPUnit_Framework_Constraint')) {
    trait ConstraintTrait
    {
        use ConstraintTraitForV6;
    }
} else {
    trait ConstraintTrait
    {
        use BaseConstraintTrait;
    }
}
