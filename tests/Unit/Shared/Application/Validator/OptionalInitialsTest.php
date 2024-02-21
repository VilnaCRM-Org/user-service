<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator;

use App\Shared\Application\Validator\OptionalInitials;
use App\Tests\Unit\UnitTestCase;

class OptionalInitialsTest extends UnitTestCase
{
    public function testConstraintInitialization(): void
    {
        $groups = [$this->faker->word(), $this->faker->word()];
        $payload = [$this->faker->word() => $this->faker->word()];

        $constraint = new OptionalInitials($groups, $payload);

        $this->assertEquals($groups, $constraint->groups);
        $this->assertEquals($payload, $constraint->payload);
    }

}
