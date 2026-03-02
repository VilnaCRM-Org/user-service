<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\CompleteTwoFactorCommand;
use App\User\Application\DTO\CompleteTwoFactorCommandResponse;
use App\User\Application\DTO\CompleteTwoFactorDto;
use App\User\Application\Factory\CompleteTwoFactorCommandFactory;
use App\User\Application\Processor\CompleteTwoFactorProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class CompleteTwoFactorProcessorTest extends UnitTestCase
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
        $constructor = new \ReflectionMethod(
            CompleteTwoFactorProcessor::class,
            '__construct'
        );
        $parameters = $constructor->getParameters();

        $this->assertSame(900, $parameters[3]->getDefaultValue());
        $this->assertSame(2592000, $parameters[4]->getDefaultValue());
    }

    public function testProcessReturnsTokensAndSetsCookieWithStandardTtl(): void
    {
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();
        $request = $this->createRequest($ipAddress, $userAgent);
        $dto = new CompleteTwoFactorDto('01ARZ3NDEKTSV4RRFFQ69G5FAZ', '123456');
        $this->expectDispatchValidatingMetadata(
            '01ARZ3NDEKTSV4RRFFQ69G5FAZ',
            '123456',
            $ipAddress,
            $userAgent
        );
        $response = $this->processDto($dto, $request);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertExpectedTokensInResponse($response);
        $this->assertStandardAuthCookie(
            $response->headers->getCookies(),
            'issued-access-token',
            899,
            900
        );
    }

    public function testProcessSetsCookieWithRememberMeTtlWhenRememberMeIsTrue(): void
    {
        $request = $this->createRequest($this->faker->ipv4(), $this->faker->userAgent());
        $dto = new CompleteTwoFactorDto('01ARZ3NDEKTSV4RRFFQ69G5FZZ', '123456');
        $commandResponse = (new CompleteTwoFactorCommandResponse(
            'remember-access-token',
            'remember-refresh-token'
        ))->withRememberMe();
        $this->expectDispatchSetsResponse($commandResponse);
        $response = $this->processDto($dto, $request);
        $cookies = $response->headers->getCookies();
        $this->assertCount(1, $cookies);
        $this->assertGreaterThanOrEqual(2591999, $cookies[0]->getMaxAge());
        $this->assertLessThanOrEqual(2592000, $cookies[0]->getMaxAge());
    }

    public function testProcessDoesNotSetCookieWhenAccessTokenIsEmpty(): void
    {
        $request = $this->createRequest($this->faker->ipv4(), $this->faker->userAgent());
        $dto = new CompleteTwoFactorDto('01ARZ3NDEKTSV4RRFFQ69G5FB0', '123456');
        $this->expectDispatchSetsResponse(
            new CompleteTwoFactorCommandResponse('', 'issued-refresh-token')
        );
        $response = $this->processDto($dto, $request);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(0, $response->headers->getCookies());
    }

    public function testProcessUsesRequestStackWhenContextRequestIsMissing(): void
    {
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();
        $this->requestStack->push($this->createRequest($ipAddress, $userAgent));
        $dto = new CompleteTwoFactorDto('01ARZ3NDEKTSV4RRFFQ69G5FB1', '123456');
        $this->expectDispatchAssertingRequestMetadata(
            $ipAddress,
            $userAgent,
            new CompleteTwoFactorCommandResponse('stack-access-token', 'stack-refresh-token')
        );
        $response = $this->processDto($dto);
        $this->assertSame(200, $response->getStatusCode());
        $cookies = $response->headers->getCookies();
        $this->assertCount(1, $cookies);
        $this->assertGreaterThanOrEqual(899, $cookies[0]->getMaxAge());
        $this->assertLessThanOrEqual(900, $cookies[0]->getMaxAge());
    }

    public function testProcessFallsBackToEmptyRequestMetadataWhenNoRequestAvailable(): void
    {
        $dto = new CompleteTwoFactorDto('01ARZ3NDEKTSV4RRFFQ69G5FB9', '123456');
        $this->expectDispatchAssertingRequestMetadata(
            '',
            '',
            new CompleteTwoFactorCommandResponse(
                'no-request-access-token',
                'no-request-refresh-token'
            )
        );
        $response = $this->processDto($dto);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testProcessIncludesRecoveryCodeWarningFieldsInResponse(): void
    {
        $request = $this->createRequest($this->faker->ipv4(), $this->faker->userAgent());
        $dto = new CompleteTwoFactorDto('01ARZ3NDEKTSV4RRFFQ69G5FB2', 'AB12-CD34');
        $this->expectDispatchSetsResponse(new CompleteTwoFactorCommandResponse(
            'recovery-access-token',
            'recovery-refresh-token',
            1,
            'Only 1 recovery code(s) remaining. Regenerate soon.'
        ));
        $response = $this->processDto($dto, $request);
        $body = json_decode((string) $response->getContent(), true);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, $body['recovery_codes_remaining']);
        $this->assertSame(
            'Only 1 recovery code(s) remaining. Regenerate soon.',
            $body['warning']
        );
    }

    public function testProcessOmitsRecoveryCodeFieldsWhenNull(): void
    {
        $request = $this->createRequest($this->faker->ipv4(), $this->faker->userAgent());
        $dto = new CompleteTwoFactorDto('01ARZ3NDEKTSV4RRFFQ69G5FB3', '123456');
        $this->expectDispatchSetsResponse(
            new CompleteTwoFactorCommandResponse('totp-access-token', 'totp-refresh-token')
        );
        $response = $this->processDto($dto, $request);
        $body = json_decode((string) $response->getContent(), true);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayNotHasKey('recovery_codes_remaining', $body);
        $this->assertArrayNotHasKey('warning', $body);
    }

    private function createRequest(string $ipAddress, string $userAgent): Request
    {
        return Request::create(
            '/api/signin/2fa',
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

    private function expectDispatchValidatingMetadata(
        string $pendingSessionId,
        string $totpCode,
        string $ipAddress,
        string $userAgent
    ): void {
        $this->commandBus->expects($this->once())->method('dispatch')
            ->with($this->callback(
                function (CompleteTwoFactorCommand $cmd) use (
                    $pendingSessionId,
                    $totpCode,
                    $ipAddress,
                    $userAgent
                ): bool {
                    $this->assertSame($pendingSessionId, $cmd->pendingSessionId);
                    $this->assertSame($totpCode, $cmd->twoFactorCode);
                    $this->assertSame($ipAddress, $cmd->ipAddress);
                    $this->assertSame($userAgent, $cmd->userAgent);
                    $cmd->setResponse(new CompleteTwoFactorCommandResponse(
                        'issued-access-token',
                        'issued-refresh-token'
                    ));
                    return true;
                }
            ));
    }

    private function expectDispatchSetsResponse(
        CompleteTwoFactorCommandResponse $response
    ): void {
        $this->commandBus->expects($this->once())->method('dispatch')
            ->with($this->callback(
                static function (CompleteTwoFactorCommand $cmd) use ($response): bool {
                    $cmd->setResponse($response);
                    return true;
                }
            ));
    }

    private function expectDispatchAssertingRequestMetadata(
        string $ipAddress,
        string $userAgent,
        CompleteTwoFactorCommandResponse $response
    ): void {
        $this->commandBus->expects($this->once())->method('dispatch')
            ->with($this->callback(
                function (CompleteTwoFactorCommand $cmd) use (
                    $ipAddress,
                    $userAgent,
                    $response
                ): bool {
                    $this->assertSame($ipAddress, $cmd->ipAddress);
                    $this->assertSame($userAgent, $cmd->userAgent);
                    $cmd->setResponse($response);
                    return true;
                }
            ));
    }

    private function processDto(
        CompleteTwoFactorDto $dto,
        ?Request $request = null
    ): mixed {
        $processor = new CompleteTwoFactorProcessor(
            $this->commandBus,
            $this->requestStack,
            new CompleteTwoFactorCommandFactory()
        );
        if ($request !== null) {
            return $processor->process($dto, $this->operation, [], ['request' => $request]);
        }
        return $processor->process($dto, $this->operation);
    }

    private function assertExpectedTokensInResponse(mixed $response): void
    {
        $this->assertJsonStringEqualsJsonString(
            json_encode([
                '2fa_enabled' => true,
                'access_token' => 'issued-access-token',
                'refresh_token' => 'issued-refresh-token',
            ]),
            (string) $response->getContent()
        );
    }

    /**
     * @param array<Cookie> $cookies
     */
    private function assertStandardAuthCookie(
        array $cookies,
        string $expectedValue,
        int $minAge,
        int $maxAge
    ): void {
        $this->assertCount(1, $cookies);
        $cookie = $cookies[0];
        $this->assertSame('__Host-auth_token', $cookie->getName());
        $this->assertSame($expectedValue, $cookie->getValue());
        $this->assertSame('/', $cookie->getPath());
        $this->assertNull($cookie->getDomain());
        $this->assertTrue($cookie->isSecure());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame(Cookie::SAMESITE_LAX, $cookie->getSameSite());
        $this->assertGreaterThanOrEqual($minAge, $cookie->getMaxAge());
        $this->assertLessThanOrEqual($maxAge, $cookie->getMaxAge());
    }
}
