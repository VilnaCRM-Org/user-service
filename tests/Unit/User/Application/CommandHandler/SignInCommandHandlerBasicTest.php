<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\User\Application\Command\SignInCommand;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class SignInCommandHandlerBasicTest extends SignInCommandHandlerTestCase
{
    public function testInvokeReturnsTokensForUserWithoutTwoFactor(): void
    {
        [$user, $email, $pw, $ip, $ua] = $this->arrangeCredentials();

        $this->lockoutService->method('isLocked')->willReturn(false);
        $this->lockoutService->method('recordFailure')->willReturn(false);
        $this->lockoutService->method('clearFailures');

        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $this->passwordHasher->expects($this->once())
            ->method('verify')
            ->with($user->getPassword(), $pw)
            ->willReturn(true);
        $this->passwordHasher->method('needsRehash')->willReturn(false);

        $sessionUlid = \Symfony\Component\Uid\Ulid::fromString('01ARZ3NDEKTSV4RRFFQ69G5FA1');
        $this->ulidFactory->expects($this->once())
            ->method('create')
            ->willReturn($sessionUlid);

        $this->eventBus->expects($this->once())->method('publish');

        $command = new SignInCommand($email, $pw, false, $ip, $ua);
        $this->createHandler()->__invoke($command);
        $response = $command->getResponse();

        $this->assertFalse($response->isTwoFactorEnabled());
        $this->assertNotEmpty($response->getAccessToken());
        $this->assertNotEmpty($response->getRefreshToken());
        $this->assertNull($this->pendingTwoFactorRepository->saved());
    }

    public function testInvokeThrowsUnauthorizedWhenPasswordIsInvalid(): void
    {
        $email = strtolower($this->faker->email());
        $pw = $this->faker->password();
        $ip = $this->faker->ipv4();
        $ua = $this->faker->userAgent();
        $user = $this->createUser($email);

        $this->lockoutService->method('isLocked')->willReturn(false);
        $this->lockoutService->method('recordFailure')->willReturn(false);

        $this->userRepository->method('findByEmail')->willReturn($user);

        $this->passwordHasher->expects($this->once())
            ->method('verify')
            ->with($user->getPassword(), $pw)
            ->willReturn(false);

        $this->authTokenFactory->method('nextEventId')->willReturn('event-id');

        $command = new SignInCommand($email, $pw, false, $ip, $ua);

        try {
            $this->createHandler()->__invoke($command);
            $this->fail('Expected UnauthorizedHttpException.');
        } catch (UnauthorizedHttpException $exception) {
            $wwwAuth = $exception->getHeaders()['WWW-Authenticate'] ?? '';
            $this->assertStringContainsString('Bearer', (string) $wwwAuth);
            $this->assertSame('Invalid credentials.', $exception->getMessage());
        }
    }

    public function testInvokeCreatesRememberMeSessionWhenRequested(): void
    {
        [$user, $email, $pw, $ip, $ua] = $this->arrangeCredentials();

        $this->lockoutService->method('isLocked')->willReturn(false);
        $this->lockoutService->method('clearFailures');

        $this->userRepository->method('findByEmail')->willReturn($user);

        $this->passwordHasher->method('verify')
            ->with($user->getPassword(), $pw)
            ->willReturn(true);
        $this->passwordHasher->method('needsRehash')->willReturn(false);

        $sessionUlid = \Symfony\Component\Uid\Ulid::fromString('01ARZ3NDEKTSV4RRFFQ69G5FA2');
        $this->ulidFactory->method('create')->willReturn($sessionUlid);

        $this->eventBus->method('publish');

        $command = new SignInCommand($email, $pw, true, $ip, $ua);
        $this->createHandler()->__invoke($command);

        $this->assertNotEmpty($command->getResponse()->getAccessToken());
    }
}
