<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Adapter;

use App\Shared\Infrastructure\Adapter\RedisIamAuthenticator;
use App\Shared\Infrastructure\Adapter\RedisIamAuthToken;
use App\Shared\Infrastructure\Factory\RedisIamAuthTokenFactoryInterface;
use App\Shared\Infrastructure\Observability\Factory\AuthFailureMetricFactory;
use App\Tests\Unit\Shared\Infrastructure\Observability\BusinessMetricsEmitterSpy;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

final class RedisIamAuthenticatorTest extends UnitTestCase
{
    private RedisIamAuthTokenFactoryInterface&MockObject $tokenFactory;
    private BusinessMetricsEmitterSpy $metricsEmitter;
    private LoggerInterface&MockObject $logger;
    private string $userId;
    private string $token;
    private int $validUntil;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->tokenFactory = $this->createMock(RedisIamAuthTokenFactoryInterface::class);
        $this->metricsEmitter = new BusinessMetricsEmitterSpy();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->userId = $this->faker->userName();
        $this->token = $this->faker->sha256();
        $this->validUntil = $this->faker->numberBetween(1_700_000_000, 1_900_000_000);
    }

    public function testSendsAuthWithUserIdAndFreshTokenAndReturnsTokenValidity(): void
    {
        $this->tokenFactory->expects(self::once())->method('create')
            ->willReturn($this->authToken());
        $client = $this->createMock(\Redis::class);
        $client->expects(self::once())->method('auth')
            ->with([$this->userId, $this->token])
            ->willReturn(true);
        $client->expects(self::never())->method('close');
        $this->logger->expects(self::never())->method('error');

        $validUntil = $this->authenticator()->authenticate($client);

        self::assertSame($this->validUntil, $validUntil);
        self::assertSame(0, $this->metricsEmitter->count());
    }

    #[DataProvider('authenticationRejectionProvider')]
    public function testRejectedAuthClosesConnectionCountsFailureAndThrows(string $reply): void
    {
        $exception = new \RedisException($reply);
        $this->tokenFactory->method('create')->willReturn($this->authToken());
        $client = $this->createMock(\Redis::class);
        $client->method('auth')->willThrowException($exception);
        $client->expects(self::once())->method('close');
        $this->logger->expects(self::never())->method('warning');
        $this->expectFailureLog($exception, $exception->getMessage());

        $this->assertAuthenticationFails($client, $exception);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function authenticationRejectionProvider(): iterable
    {
        yield 'WRONGPASS' => ['WRONGPASS invalid username-password pair or user is disabled.'];
        yield 'NOAUTH' => ['NOAUTH Authentication required.'];
        yield 'NOPERM' => ['NOPERM User user-service has no permissions to run the auth command'];
        yield 'AUTH without password' => [
            'ERR AUTH <password> called without any password configured for the default user.',
        ];
        yield 'phpredis replay rejected' => ['AUTH failed while reconnecting'];
        yield 'invalid pair text' => ['invalid username-password pair'];
    }

    #[DataProvider('transportErrorProvider')]
    public function testTransportErrorDuringAuthIsLoggedButNotCounted(string $message): void
    {
        $exception = new \RedisException($message);
        $this->tokenFactory->method('create')->willReturn($this->authToken());
        $client = $this->createMock(\Redis::class);
        $client->method('auth')->willThrowException($exception);
        $client->expects(self::once())->method('close');
        $this->logger->expects(self::never())->method('error');
        $this->logger->expects(self::once())->method('warning')->with(
            'Redis IAM connection error during authentication.',
            [
                'backend' => 'redis',
                'user_id' => $this->userId,
                'exception_class' => \RedisException::class,
                'error' => $message,
            ]
        );

        try {
            $this->authenticator()->authenticate($client);
            self::fail('Connection error was not rethrown.');
        } catch (\RedisException $actual) {
            self::assertSame($exception, $actual);
        }

        self::assertSame(0, $this->metricsEmitter->count());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function transportErrorProvider(): iterable
    {
        yield 'read error' => ['read error on connection to valkey-iam:6379'];
        yield 'went away' => ['Redis server tls://valkey-iam:6379 went away'];
        yield 'connection closed' => ['Connection closed'];
        yield 'loading' => ['LOADING Redis is loading the dataset in memory'];
        yield 'generic server error' => ['ERR max number of clients reached'];
    }

    public function testFailingCloseDoesNotMaskTheOriginalConnectionError(): void
    {
        $exception = new \RedisException('read error on connection to valkey-iam:6379');
        $this->tokenFactory->method('create')->willReturn($this->authToken());
        $client = $this->createMock(\Redis::class);
        $client->method('auth')->willThrowException($exception);
        $client->expects(self::once())->method('close')
            ->willThrowException(new \RedisException('close failed'));
        $this->logger->expects(self::once())->method('warning')
            ->with('Redis IAM connection error during authentication.');

        $this->expectExceptionObject($exception);

        try {
            $this->authenticator()->authenticate($client);
        } finally {
            self::assertSame(0, $this->metricsEmitter->count());
        }
    }

    public function testNonRedisErrorDuringAuthIsLoggedWithoutMessageAndNotCounted(): void
    {
        $exception = new \RuntimeException($this->faker->sentence());
        $this->tokenFactory->method('create')->willReturn($this->authToken());
        $client = $this->createMock(\Redis::class);
        $client->method('auth')->willThrowException($exception);
        $client->expects(self::once())->method('close');
        $this->logger->expects(self::once())->method('warning')->with(
            'Redis IAM connection error during authentication.',
            [
                'backend' => 'redis',
                'user_id' => $this->userId,
                'exception_class' => \RuntimeException::class,
            ]
        );

        $this->expectExceptionObject($exception);

        try {
            $this->authenticator()->authenticate($client);
        } finally {
            self::assertSame(0, $this->metricsEmitter->count());
        }
    }

    public function testFalseAuthReplyIsTreatedAsFailure(): void
    {
        $this->tokenFactory->method('create')->willReturn($this->authToken());
        $client = $this->createMock(\Redis::class);
        $client->method('auth')->willReturn(false);
        $client->expects(self::once())->method('close');

        $this->expectException(\RedisException::class);
        $this->expectExceptionMessage('Redis rejected the IAM authentication request.');

        try {
            $this->authenticator()->authenticate($client);
        } finally {
            self::assertSame(1, $this->metricsEmitter->count());
        }
    }

    public function testQueuedAuthReplyIsTreatedAsFailure(): void
    {
        $this->tokenFactory->method('create')->willReturn($this->authToken());
        $client = $this->createMock(\Redis::class);
        $client->method('auth')->willReturn($client);

        $this->expectException(\RedisException::class);

        $this->authenticator()->authenticate($client);
    }

    public function testTokenFailureLogsOnlyTheExceptionClassNotItsMessage(): void
    {
        $exception = new \RuntimeException(sprintf(
            'Error retrieving credentials from http://169.254.170.2/v2/credentials/%s',
            $this->faker->uuid()
        ));
        $this->tokenFactory->method('create')->willThrowException($exception);
        $client = $this->createMock(\Redis::class);
        $client->expects(self::never())->method('auth');
        $client->expects(self::once())->method('close');
        $this->expectFailureLog($exception);

        $this->assertAuthenticationFails($client, $exception);
    }

    private function assertAuthenticationFails(\Redis $client, \Throwable $expected): void
    {
        try {
            $this->authenticator()->authenticate($client);
            self::fail('Authentication failure was not rethrown.');
        } catch (\Throwable $actual) {
            self::assertSame($expected, $actual);
        }

        self::assertSame(1, $this->metricsEmitter->count());
        $metric = $this->metricsEmitter->emitted()->all()[0];
        self::assertSame('auth_failure', $metric->name());
        self::assertSame(
            ['backend' => 'redis'],
            $metric->dimensions()->values()->toAssociativeArray()
        );
    }

    private function expectFailureLog(\Throwable $exception, ?string $error = null): void
    {
        $context = [
            'backend' => 'redis',
            'user_id' => $this->userId,
            'exception_class' => $exception::class,
        ];
        if ($error !== null) {
            $context['error'] = $error;
        }

        $this->logger->expects(self::once())->method('error')->with(
            'Redis IAM authentication failed.',
            $context
        );
    }

    private function authToken(): RedisIamAuthToken
    {
        return new RedisIamAuthToken($this->token, $this->validUntil);
    }

    private function authenticator(): RedisIamAuthenticator
    {
        return new RedisIamAuthenticator(
            $this->tokenFactory,
            $this->metricsEmitter,
            new AuthFailureMetricFactory(),
            $this->logger,
            $this->userId
        );
    }
}
