<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Shared\Application\Bus\Guard\CommandResponseTypeGuard;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\SignInCommand;
use App\User\Application\DTO\SignInCommandResponse;
use App\User\Application\DTO\SignInDto;
use App\User\Application\Factory\AuthCookieFactoryInterface;
use App\User\Application\Factory\SignInCommandFactory;
use App\User\Application\Processor\SignInProcessor;
use App\User\Application\Resolver\HttpRequestContextResolverInterface;
use App\User\Application\Service\SignInCommandDispatcher;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class SignInProcessorTest extends AuthProcessorTestCase
{
    private CommandBusInterface&MockObject $commandBus;
    private HttpRequestContextResolverInterface&MockObject $requestContextResolver;
    private AuthCookieFactoryInterface&MockObject $cookieFactory;
    private Operation $operation;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->requestContextResolver = $this->createMock(
            HttpRequestContextResolverInterface::class
        );
        $this->cookieFactory = $this->createMock(AuthCookieFactoryInterface::class);
        $this->operation = $this->createMock(Operation::class);
    }

    public function testProcessReturnsTokensAndAttachesCookie(): void
    {
        [$email, $password, $ip, $ua] = $this->generateCredentials();
        $access = $this->faker->sha256();
        $refresh = $this->faker->sha256();
        $request = $this->createMock(Request::class);
        $this->stubRequestContextResolver($this->requestContextResolver, $request, $ip, $ua);
        $cmdResponse = new SignInCommandResponse(false, $access, $refresh);
        $this->expectDispatchValidatingCommand($email, $password, $ip, $ua, $cmdResponse);
        $this->cookieFactory->expects($this->once())->method('create')
            ->with($access, false)
            ->willReturn(Cookie::create('__Host-auth_token', $access));
        $response = $this->processWithRequest(new SignInDto($email, $password), $request);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertTokenResponseBody($response, false, $access, $refresh);
    }

    public function testProcessAttachesCookieWithRememberMe(): void
    {
        $request = $this->stubRandomRequestContext($this->requestContextResolver);
        $dto = new SignInDto($this->faker->email(), $this->faker->password());
        $dto->setRememberMe(true);
        $accessToken = $this->faker->sha256();
        $cmdResponse = new SignInCommandResponse(false, $accessToken, $this->faker->sha256());
        $this->expectDispatchWithRememberMe($cmdResponse);
        $this->cookieFactory->expects($this->once())->method('create')
            ->with($accessToken, true)
            ->willReturn(Cookie::create('__Host-auth_token', $accessToken));
        $response = $this->processWithRequest($dto, $request);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testProcessReturnsTwoFactorBodyWithoutAttachingCookie(): void
    {
        $request = $this->stubRandomRequestContext($this->requestContextResolver);
        $dto = new SignInDto($this->faker->email(), $this->faker->password());
        $pendingSessionId = $this->faker->uuid();
        $cmdResponse = new SignInCommandResponse(true, null, null, $pendingSessionId);
        $this->expectDispatchWithResponse($cmdResponse);
        $this->cookieFactory->expects($this->never())->method('create');
        $response = $this->processWithRequest($dto, $request);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertTwoFactorResponseBody(
            $response,
            $pendingSessionId
        );
    }

    public function testProcessDoesNotAttachCookieForTwoFactorEvenWithTokenValue(): void
    {
        $request = $this->stubRandomRequestContext($this->requestContextResolver);
        $dto = new SignInDto($this->faker->email(), $this->faker->password());
        $cmdResponse = new SignInCommandResponse(
            true,
            $this->faker->sha256(),
            null,
            $this->faker->uuid()
        );
        $this->expectDispatchWithResponse($cmdResponse);
        $this->cookieFactory->expects($this->never())->method('create');
        $this->processWithRequest($dto, $request);
    }

    public function testProcessDoesNotAttachCookieWhenAccessTokenIsMissing(): void
    {
        $request = $this->stubRandomRequestContext($this->requestContextResolver);
        $dto = new SignInDto($this->faker->email(), $this->faker->password());
        $refreshToken = $this->faker->sha256();
        $this->expectDispatchWithResponse(new SignInCommandResponse(false, null, $refreshToken));
        $this->cookieFactory->expects($this->never())->method('create');
        $response = $this->processWithRequest($dto, $request);
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
        $this->stubRequestContextResolver(
            $this->requestContextResolver,
            $request,
            $this->faker->ipv4(),
            $this->faker->userAgent()
        );
        $dto = new SignInDto($this->faker->email(), $this->faker->password());
        $accessToken = $this->faker->sha256();
        $this->expectDispatchWithResponse(new SignInCommandResponse(false, $accessToken, null));
        $this->cookieFactory->expects($this->once())->method('create')
            ->with($accessToken, false)
            ->willReturn(Cookie::create('__Host-auth_token', $accessToken));
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
        $this->stubRequestContextResolver(
            $this->requestContextResolver,
            $request,
            $this->faker->ipv4(),
            $this->faker->userAgent()
        );
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
        $request = $this->createMock(Request::class);
        [$ipAddress, $userAgent] = $this->expectResolvedRequestContext(
            $this->requestContextResolver,
            $request,
            $request
        );
        $cmdResponse = $this->createCommandResponse();
        $this->expectDispatchWithRequestMetadata(
            $this->commandBus,
            SignInCommand::class,
            $cmdResponse,
            $ipAddress,
            $userAgent
        );
        $response = $this->processWithRequest(
            new SignInDto($this->faker->email(), $this->faker->password()),
            $request
        );
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testProcessPassesNullToResolverWhenContextRequestIsMissing(): void
    {
        $resolvedRequest = $this->createMock(Request::class);
        [$ipAddress, $userAgent] = $this->expectResolvedRequestContext(
            $this->requestContextResolver,
            null,
            $resolvedRequest
        );
        $cmdResponse = $this->createCommandResponse();
        $this->expectDispatchWithRequestMetadata(
            $this->commandBus,
            SignInCommand::class,
            $cmdResponse,
            $ipAddress,
            $userAgent
        );
        $response = $this->processWithRequest(
            new SignInDto($this->faker->email(), $this->faker->password())
        );
        $this->assertSame(200, $response->getStatusCode());
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
        $this->expectDispatchMatchingCommand(
            $this->commandBus,
            SignInCommand::class,
            $response,
            function (SignInCommand $cmd) use (
                $email,
                $password,
                $ipAddress,
                $userAgent,
            ): void {
                $this->assertSame($email, $cmd->email);
                $this->assertSame($password, $cmd->password);
                $this->assertFalse($cmd->rememberMe);
                $this->assertSame($ipAddress, $cmd->ipAddress);
                $this->assertSame($userAgent, $cmd->userAgent);
            }
        );
    }

    private function expectDispatchWithResponse(SignInCommandResponse $response): void
    {
        $this->expectDispatchMatchingCommand(
            $this->commandBus,
            SignInCommand::class,
            $response,
            static function (SignInCommand $cmd): void {
                self::assertNotSame('', $cmd->email);
            }
        );
    }

    private function expectDispatchWithRememberMe(SignInCommandResponse $response): void
    {
        $this->expectDispatchMatchingCommand(
            $this->commandBus,
            SignInCommand::class,
            $response,
            function (SignInCommand $cmd): void {
                $this->assertTrue($cmd->rememberMe);
            }
        );
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

    private function processWithRequest(SignInDto $dto, ?Request $request = null): mixed
    {
        if ($request === null) {
            return $this->createProcessor()->process($dto, $this->operation);
        }

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
            new SignInCommandDispatcher(
                $this->commandBus,
                new CommandResponseTypeGuard(),
                new SignInCommandFactory(),
                $this->requestContextResolver
            ),
            $this->cookieFactory,
        );
    }
}
