<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use Symfony\Component\HttpKernel\Exception\LockedHttpException;

final class SignInCommandHandlerLockedTest extends SignInCommandHandlerTestCase
{
    public function testInvokeThrowsLockedWhenAuthenticatorThrowsLocked(): void
    {
        $this->userAuthenticator->method('authenticate')
            ->willThrowException(new LockedHttpException(
                'Account temporarily locked',
                null,
                0,
                ['Retry-After' => '900']
            ));

        $this->expectException(LockedHttpException::class);
        $this->expectExceptionMessage('Account temporarily locked');

        $this->createHandler()->__invoke($this->createRandomSignInCommand());
    }
}
