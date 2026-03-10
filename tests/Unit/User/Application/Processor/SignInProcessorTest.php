<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\SignInCommand;
use App\User\Application\DTO\SignInCommandResponse;
use App\User\Application\DTO\SignInDto;
use App\User\Application\Factory\SignInCommandFactory;
use App\User\Application\Processor\SignInProcessor;
use App\User\Application\Provider\AuthCookieProviderInterface;
use App\User\Application\Resolver\HttpRequestContextResolverInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class SignInProcessorTest extends UnitTestCase
{
    private CommandBusInterface&MockObject $commandBus;
    private HttpRequestContextResolverInterface&MockObject $requestContextResolver;
    private AuthCookieProviderInterface&MockObject $cookieProvider;
    private Operation $operation;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->requestContextResolver = $this->createMock(
            HttpRequestContextResolverInterface::class
        );
        $this->cookieProvider = $this->createMock(AuthCookieProviderInterface::class);
        $this->operation = $this->createMock(Operation::class);
    }

    public function testProcessReturnsTokensAndAttachesCookie(): void
    {
        [$email, $password, $ip, $ua] = $this->generateCredentials();
        $access = $this->faker->sha256();
        $refresh = $this->faker->sha256();
        $request = $this->createMock(Request::class);
        $this->stubResolver($request, $ip, $ua);
        $cmdResponse = new SignInCommandResponse(false, $access, $refresh);
        $this->expectDispatchValidatingCommand($email, $password, $ip, $ua, $cmdResponse);
        $this->cookieProvider->expects($this->once())->method('attach')
            ->with($this->isInstanceOf(JsonResponse::class), $access, false);
        $response = $this->processWithRequest(new SignInDto($email, $password), $request);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertTokenResponseBody($response, false, $access, $refresh);
    }

    public function testProcessAttachesCookieWithRememberMe(): void
    {
        $request = $this->createMock(Request::class);
        $this->stubResolver($request, $this->faker->ipv4(), $this->faker->userAgent());
        $dto = new SignInDto($this->faker->email(), $this->faker->password());
        $dto->setRememberMe(true);
        $accessToken = $this->faker->sha256();
        $cmdResponse = new SignInCommandResponse(false, $accessToken, $this->faker->sha256());
        $this->expectDispatchWithRememberMe($cmdResponse);
        $this->cookieProvider->expects($this->once())->method('attach')
            ->with($this->anything(), $accessToken, true);
        $response = $this->processWithRequest($dto, $request);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testProcessReturnsTwoFactorBodyWithoutAttachingCookie(): void
    {
        $request = $this->createMock(Request::class);
        $this->stubResolver($request, $this->faker->ipv4(), $this->faker->userAgent());
        $dto = new SignInDto($this->faker->email(), $this->faker->password());
        $pendingSessionId = $this->faker->uuid();
        $cmdResponse = new SignInCommandResponse(true, null, null, $pendingSessionId);
        $this->expectDispatchWithResponse($cmdResponse);
        $this->cookieProvider->expects($this->never())->method('attach');
        $response = $this->createProcessor()->process(
            $dto,
            $this->operation,
            [],
            ['request' => $request]
        );
        $this->assertSame(200, $response->getStatusCode());
        $this->assertTwoFactorResponseBody(
            $response,
            $pendingSessionId
        );
    }

    public function testProcessDoesNotAttachCookieForTwoFactorEvenWithTokenValue(): void
    {
        $request = $this->createMock(Request::class);
        $this->stubResolver($request, $this->faker->ipv4(), $this->faker->userAgent());
        $dto = new SignInDto($this->faker->email(), $this->faker->password());
        $cmdResponse = new SignInCommandResponse(
            true,
            $this->faker->sha256(),
            null,
            $this->faker->uuid()
        );
        $this->expectDispatchWithResponse($cmdResponse);
        $this->cookieProvider->expects($this->never())->method('attach');
        $this->createProcessor()->process(
            $dto,
            $this->operation,
            [],
            ['request' => $request]
        );
    }

    public function testProcessDoesNotAttachCookieWhenAccessTokenIsMissing(): void
    {
        $request = $this->createMock(Request::class);
        $this->stubResolver($request, $this->faker->ipv4(), $this->faker->userAgent());
        $dto = new SignInDto($this->faker->email(), $this->faker->password());
        $refreshToken = $this->faker->sha256();
        $this->expectDispatchWithResponse(new SignInCommandResponse(false, null, $refreshToken));
        $this->cookieProvider->expects($this->never())->method('attach');
        $response = $this->createProcessor()->process(
            $dto,
            $this->operation,
            [],
            ['request' => $request]
        );
        $this->assertSame(200, $response->getStatusCode());
        $this->assertTokenResponseBody(
            $response,
            false,
            '',
            $refreshToken
        );
    }

    public function testProcessCastsMissingRefreshTokenToEmptyStringInResponseBody(): void
    {
        $request = $this->createMock(Request::class);
        $this->stubResolver($request, $this->faker->ipv4(), $this->faker->userAgent());
        $dto = new SignInDto($this->faker->email(), $this->faker->password());
        $accessToken = $this->faker->sha256();
        $this->expectDispatchWithResponse(new SignInCommandResponse(false, $accessToken, null));
        $this->cookieProvider->expects($this->once())->method('attach');
        $response = $this->createProcessor()->process(
            $dto,
            $this->operation,
            [],
            ['request' => $request]
        );
        $this->assertTokenResponseBody($response, false, $accessToken, '');
    }

    public function testProcessPropagatesUnauthorizedExceptionWithBearerHeader(): void
    {
        $request = $this->createMock(Request::class);
        $this->stubResolver($request, $this->faker->ipv4(), $this->faker->userAgent());
        $dto = new SignInDto($this->faker->email(), $this->faker->password());
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->willThrowException(new UnauthorizedHttpException('Bearer', 'Invalid credentials.'));
        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid credentials.');
        $this->createProcessor()->process($dto, $this->operation, [], ['request' => $request]);
    }

    public function testProcessDelegatesContextRequestToResolver(): void
    {
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();
        $request = $this->createMock(Request::class);
        $this->requestContextResolver->expects($this->once())
            ->method('resolveRequest')->with($request)->willReturn($request);
        $this->stubResolverMetadata($request, $ipAddress, $userAgent);
        $cmdResponse = $this->createCommandResponse();
        $this->expectDispatchValidatingRequestMetadata($ipAddress, $userAgent, $cmdResponse);
        $response = $this->createProcessor()->process(
            new SignInDto($this->faker->email(), $this->faker->password()),
            $this->operation,
            [],
            ['request' => $request]
        );
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testProcessPassesNullToResolverWhenContextRequestIsMissing(): void
    {
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();
        $resolvedRequest = $this->createMock(Request::class);
        $this->requestContextResolver->expects($this->once())
            ->method('resolveRequest')->with(null)->willReturn($resolvedRequest);
        $this->stubResolverMetadata($resolvedRequest, $ipAddress, $userAgent);
        $cmdResponse = $this->createCommandResponse();
        $this->expectDispatchValidatingRequestMetadata($ipAddress, $userAgent, $cmdResponse);
        $response = $this->createProcessor()->process(
            new SignInDto($this->faker->email(), $this->faker->password()),
            $this->operation
        );
        $this->assertSame(200, $response->getStatusCode());
    }

    private function stubResolver(
        ?Request $request,
        string $ipAddress,
        string $userAgent
    ): void {
        $this->requestContextResolver->method('resolveRequest')->willReturn($request);
        $this->stubResolverMetadata($request, $ipAddress, $userAgent);
    }

    private function stubResolverMetadata(
        ?Request $request,
        string $ipAddress,
        string $userAgent
    ): void {
        $this->requestContextResolver->method('resolveIpAddress')
            ->with($request)->willReturn($ipAddress);
        $this->requestContextResolver->method('resolveUserAgent')
            ->with($request)->willReturn($userAgent);
    }

    private function createCommandResponse(): SignInCommandResponse
    {
        return new SignInCommandResponse(
            false,
            $this->faker->sha256(),
            $this->faker->sha256()
        );
    }

    private function expectDispatchValidatingCommand(
        string $email,
        string $password,
        string $ipAddress,
        string $userAgent,
        SignInCommandResponse $response
    ): void {
        $this->commandBus->expects($this->once())->method('dispatch')
            ->with($this->callback(
                function (SignInCommand $cmd) use (
                    $email,
                    $password,
                    $ipAddress,
                    $userAgent,
                    $response
                ): bool {
                    $this->assertSame($email, $cmd->email);
                    $this->assertSame($password, $cmd->password);
                    $this->assertFalse($cmd->rememberMe);
                    $this->assertSame($ipAddress, $cmd->ipAddress);
                    $this->assertSame($userAgent, $cmd->userAgent);
                    $cmd->setResponse($response);
                    return true;
                }
            ));
    }

    private function expectDispatchWithResponse(SignInCommandResponse $response): void
    {
        $this->commandBus->expects($this->once())->method('dispatch')
            ->with($this->callback(
                static function (SignInCommand $cmd) use ($response): bool {
                    $cmd->setResponse($response);
                    return true;
                }
            ));
    }

    private function expectDispatchWithRememberMe(SignInCommandResponse $response): void
    {
        $this->commandBus->expects($this->once())->method('dispatch')
            ->with($this->callback(
                function (SignInCommand $cmd) use ($response): bool {
                    $this->assertTrue($cmd->rememberMe);
                    $cmd->setResponse($response);
                    return true;
                }
            ));
    }

    private function expectDispatchValidatingRequestMetadata(
        string $expectedIp,
        string $expectedAgent,
        SignInCommandResponse $response
    ): void {
        $this->commandBus->expects($this->once())->method('dispatch')
            ->with($this->callback(
                function (SignInCommand $cmd) use ($expectedIp, $expectedAgent, $response): bool {
                    $this->assertSame($expectedIp, $cmd->ipAddress);
                    $this->assertSame($expectedAgent, $cmd->userAgent);
                    $cmd->setResponse($response);
                    return true;
                }
            ));
    }

    private function assertTokenResponseBody(
        mixed $response,
        bool $twoFaEnabled,
        string $accessToken,
        string $refreshToken
    ): void {
        $this->assertJsonStringEqualsJsonString(
            json_encode([
                '2fa_enabled' => $twoFaEnabled,
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
            ]),
            (string) $response->getContent()
        );
    }

    private function assertTwoFactorResponseBody(mixed $response, string $pendingSessionId): void
    {
        $this->assertJsonStringEqualsJsonString(
            json_encode([
                '2fa_enabled' => true,
                'pending_session_id' => $pendingSessionId,
            ]),
            (string) $response->getContent()
        );
    }

    /**
     * @return array{string, string, string, string}
     */
    private function generateCredentials(): array
    {
        return [
            $this->faker->email(),
            $this->faker->password(),
            $this->faker->ipv4(),
            $this->faker->userAgent(),
        ];
    }

    private function processWithRequest(SignInDto $dto, Request $request): mixed
    {
        return $this->createProcessor()->process(
            $dto,
            $this->operation,
            [],
            ['request' => $request]
        );
    }

    private function createProcessor(): SignInProcessor
    {
        return new SignInProcessor(
            $this->commandBus,
            new SignInCommandFactory(),
            $this->requestContextResolver,
            $this->cookieProvider,
        );
    }
}
