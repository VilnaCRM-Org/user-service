<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Validator;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Validator\UserPatchPayloadValidator;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class UserPatchPayloadValidatorInvalidTest extends UnitTestCase
{
    private UserPatchPayloadValidator $validator;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new UserPatchPayloadValidator();
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

    public function testThrowsOnlyWhenFieldExistsAndIsNull(): void
    {
        $this->expectException(BadRequestHttpException::class);

        $this->validator->ensureNoExplicitNulls([
            'email' => null,
        ]);
    }
}
