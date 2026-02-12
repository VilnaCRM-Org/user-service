<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\SignInCommand;
use App\User\Application\Command\SignInCommandResponse;
use App\User\Application\DTO\SignInDto;
use App\User\Application\Processor\SignInProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class SignInProcessorTest extends UnitTestCase
{
    private CommandBusInterface&MockObject $commandBus;
    private RequestStack $requestStack;
    private Operation $operation;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->requestStack = new RequestStack();
        $this->operation = $this->createMock(Operation::class);
    }

    public function testProcessReturnsTokensAndSetsStandardCookie(): void
    {
        $email = $this->faker->email();
        $password = $this->faker->password();
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();

        $request = $this->createRequest($ipAddress, $userAgent);
        $this->requestStack->push($request);

        $dto = new SignInDto($email, $password, false);

        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(/**
             * @return true
             */
            function (SignInCommand $command) use ($email, $password, $ipAddress, $userAgent): bool {
                $this->assertSame($email, $command->email);
                $this->assertSame($password, $command->password);
                $this->assertFalse($command->rememberMe);
                $this->assertSame($ipAddress, $command->ipAddress);
                $this->assertSame($userAgent, $command->userAgent);

                $command->setResponse(
                    new SignInCommandResponse(
                        false,
                        'access-token-value',
                        'refresh-token-value'
                    )
                );

                return true;
            }));

        $processor = new SignInProcessor($this->commandBus, $this->requestStack);
        $response = $processor->process($dto, $this->operation, [], ['request' => $request]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode([
                '2fa_enabled' => false,
                'access_token' => 'access-token-value',
                'refresh_token' => 'refresh-token-value',
            ]),
            (string) $response->getContent()
        );

        $cookies = $response->headers->getCookies();
        $this->assertCount(1, $cookies);
        $cookie = $cookies[0];

        $this->assertSame('__Host-auth_token', $cookie->getName());
        $this->assertSame('access-token-value', $cookie->getValue());
        $this->assertSame('/', $cookie->getPath());
        $this->assertNull($cookie->getDomain());
        $this->assertTrue($cookie->isSecure());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame(Cookie::SAMESITE_LAX, $cookie->getSameSite());
        $this->assertGreaterThanOrEqual(899, $cookie->getMaxAge());
        $this->assertLessThanOrEqual(900, $cookie->getMaxAge());
    }

    public function testProcessSetsRememberMeCookieMaxAge(): void
    {
        $request = $this->createRequest($this->faker->ipv4(), $this->faker->userAgent());
        $dto = new SignInDto($this->faker->email(), $this->faker->password(), true);

        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(/**
             * @return true
             */
            function (SignInCommand $command): bool {
                $this->assertTrue($command->rememberMe);

                $command->setResponse(
                    new SignInCommandResponse(
                        false,
                        'remember-token',
                        'refresh-token'
                    )
                );

                return true;
            }));

        $processor = new SignInProcessor($this->commandBus, $this->requestStack);
        $response = $processor->process($dto, $this->operation, [], ['request' => $request]);

        $cookies = $response->headers->getCookies();
        $this->assertCount(1, $cookies);
        $this->assertGreaterThanOrEqual(2591999, $cookies[0]->getMaxAge());
        $this->assertLessThanOrEqual(2592000, $cookies[0]->getMaxAge());
    }

    public function testProcessReturnsTwoFactorBodyWithoutCookie(): void
    {
        $request = $this->createRequest($this->faker->ipv4(), $this->faker->userAgent());
        $dto = new SignInDto($this->faker->email(), $this->faker->password(), false);

        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(/**
             * @return true
             */
            static function (SignInCommand $command): bool {
                $command->setResponse(
                    new SignInCommandResponse(
                        true,
                        null,
                        null,
                        'pending-session-123'
                    )
                );

                return true;
            }));

        $processor = new SignInProcessor($this->commandBus, $this->requestStack);
        $response = $processor->process($dto, $this->operation, [], ['request' => $request]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode([
                '2fa_enabled' => true,
                'pending_session_id' => 'pending-session-123',
            ]),
            (string) $response->getContent()
        );
        $this->assertCount(0, $response->headers->getCookies());
    }

    public function testProcessDoesNotSetCookieForTwoFactorResponseEvenWithTokenValue(): void
    {
        $request = $this->createRequest($this->faker->ipv4(), $this->faker->userAgent());
        $dto = new SignInDto($this->faker->email(), $this->faker->password(), false);

        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(/**
             * @return true
             */
            static function (SignInCommand $command): bool {
                $command->setResponse(
                    new SignInCommandResponse(
                        true,
                        'unexpected-access-token',
                        null,
                        'pending-session-with-token'
                    )
                );

                return true;
            }));

        $processor = new SignInProcessor($this->commandBus, $this->requestStack);
        $response = $processor->process($dto, $this->operation, [], ['request' => $request]);

        $this->assertCount(0, $response->headers->getCookies());
    }

    public function testProcessDoesNotSetCookieWhenAccessTokenIsMissing(): void
    {
        $request = $this->createRequest($this->faker->ipv4(), $this->faker->userAgent());
        $dto = new SignInDto($this->faker->email(), $this->faker->password(), false);

        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(/**
             * @return true
             */
            static function (SignInCommand $command): bool {
                $command->setResponse(
                    new SignInCommandResponse(
                        false,
                        null,
                        'refresh-token'
                    )
                );

                return true;
            }));

        $processor = new SignInProcessor($this->commandBus, $this->requestStack);
        $response = $processor->process($dto, $this->operation, [], ['request' => $request]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode([
                '2fa_enabled' => false,
                'access_token' => '',
                'refresh_token' => 'refresh-token',
            ]),
            (string) $response->getContent()
        );
        $this->assertCount(0, $response->headers->getCookies());
    }

    public function testProcessCastsMissingRefreshTokenToEmptyStringInResponseBody(): void
    {
        $request = $this->createRequest($this->faker->ipv4(), $this->faker->userAgent());
        $dto = new SignInDto($this->faker->email(), $this->faker->password(), false);

        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(/**
             * @return true
             */
            static function (SignInCommand $command): bool {
                $command->setResponse(
                    new SignInCommandResponse(
                        false,
                        'access-token',
                        null
                    )
                );

                return true;
            }));

        $processor = new SignInProcessor($this->commandBus, $this->requestStack);
        $response = $processor->process($dto, $this->operation, [], ['request' => $request]);

        $this->assertJsonStringEqualsJsonString(
            json_encode([
                '2fa_enabled' => false,
                'access_token' => 'access-token',
                'refresh_token' => '',
            ]),
            (string) $response->getContent()
        );
    }

    public function testProcessPropagatesUnauthorizedExceptionWithBearerHeader(): void
    {
        $request = $this->createRequest($this->faker->ipv4(), $this->faker->userAgent());
        $dto = new SignInDto($this->faker->email(), $this->faker->password(), false);

        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->willThrowException(
                new UnauthorizedHttpException('Bearer', 'Invalid credentials.')
            );

        $processor = new SignInProcessor($this->commandBus, $this->requestStack);

        try {
            $processor->process($dto, $this->operation, [], ['request' => $request]);
            $this->fail('Expected UnauthorizedHttpException to be thrown.');
        } catch (UnauthorizedHttpException $exception) {
            $this->assertSame('Invalid credentials.', $exception->getMessage());
            $this->assertSame('Bearer', $exception->getHeaders()['WWW-Authenticate'] ?? null);
        }
    }

    public function testProcessUsesRequestStackWhenContextRequestIsMissing(): void
    {
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();
        $request = $this->createRequest($ipAddress, $userAgent);
        $this->requestStack->push($request);

        $dto = new SignInDto($this->faker->email(), $this->faker->password(), false);

        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(/**
             * @return true
             */
            function (SignInCommand $command) use ($ipAddress, $userAgent): bool {
                $this->assertSame($ipAddress, $command->ipAddress);
                $this->assertSame($userAgent, $command->userAgent);

                $command->setResponse(
                    new SignInCommandResponse(
                        false,
                        'stack-token',
                        'refresh-token'
                    )
                );

                return true;
            }));

        $processor = new SignInProcessor($this->commandBus, $this->requestStack);
        $response = $processor->process($dto, $this->operation);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testProcessUsesContextRequestInsteadOfRequestStackWhenBothProvided(): void
    {
        $stackRequest = $this->createRequest('203.0.113.10', 'Stack Agent');
        $contextRequest = $this->createRequest('198.51.100.15', 'Context Agent');
        $this->requestStack->push($stackRequest);

        $dto = new SignInDto($this->faker->email(), $this->faker->password(), false);

        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(/**
             * @return true
             */
            function (SignInCommand $command): bool {
                $this->assertSame('198.51.100.15', $command->ipAddress);
                $this->assertSame('Context Agent', $command->userAgent);

                $command->setResponse(
                    new SignInCommandResponse(
                        false,
                        'context-token',
                        'refresh-token'
                    )
                );

                return true;
            }));

        $processor = new SignInProcessor($this->commandBus, $this->requestStack);
        $response = $processor->process($dto, $this->operation, [], ['request' => $contextRequest]);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testProcessFallsBackToEmptyRequestMetadataWhenNoRequestExists(): void
    {
        $dto = new SignInDto($this->faker->email(), $this->faker->password(), false);

        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(/**
             * @return true
             */
            function (SignInCommand $command): bool {
                $this->assertSame('', $command->ipAddress);
                $this->assertSame('', $command->userAgent);

                $command->setResponse(
                    new SignInCommandResponse(
                        false,
                        'empty-request-token',
                        'refresh-token'
                    )
                );

                return true;
            }));

        $processor = new SignInProcessor($this->commandBus, $this->requestStack);
        $response = $processor->process($dto, $this->operation);

        $this->assertSame(200, $response->getStatusCode());
    }

    private function createRequest(string $ipAddress, string $userAgent): Request
    {
        return Request::create(
            '/api/signin',
            'POST',
            [],
            [],
            [],
            [
                'REMOTE_ADDR' => $ipAddress,
                'HTTP_USER_AGENT' => $userAgent,
            ]
        );
    }
}
