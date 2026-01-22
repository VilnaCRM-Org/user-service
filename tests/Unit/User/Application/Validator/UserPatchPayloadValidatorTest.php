<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Validator;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Validator\UserPatchPayloadValidator;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class UserPatchPayloadValidatorTest extends UnitTestCase
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

    public function testThrowsExceptionForNullEmail(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('email must not be null.');

        $this->validator->ensureNoExplicitNulls([
            'email' => null,
        ]);
    }

    public function testThrowsExceptionForNullInitials(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('initials must not be null.');

        $this->validator->ensureNoExplicitNulls([
            'initials' => null,
        ]);
    }

    public function testThrowsExceptionForNullNewPassword(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('newPassword must not be null.');

        $this->validator->ensureNoExplicitNulls([
            'newPassword' => null,
        ]);
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
        // When field is not present at all (not null, just missing)
        // should not throw exception
        $this->validator->ensureNoExplicitNulls([
            'initials' => 'AB',
            // email is missing - this is OK
            // newPassword is missing - this is OK
        ]);

        $this->assertTrue(true);
    }

    public function testDoesNotThrowForMissingEmailField(): void
    {
        $this->validator->ensureNoExplicitNulls([
            'newPassword' => 'password123',
            // email field is completely absent
        ]);

        $this->assertTrue(true);
    }

    public function testThrowsOnlyWhenFieldExistsAndIsNull(): void
    {
        // Test that BOTH conditions must be true: key exists AND value is null

        // Case 1: Key exists but value is NOT null - should not throw
        $this->validator->ensureNoExplicitNulls([
            'email' => 'test@example.com', // exists and not null
        ]);

        // Case 2: Key does NOT exist - should not throw
        $this->validator->ensureNoExplicitNulls([
            'otherField' => 'value', // email key doesn't exist
        ]);

        // Case 3: Both conditions true - should throw
        $this->expectException(BadRequestHttpException::class);
        $this->validator->ensureNoExplicitNulls([
            'email' => null, // exists AND is null
        ]);
    }

    public function testDoesNotThrowWhenEmailHasNonNullValue(): void
    {
        // Verify that having the key present with a non-null value doesn't throw
        $this->validator->ensureNoExplicitNulls([
            'email' => '',  // empty string is not null
            'initials' => 'AB',
            'newPassword' => 'test',
        ]);

        $this->assertTrue(true);
    }
}
