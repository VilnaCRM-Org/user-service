<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator;

use App\Shared\Application\Validator\CreateUserBatch;
use App\Tests\Unit\UnitTestCase;

final class CreateUserBatchTest extends UnitTestCase
{
    public function testConstraintInitialization(): void
    {
        $groups = [$this->faker->word(), $this->faker->word()];
        $payload = [$this->faker->word() => $this->faker->word()];

        $constraint = new CreateUserBatch($groups, $payload);

        $this->assertEquals($groups, $constraint->groups);
        $this->assertEquals($payload, $constraint->payload);
    }
}
