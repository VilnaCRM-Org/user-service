<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\User\Application\Command\SignInCommand;
use App\User\Application\CommandHandler\SignInCommandHandler;
use Symfony\Component\Uid\Ulid;

final class SignInCommandHandlerTwoFactorTest extends SignInCommandHandlerTestCase
{
    public function testInvokeReturnsTwoFactorResponseWhenTwoFactorIsEnabled(): void
    {
        [$user, $email, $pw, $ip, $ua] = $this->arrangeCredentials();
        $user->setTwoFactorEnabled(true);

        $this->lockoutService->method('isLocked')->willReturn(false);
        $this->lockoutService->method('clearFailures');

        $this->userRepository->method('findByEmail')->willReturn($user);

        $this->passwordHasher->method('verify')
            ->with($user->getPassword(), $pw)
            ->willReturn(true);
        $this->passwordHasher->method('needsRehash')->willReturn(false);

        $pendingSid = Ulid::fromString('01ARZ3NDEKTSV4RRFFQ69G5FB2');
        $this->ulidFactory->expects($this->once())->method('create')->willReturn($pendingSid);

        $this->authSessionRepository->expects($this->never())->method('save');
        $this->eventBus->expects($this->never())->method('publish');

        $command = new SignInCommand($email, $pw, false, $ip, $ua);
        $this->createHandler()->__invoke($command);

        $sid = (string) $pendingSid;
        $this->assertPendingTwoFactor($sid, $user->getId(), 300, false);
        $this->assertTrue($command->getResponse()->isTwoFactorEnabled());
        $this->assertSame($sid, $command->getResponse()->getPendingSessionId());
        $this->assertNull($command->getResponse()->getAccessToken());
        $this->assertNull($command->getResponse()->getRefreshToken());
    }

    public function testInvokeStoresRememberMeInPendingTwoFactorWhenTwoFactorIsEnabled(): void
    {
        [$user, $email, $pw, $ip, $ua] = $this->arrangeCredentials();
        $user->setTwoFactorEnabled(true);

        $this->lockoutService->method('isLocked')->willReturn(false);
        $this->lockoutService->method('clearFailures');

        $this->userRepository->method('findByEmail')->willReturn($user);

        $this->passwordHasher->method('verify')
            ->with($user->getPassword(), $pw)
            ->willReturn(true);
        $this->passwordHasher->method('needsRehash')->willReturn(false);

        $pendingSid = Ulid::fromString('01ARZ3NDEKTSV4RRFFQ69G5FB3');
        $this->ulidFactory->method('create')->willReturn($pendingSid);

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

        $this->lockoutService->method('isLocked')->willReturn(false);
        $this->lockoutService->method('clearFailures');

        $this->userRepository->method('findByEmail')->willReturn($user);

        $this->passwordHasher->method('verify')
            ->with($user->getPassword(), $pw)
            ->willReturn(true);
        $this->passwordHasher->method('needsRehash')->willReturn(false);

        $pendingSid = Ulid::fromString('01ARZ3NDEKTSV4RRFFQ69G5FB5');
        $this->ulidFactory->method('create')->willReturn($pendingSid);

        $handler = new SignInCommandHandler(
            $this->userRepository,
            $this->passwordHasher,
            $this->lockoutService,
            $this->authSessionRepository,
            $this->authRefreshTokenRepository,
            $this->accessTokenGenerator,
            $this->authTokenFactory,
            $this->eventBus,
            $this->pendingTwoFactorRepository,
            $this->ulidFactory,
            '$2y$04$test.dummy.hash.that.is.valid.bcrypt.placeholder',
        );

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
