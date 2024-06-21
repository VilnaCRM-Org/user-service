<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator;

use App\Shared\Application\Validator\Password;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\Constraint;

final class PasswordTest extends UnitTestCase
{
    public function testConstructor(): void
    {
        $groups = [$this->faker->word(), $this->faker->word()];
        $payload = [$this->faker->word() => $this->faker->word()];

        $constraint = new Password($groups, $payload);

        self::assertInstanceOf(Constraint::class, $constraint);
        self::assertSame($groups, $constraint->groups);
        self::assertSame($payload, $constraint->payload);
    }
}
