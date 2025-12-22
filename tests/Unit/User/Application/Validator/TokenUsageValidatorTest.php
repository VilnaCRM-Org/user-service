<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Validator;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Validator\TokenUsageValidator;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Exception\PasswordResetTokenAlreadyUsedException;

final class TokenUsageValidatorTest extends UnitTestCase
{
    private TokenUsageValidator $validator;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new TokenUsageValidator();
    }

    public function testValidateWithValidToken(): void
    {
        $token = $this->createMock(PasswordResetTokenInterface::class);
        $token->expects($this->once())
            ->method('isUsed')
            ->willReturn(false);

        // Should not throw any exception
        $this->validator->validate($token);
        $this->addToAssertionCount(1);
    }

    public function testValidateWithUsedTokenThrowsException(): void
    {
        $token = $this->createMock(PasswordResetTokenInterface::class);
        $token->expects($this->once())
            ->method('isUsed')
            ->willReturn(true);

        $this->expectException(PasswordResetTokenAlreadyUsedException::class);
        $this->validator->validate($token);
    }

    public function testValidateWithNullToken(): void
    {
        $this->expectNotToPerformAssertions();
        $this->validator->validate(null);
    }
}
