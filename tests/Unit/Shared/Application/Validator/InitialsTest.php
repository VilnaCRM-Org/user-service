<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator;

use App\Shared\Application\Validator\Initials;
use App\Tests\Unit\UnitTestCase;

final class InitialsTest extends UnitTestCase
{
    public function testConstraintInitialization(): void
    {
        $groups = [$this->faker->word(), $this->faker->word()];
        $payload = [$this->faker->word() => $this->faker->word()];

        $constraint = new Initials($groups, $payload);

        $this->assertEquals($groups, $constraint->groups);
        $this->assertEquals($payload, $constraint->payload);
    }
}
