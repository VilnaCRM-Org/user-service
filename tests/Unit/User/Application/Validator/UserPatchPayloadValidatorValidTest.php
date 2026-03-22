<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Validator;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Validator\UserPatchPayloadValidator;

final class UserPatchPayloadValidatorValidTest extends UnitTestCase
{
    private UserPatchPayloadValidator $validator;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new UserPatchPayloadValidator();
    }

    public function testNoExceptionForNullPayload(): void
    {
        $this->validator->ensureNoExplicitNulls(null);

        $this->assertTrue(true);
    }

    public function testNoExceptionForEmptyPayload(): void
    {
        $this->validator->ensureNoExplicitNulls([]);

        $this->assertTrue(true);
    }

    public function testNoExceptionForValidPayload(): void
    {
        $this->validator->ensureNoExplicitNulls([
            'email' => $this->faker->email(),
            'initials' => $this->faker->word(),
            'newPassword' => $this->faker->password(),
        ]);

        $this->assertTrue(true);
    }

    public function testIgnoresOtherNullFields(): void
    {
        $this->validator->ensureNoExplicitNulls([
            'otherField' => null,
        ]);

        $this->assertTrue(true);
    }

    public function testDoesNotThrowWhenFieldMissing(): void
    {
        $this->validator->ensureNoExplicitNulls([
            'initials' => 'AB',
        ]);

        $this->assertTrue(true);
    }

    public function testDoesNotThrowForMissingEmailField(): void
    {
        $this->validator->ensureNoExplicitNulls([
            'newPassword' => 'password123',
        ]);

        $this->assertTrue(true);
    }

    public function testDoesNotThrowWhenEmailHasNonNullValue(): void
    {
        $this->validator->ensureNoExplicitNulls([
            'email' => '',
            'initials' => 'AB',
            'newPassword' => 'test',
        ]);

        $this->assertTrue(true);
    }

    public function testDoesNotThrowWhenEmailKeyMissing(): void
    {
        $this->validator->ensureNoExplicitNulls([
            'otherField' => 'value',
        ]);

        $this->assertTrue(true);
    }
}
