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
use App\User\Application\Resolver\HttpRequestContextResolverInterface;
use App\User\Application\Service\AuthCookieServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;

final class CompleteTwoFactorProcessorTest extends UnitTestCase
{
    private CommandBusInterface&MockObject $commandBus;
    private HttpRequestContextResolverInterface&MockObject $requestContextResolver;
    private AuthCookieServiceInterface&MockObject $cookieService;
    private Operation $operation;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->requestContextResolver = $this->createMock(
            HttpRequestContextResolverInterface::class
        );
        $this->cookieService = $this->createMock(AuthCookieServiceInterface::class);
        $this->operation = $this->createMock(Operation::class);
    }

    public function testProcessReturnsTokensAndAttachesCookie(): void
    {
        $data = $this->makeTokenScenarioData();
        [$ipAddress, $userAgent, $pendingSessionId, $totpCode, $accessToken, $refreshToken] = $data;
        $request = $this->createMock(Request::class);
        $this->stubResolver($request, $ipAddress, $userAgent);
        $dto = new CompleteTwoFactorDto($pendingSessionId, $totpCode);
        $this->expectDispatchValidatingMetadata(
            $pendingSessionId,
            $totpCode,
            $ipAddress,
            $userAgent,
            $accessToken,
            $refreshToken
        );
        $this->cookieService->expects($this->once())->method('attach')
            ->with($this->anything(), $accessToken, false);
        $response = $this->processDto($dto, $request);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertExpectedTokensInResponse($response, $accessToken, $refreshToken);
    }

    public function testProcessAttachesCookieWithRememberMe(): void
    {
        $request = $this->createMock(Request::class);
        $this->stubResolver($request, $this->faker->ipv4(), $this->faker->userAgent());
        $accessToken = $this->faker->sha256();
        $dto = new CompleteTwoFactorDto(
            $this->faker->lexify('??????????????????????????'),
            (string) $this->faker->numberBetween(100000, 999999)
        );
        $commandResponse = (new CompleteTwoFactorCommandResponse(
            $accessToken,
            $this->faker->sha256()
        ))->withRememberMe();
        $this->expectDispatchSetsResponse($commandResponse);
        $this->cookieService->expects($this->once())->method('attach')
            ->with($this->anything(), $accessToken, true);
        $response = $this->processDto($dto, $request);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testProcessAttachesCookieWithEmptyAccessToken(): void
    {
        $request = $this->createMock(Request::class);
        $this->stubResolver($request, $this->faker->ipv4(), $this->faker->userAgent());
        $dto = new CompleteTwoFactorDto(
            $this->faker->lexify('??????????????????????????'),
            (string) $this->faker->numberBetween(100000, 999999)
        );
        $this->expectDispatchSetsResponse(
            new CompleteTwoFactorCommandResponse(
                '',
                $this->faker->sha256()
            )
        );
        $this->cookieService->expects($this->once())->method('attach')
            ->with($this->anything(), '', false);
        $response = $this->processDto($dto, $request);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testProcessDelegatesContextRequestToResolver(): void
    {
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();
        $request = $this->createMock(Request::class);
        $this->requestContextResolver->expects($this->once())->method('resolveRequest')
            ->with($request)->willReturn($request);
        $this->stubResolverMetadata($request, $ipAddress, $userAgent);
        $dto = $this->makeRandomDto();
        $accessToken = $this->faker->sha256();
        $this->expectDispatchAssertingRequestMetadata(
            $ipAddress,
            $userAgent,
            new CompleteTwoFactorCommandResponse(
                $accessToken,
                $this->faker->sha256()
            )
        );
        $response = $this->processDto($dto, $request);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testProcessPassesNullToResolverWhenContextRequestIsMissing(): void
    {
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();
        $resolvedRequest = $this->createMock(Request::class);
        $this->requestContextResolver->expects($this->once())->method('resolveRequest')
            ->with(null)->willReturn($resolvedRequest);
        $this->stubResolverMetadata($resolvedRequest, $ipAddress, $userAgent);
        $dto = new CompleteTwoFactorDto(
            $this->faker->lexify('??????????????????????????'),
            (string) $this->faker->numberBetween(100000, 999999)
        );
        $this->expectDispatchAssertingRequestMetadata(
            $ipAddress,
            $userAgent,
            new CompleteTwoFactorCommandResponse(
                $this->faker->sha256(),
                $this->faker->sha256()
            )
        );
        $response = $this->processDto($dto);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testProcessIncludesRecoveryCodeWarningFieldsInResponse(): void
    {
        $request = $this->createMock(Request::class);
        $this->stubResolver($request, $this->faker->ipv4(), $this->faker->userAgent());
        $recoveryCode = $this->faker->regexify('[A-Z0-9]{4}-[A-Z0-9]{4}');
        $dto = new CompleteTwoFactorDto(
            $this->faker->lexify('??????????????????????????'),
            $recoveryCode
        );
        $remainingCodes = $this->faker->numberBetween(1, 3);
        $warningMessage = $this->faker->sentence();
        $this->expectDispatchSetsResponse(new CompleteTwoFactorCommandResponse(
            $this->faker->sha256(),
            $this->faker->sha256(),
            $remainingCodes,
            $warningMessage
        ));
        $response = $this->processDto($dto, $request);
        $body = json_decode((string) $response->getContent(), true);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($remainingCodes, $body['recovery_codes_remaining']);
        $this->assertSame($warningMessage, $body['warning']);
    }

    public function testProcessOmitsRecoveryCodeFieldsWhenNull(): void
    {
        $request = $this->createMock(Request::class);
        $this->stubResolver($request, $this->faker->ipv4(), $this->faker->userAgent());
        $dto = new CompleteTwoFactorDto(
            $this->faker->lexify('??????????????????????????'),
            (string) $this->faker->numberBetween(100000, 999999)
        );
        $this->expectDispatchSetsResponse(
            new CompleteTwoFactorCommandResponse(
                $this->faker->sha256(),
                $this->faker->sha256()
            )
        );
        $response = $this->processDto($dto, $request);
        $body = json_decode((string) $response->getContent(), true);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayNotHasKey('recovery_codes_remaining', $body);
        $this->assertArrayNotHasKey('warning', $body);
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

    private function expectDispatchValidatingMetadata(
        string $pendingSessionId,
        string $totpCode,
        string $ipAddress,
        string $userAgent,
        string $accessToken,
        string $refreshToken
    ): void {
        $response = new CompleteTwoFactorCommandResponse($accessToken, $refreshToken);
        $this->commandBus->expects($this->once())->method('dispatch')
            ->with($this->callback(
                function (CompleteTwoFactorCommand $cmd) use (
                    $pendingSessionId,
                    $totpCode,
                    $ipAddress,
                    $userAgent,
                    $response
                ): bool {
                    $this->assertSame($pendingSessionId, $cmd->pendingSessionId);
                    $this->assertSame($totpCode, $cmd->twoFactorCode);
                    $this->assertSame($ipAddress, $cmd->ipAddress);
                    $this->assertSame($userAgent, $cmd->userAgent);
                    $cmd->setResponse($response);
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
            new CompleteTwoFactorCommandFactory(),
            $this->requestContextResolver,
            $this->cookieService,
        );
        if ($request !== null) {
            return $processor->process($dto, $this->operation, [], ['request' => $request]);
        }
        return $processor->process($dto, $this->operation);
    }

    private function assertExpectedTokensInResponse(
        mixed $response,
        string $accessToken,
        string $refreshToken
    ): void {
        $this->assertJsonStringEqualsJsonString(
            json_encode([
                '2fa_enabled' => true,
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
            ]),
            (string) $response->getContent()
        );
    }

    /**
     * @return array{string, string, string, string, string, string}
     */
    private function makeTokenScenarioData(): array
    {
        return [
            $this->faker->ipv4(),
            $this->faker->userAgent(),
            $this->faker->lexify('??????????????????????????'),
            (string) $this->faker->numberBetween(100000, 999999),
            $this->faker->sha256(),
            $this->faker->sha256(),
        ];
    }

    private function makeRandomDto(): CompleteTwoFactorDto
    {
        return new CompleteTwoFactorDto(
            $this->faker->lexify('??????????????????????????'),
            (string) $this->faker->numberBetween(100000, 999999)
        );
    }
}
