<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\User\Application\Command\SignInCommand;
use Symfony\Component\Uid\Ulid;

final class SignInCommandHandlerTwoFactorTest extends SignInCommandHandlerTestCase
{
    public function testInvokeReturnsTwoFactorResponseWhenTwoFactorIsEnabled(): void
    {
        [$user, $email, $pw, $ip, $ua] = $this->arrangeCredentials();
        $user->setTwoFactorEnabled(true);

        $this->credentialValidator->method('validate')
            ->with($email, $pw, $ip, $ua)
            ->willReturn($user);

        $pendingSid = (string) Ulid::fromString('01ARZ3NDEKTSV4RRFFQ69G5FB2');
        $this->idFactory->expects($this->once())->method('create')->willReturn($pendingSid);

        $this->sessionIssuer->expects($this->never())->method('create');
        $this->signInPublisher->expects($this->never())->method('publishSignedIn');

        $command = new SignInCommand($email, $pw, false, $ip, $ua);
        $this->createHandler()->__invoke($command);

        $this->assertPendingTwoFactor($pendingSid, $user->getId(), 300, false);
        $this->assertTrue($command->getResponse()->isTwoFactorEnabled());
        $this->assertSame($pendingSid, $command->getResponse()->getPendingSessionId());
        $this->assertNull($command->getResponse()->getAccessToken());
        $this->assertNull($command->getResponse()->getRefreshToken());
    }

    public function testInvokeStoresRememberMeInPendingTwoFactorWhenTwoFactorIsEnabled(): void
    {
        [$user, $email, $pw, $ip, $ua] = $this->arrangeCredentials();
        $user->setTwoFactorEnabled(true);

        $this->credentialValidator->method('validate')
            ->with($email, $pw, $ip, $ua)
            ->willReturn($user);

        $pendingSid = (string) Ulid::fromString('01ARZ3NDEKTSV4RRFFQ69G5FB3');
        $this->idFactory->method('create')->willReturn($pendingSid);

        $command = new SignInCommand($email, $pw, true, $ip, $ua);
        $this->createHandler()->__invoke($command);

        $saved = $this->pendingTwoFactorRepository->saved();
        $this->assertNotNull($saved);
        $this->assertTrue($saved->isRememberMe());
        $this->assertTrue($command->getResponse()->isTwoFactorEnabled());
    }

    public function testDefaultTtlIsThreeHundredSeconds(): void
    {
        [$user, $email, $pw, $ip, $ua] = $this->arrangeCredentials();
        $user->setTwoFactorEnabled(true);

        $this->credentialValidator->method('validate')
            ->with($email, $pw, $ip, $ua)
            ->willReturn($user);

        $pendingSid = (string) Ulid::fromString('01ARZ3NDEKTSV4RRFFQ69G5FB5');
        $this->idFactory->method('create')->willReturn($pendingSid);

        $handler = $this->createHandler();

        $command = new SignInCommand($email, $pw, false, $ip, $ua);
        $handler->__invoke($command);

        $saved = $this->pendingTwoFactorRepository->saved();
        $this->assertNotNull($saved);
        $ttl = $saved->getExpiresAt()->getTimestamp() - $saved->getCreatedAt()->getTimestamp();
        $this->assertSame(300, $ttl);
    }

    private function assertPendingTwoFactor(
        string $id,
        string $userId,
        int $ttl,
        bool $rememberMe
    ): void {
        $saved = $this->pendingTwoFactorRepository->saved();
        $this->assertNotNull($saved);
        $this->assertSame($id, $saved->getId());
        $this->assertSame($userId, $saved->getUserId());
        $expiresAt = $saved->getExpiresAt()->getTimestamp();
        $this->assertSame(
            $ttl,
            $expiresAt - $saved->getCreatedAt()->getTimestamp()
        );
        $this->assertSame($rememberMe, $saved->isRememberMe());
    }
}
