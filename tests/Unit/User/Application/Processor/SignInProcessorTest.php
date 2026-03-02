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

    public function testConstructorDefinesExpectedDefaultCookieTtls(): void
    {
        $constructor = new \ReflectionMethod(SignInProcessor::class, '__construct');
        $parameters = $constructor->getParameters();

        $this->assertSame(900, $parameters[3]->getDefaultValue());
        $this->assertSame(2592000, $parameters[4]->getDefaultValue());
    }

    public function testProcessReturnsTokensAndSetsStandardCookie(): void
    {
        $email = $this->faker->email();
        $password = $this->faker->password();
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();
        $access = 'access-token-value';
        $refresh = 'refresh-token-value';
        $request = $this->createRequest($ipAddress, $userAgent);
        $this->requestStack->push($request);
        $dto = new SignInDto($email, $password);
        $cmd = new SignInCommandResponse(false, $access, $refresh);
        $this->expectDispatchValidatingCommand($email, $password, $ipAddress, $userAgent, $cmd);
        $processor = $this->createProcessor();
        $response = $processor->process($dto, $this->operation, [], ['request' => $request]);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertTokenResponseBody($response, false, $access, $refresh);
        $this->assertCount(1, $response->headers->getCookies());
        $this->assertStandardAuthCookie($response->headers->getCookies()[0], $access);
    }

    public function testProcessSetsRememberMeCookieMaxAge(): void
    {
        $request = $this->createRequest($this->faker->ipv4(), $this->faker->userAgent());
        $dto = new SignInDto($this->faker->email(), $this->faker->password());
        $dto->setRememberMe(true);
        $this->expectDispatchWithRememberMe(
            new SignInCommandResponse(false, 'remember-token', 'refresh-token')
        );
        $response = $this->createProcessor()->process(
            $dto,
            $this->operation,
            [],
            ['request' => $request]
        );
        $cookies = $response->headers->getCookies();
        $this->assertCount(1, $cookies);
        $this->assertGreaterThanOrEqual(2591999, $cookies[0]->getMaxAge());
        $this->assertLessThanOrEqual(2592000, $cookies[0]->getMaxAge());
    }

    public function testProcessReturnsTwoFactorBodyWithoutCookie(): void
    {
        $request = $this->createRequest($this->faker->ipv4(), $this->faker->userAgent());
        $dto = new SignInDto($this->faker->email(), $this->faker->password());
        $cmdResponse = new SignInCommandResponse(true, null, null, 'pending-session-123');
        $this->expectDispatchWithResponse($cmdResponse);
        $processor = $this->createProcessor();
        $response = $processor->process($dto, $this->operation, [], ['request' => $request]);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertTwoFactorResponseBody($response, 'pending-session-123');
        $this->assertCount(0, $response->headers->getCookies());
    }

    public function testProcessDoesNotSetCookieForTwoFactorResponseEvenWithTokenValue(): void
    {
        $request = $this->createRequest($this->faker->ipv4(), $this->faker->userAgent());
        $dto = new SignInDto($this->faker->email(), $this->faker->password());
        $cmdResponse = new SignInCommandResponse(
            true,
            'unexpected-access-token',
            null,
            'pending-session-with-token'
        );
        $this->expectDispatchWithResponse($cmdResponse);
        $processor = $this->createProcessor();
        $result = $processor->process($dto, $this->operation, [], ['request' => $request]);
        $this->assertCount(0, $result->headers->getCookies());
    }

    public function testProcessDoesNotSetCookieWhenAccessTokenIsMissing(): void
    {
        $request = $this->createRequest($this->faker->ipv4(), $this->faker->userAgent());
        $dto = new SignInDto($this->faker->email(), $this->faker->password());
        $this->expectDispatchWithResponse(new SignInCommandResponse(false, null, 'refresh-token'));
        $processor = $this->createProcessor();
        $response = $processor->process($dto, $this->operation, [], ['request' => $request]);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertTokenResponseBody($response, false, '', 'refresh-token');
        $this->assertCount(0, $response->headers->getCookies());
    }

    public function testProcessCastsMissingRefreshTokenToEmptyStringInResponseBody(): void
    {
        $request = $this->createRequest($this->faker->ipv4(), $this->faker->userAgent());
        $dto = new SignInDto($this->faker->email(), $this->faker->password());
        $this->expectDispatchWithResponse(new SignInCommandResponse(false, 'access-token', null));
        $processor = $this->createProcessor();
        $response = $processor->process($dto, $this->operation, [], ['request' => $request]);
        $this->assertTokenResponseBody($response, false, 'access-token', '');
    }

    public function testProcessPropagatesUnauthorizedExceptionWithBearerHeader(): void
    {
        $request = $this->createRequest($this->faker->ipv4(), $this->faker->userAgent());
        $dto = new SignInDto($this->faker->email(), $this->faker->password());

        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->willThrowException(
                new UnauthorizedHttpException('Bearer', 'Invalid credentials.')
            );

        $processor = $this->createProcessor();

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
        $dto = new SignInDto($this->faker->email(), $this->faker->password());
        $cmdResponse = new SignInCommandResponse(false, 'stack-token', 'refresh-token');
        $this->expectDispatchValidatingRequestMetadata($ipAddress, $userAgent, $cmdResponse);
        $processor = $this->createProcessor();
        $response = $processor->process($dto, $this->operation);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testProcessUsesContextRequestInsteadOfRequestStackWhenBothProvided(): void
    {
        $stackRequest = $this->createRequest('203.0.113.10', 'Stack Agent');
        $contextRequest = $this->createRequest('198.51.100.15', 'Context Agent');
        $this->requestStack->push($stackRequest);
        $dto = new SignInDto($this->faker->email(), $this->faker->password());
        $cmdResponse = new SignInCommandResponse(false, 'context-token', 'refresh-token');
        $this->expectDispatchValidatingRequestMetadata(
            '198.51.100.15',
            'Context Agent',
            $cmdResponse
        );
        $result = $this->createProcessor()->process(
            $dto,
            $this->operation,
            [],
            ['request' => $contextRequest]
        );
        $this->assertSame(200, $result->getStatusCode());
    }

    public function testProcessFallsBackToEmptyRequestMetadataWhenNoRequestExists(): void
    {
        $dto = new SignInDto($this->faker->email(), $this->faker->password());
        $cmdResponse = new SignInCommandResponse(false, 'empty-request-token', 'refresh-token');
        $this->expectDispatchValidatingRequestMetadata('', '', $cmdResponse);
        $processor = $this->createProcessor();
        $response = $processor->process($dto, $this->operation);
        $this->assertSame(200, $response->getStatusCode());
    }

    private function expectDispatchValidatingCommand(
        string $email,
        string $password,
        string $ipAddress,
        string $userAgent,
        SignInCommandResponse $response
    ): void {
        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
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
        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(
                static function (SignInCommand $cmd) use ($response): bool {
                    $cmd->setResponse($response);
                    return true;
                }
            ));
    }

    private function expectDispatchWithRememberMe(SignInCommandResponse $response): void
    {
        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
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
        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(
                function (SignInCommand $cmd) use ($expectedIp, $expectedAgent, $response): bool {
                    $this->assertSame($expectedIp, $cmd->ipAddress);
                    $this->assertSame($expectedAgent, $cmd->userAgent);
                    $cmd->setResponse($response);
                    return true;
                }
            ));
    }

    private function assertStandardAuthCookie(Cookie $cookie, string $expectedValue): void
    {
        $this->assertSame('__Host-auth_token', $cookie->getName());
        $this->assertSame($expectedValue, $cookie->getValue());
        $this->assertSame('/', $cookie->getPath());
        $this->assertNull($cookie->getDomain());
        $this->assertTrue($cookie->isSecure());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame(Cookie::SAMESITE_LAX, $cookie->getSameSite());
        $this->assertGreaterThanOrEqual(899, $cookie->getMaxAge());
        $this->assertLessThanOrEqual(900, $cookie->getMaxAge());
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

    private function createProcessor(): SignInProcessor
    {
        return new SignInProcessor(
            $this->commandBus,
            $this->requestStack,
            new SignInCommandFactory()
        );
    }
}
