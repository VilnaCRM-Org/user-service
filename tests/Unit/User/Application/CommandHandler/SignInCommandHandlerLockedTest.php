<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\User\Application\Command\SignInCommand;
use Symfony\Component\HttpKernel\Exception\LockedHttpException;

final class SignInCommandHandlerLockedTest extends SignInCommandHandlerTestCase
{
    public function testInvokeThrowsLockedWhenAccountAlreadyLocked(): void
    {
        $this->lockoutService->expects($this->once())
            ->method('isLocked')
            ->willReturn(true);

        $this->authTokenFactory->method('nextEventId')->willReturn('event-id');

        $this->userRepository->expects($this->never())->method('findByEmail');

        $command = $this->createRandomSignInCommand();

        try {
            $this->createHandler()->__invoke($command);
            $this->fail('Expected LockedHttpException.');
        } catch (LockedHttpException $exception) {
            $this->assertSame('Account temporarily locked', $exception->getMessage());
            $this->assertSame('900', $exception->getHeaders()['Retry-After'] ?? null);
        }
    }

    public function testInvokeThrowsLockedWhenFailureThresholdReached(): void
    {
        $email = strtolower($this->faker->email());
        $pw = $this->faker->password();
        $ip = $this->faker->ipv4();
        $ua = $this->faker->userAgent();
        $user = $this->createUser($email);

        $this->lockoutService->method('isLocked')->willReturn(false);
        $this->lockoutService->expects($this->once())
            ->method('recordFailure')
            ->willReturn(true);

        $this->userRepository->method('findByEmail')->willReturn($user);

        $this->passwordHasher->method('verify')
            ->with($user->getPassword(), $pw)
            ->willReturn(false);

        $this->authTokenFactory->method('nextEventId')->willReturn('event-id');

        $command = new SignInCommand($email, $pw, false, $ip, $ua);

        try {
            $this->createHandler()->__invoke($command);
            $this->fail('Expected LockedHttpException.');
        } catch (LockedHttpException $exception) {
            $this->assertSame('Account temporarily locked', $exception->getMessage());
            $this->assertSame(0, $exception->getCode());
            $this->assertSame('900', $exception->getHeaders()['Retry-After'] ?? null);
        }
    }
}
