<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Validator;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Validator\CompositePasswordResetTokenValidator;
use App\User\Domain\Contract\PasswordResetTokenValidatorInterface;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Exception\PasswordResetTokenNotFoundException;

final class CompositePasswordResetTokenValidatorTest extends UnitTestCase
{
    public function testValidateCallsAllValidators(): void
    {
        $token = $this->createMock(PasswordResetTokenInterface::class);

        $validator1 = $this->createMock(PasswordResetTokenValidatorInterface::class);
        $validator1->expects($this->once())
            ->method('validate')
            ->with($token);

        $validator2 = $this->createMock(PasswordResetTokenValidatorInterface::class);
        $validator2->expects($this->once())
            ->method('validate')
            ->with($token);

        $validator3 = $this->createMock(PasswordResetTokenValidatorInterface::class);
        $validator3->expects($this->once())
            ->method('validate')
            ->with($token);

        $composite = new CompositePasswordResetTokenValidator([
            $validator1,
            $validator2,
            $validator3,
        ]);

        $composite->validate($token);
    }

    public function testValidateWithEmptyValidators(): void
    {
        $token = $this->createMock(PasswordResetTokenInterface::class);
        $composite = new CompositePasswordResetTokenValidator([]);

        $this->expectNotToPerformAssertions();
        $composite->validate($token);
    }

    public function testValidateThrowsExceptionWhenValidatorThrows(): void
    {
        $token = $this->createMock(PasswordResetTokenInterface::class);

        $validator1 = $this->createMock(PasswordResetTokenValidatorInterface::class);
        $validator1->expects($this->once())
            ->method('validate')
            ->with($token);

        $validator2 = $this->createMock(PasswordResetTokenValidatorInterface::class);
        $validator2->expects($this->once())
            ->method('validate')
            ->with($token)
            ->willThrowException(new PasswordResetTokenNotFoundException());

        $validator3 = $this->createMock(PasswordResetTokenValidatorInterface::class);
        $validator3->expects($this->never())
            ->method('validate');

        $composite = new CompositePasswordResetTokenValidator([
            $validator1,
            $validator2,
            $validator3,
        ]);

        $this->expectException(PasswordResetTokenNotFoundException::class);
        $composite->validate($token);
    }
}
