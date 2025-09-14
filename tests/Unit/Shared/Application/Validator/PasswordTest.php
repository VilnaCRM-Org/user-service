<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator;

use App\Shared\Application\Validator\Password;
use App\Tests\Unit\UnitTestCase;

final class PasswordTest extends UnitTestCase
{
    public function testConstraintInitialization(): void
    {
        $constraint = new Password();

        $this->assertInstanceOf(Password::class, $constraint);
        $this->assertIsArray($constraint->groups);
        $this->assertNull($constraint->payload);
    }
}
