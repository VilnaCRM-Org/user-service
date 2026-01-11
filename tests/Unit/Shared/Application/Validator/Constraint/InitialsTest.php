<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator\Constraint;

use App\Shared\Application\Validator\Constraint\Initials;
use App\Tests\Unit\UnitTestCase;

final class InitialsTest extends UnitTestCase
{
    public function testConstraintInitialization(): void
    {
        $constraint = new Initials();

        $this->assertInstanceOf(Initials::class, $constraint);
        $this->assertIsArray($constraint->groups);
        $this->assertNull($constraint->payload);
    }
}
