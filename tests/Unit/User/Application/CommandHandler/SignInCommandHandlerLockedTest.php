<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\User\Application\Command\SignInCommand;
use Symfony\Component\HttpKernel\Exception\LockedHttpException;

final class SignInCommandHandlerLockedTest extends SignInCommandHandlerTestCase
{
    private const LOCKED_MESSAGE = 'Account temporarily locked';
    private const RETRY_AFTER_SECONDS = '900';

    public function testInvokeThrowsLockedWhenAccountAlreadyLocked(): void
    {
        $command = $this->createRandomSignInCommand();
        $this->expectValidateThrowsLocked();
        $this->assertLockedException($command);
    }

    public function testInvokeThrowsLockedWhenFailureThresholdReached(): void
    {
        $email = strtolower($this->faker->email());
        $pw = $this->faker->password();
        $ip = $this->faker->ipv4();
        $ua = $this->faker->userAgent();

        $this->expectValidateThrowsLocked($email, $pw, $ip, $ua);
        $command = new SignInCommand($email, $pw, false, $ip, $ua);
        $exception = $this->assertLockedException($command);
        $this->assertSame(0, $exception->getCode());
    }

    private function expectValidateThrowsLocked(
        ?string $email = null,
        ?string $password = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): void {
        $invocation = $this->credentialValidator->expects($this->once())
            ->method('validate');

        if (
            is_string($email)
            && is_string($password)
            && is_string($ipAddress)
            && is_string($userAgent)
        ) {
            $invocation->with($email, $password, $ipAddress, $userAgent);
        }

        $invocation->willThrowException(
            new LockedHttpException(
                self::LOCKED_MESSAGE,
                null,
                0,
                ['Retry-After' => self::RETRY_AFTER_SECONDS]
            )
        );
    }

    private function assertLockedException(SignInCommand $command): LockedHttpException
    {
        try {
            $this->createHandler()->__invoke($command);
            $this->fail('Expected LockedHttpException.');
        } catch (LockedHttpException $exception) {
            $this->assertSame(self::LOCKED_MESSAGE, $exception->getMessage());
            $this->assertSame(
                self::RETRY_AFTER_SECONDS,
                $exception->getHeaders()['Retry-After'] ?? null
            );

            return $exception;
        }
    }
}
