<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\User\Application\Command\SignInCommand;

final class SignInCommandHandlerBasicTest extends SignInCommandHandlerTestCase
{
    public function testInvokeReturnsTokensForUserWithoutTwoFactor(): void
    {
        [$user, $email, $pw, $ip, $ua] = $this->arrangeCredentials();
        $this->userAuthenticator->method('authenticate')
            ->with($email, $pw, $ip, $ua)
            ->willReturn($user);
        $this->signInEvents->expects($this->once())->method('publishSignedIn');

        $command = new SignInCommand($email, $pw, false, $ip, $ua);
        $this->createHandler()->__invoke($command);
        $response = $command->getResponse();

        $this->assertFalse($response->isTwoFactorEnabled());
        $this->assertNotEmpty($response->getAccessToken());
        $this->assertNotEmpty($response->getRefreshToken());
        $this->assertNull($this->pendingTwoFactorRepository->saved());
    }

    public function testInvokeCreatesRememberMeSessionWhenRequested(): void
    {
        [$user, $email, $pw, $ip, $ua] = $this->arrangeCredentials();
        $this->userAuthenticator->method('authenticate')
            ->with($email, $pw, $ip, $ua)
            ->willReturn($user);

        $command = new SignInCommand($email, $pw, true, $ip, $ua);
        $this->createHandler()->__invoke($command);

        $this->assertNotEmpty($command->getResponse()->getAccessToken());
    }
}
