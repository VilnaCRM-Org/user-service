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
}
