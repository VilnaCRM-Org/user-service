<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\CompleteTwoFactorCommand;
use App\User\Application\DTO\CompleteTwoFactorCommandResponse;
use App\User\Application\DTO\CompleteTwoFactorDto;
use App\User\Application\Factory\AuthCookieFactoryInterface;
use App\User\Application\Factory\CompleteTwoFactorCommandFactory;
use App\User\Application\Processor\CompleteTwoFactorProcessor;
use App\User\Application\Resolver\HttpRequestContextResolverInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;

final class CompleteTwoFactorProcessorTest extends AuthProcessorTestCase
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
        $data = $this->makeTokenScenarioData();
        [$ipAddress, $userAgent, $pendingSessionId, $totpCode, $accessToken, $refreshToken] = $data;
        $request = $this->createMock(Request::class);
        $this->stubRequestContextResolver(
            $this->requestContextResolver,
            $request,
            $ipAddress,
            $userAgent
        );
        $dto = new CompleteTwoFactorDto($pendingSessionId, $totpCode);
        $this->expectDispatchForTokenScenario($data);
        $this->cookieFactory->expects($this->once())->method('create')
            ->with($accessToken, false)
            ->willReturn(Cookie::create('__Host-auth_token', $accessToken));
        $response = $this->processDto($dto, $request);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertExpectedTokensInResponse($response, $accessToken, $refreshToken);
    }

    public function testProcessAttachesCookieWithRememberMe(): void
    {
        $request = $this->stubRandomRequestContext($this->requestContextResolver);
        $accessToken = $this->faker->sha256();
        $dto = $this->makeRandomDto();
        $commandResponse = (new CompleteTwoFactorCommandResponse(
            $accessToken,
            $this->faker->sha256()
        ))->withRememberMe();
        $this->expectDispatchSetsResponse($commandResponse);
        $this->cookieFactory->expects($this->once())->method('create')
            ->with($accessToken, true)
            ->willReturn(Cookie::create('__Host-auth_token', $accessToken));
        $response = $this->processDto($dto, $request);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testProcessDoesNotCreateCookieWhenAccessTokenIsEmpty(): void
    {
        $request = $this->stubRandomRequestContext($this->requestContextResolver);
        $dto = $this->makeRandomDto();
        $this->expectDispatchSetsResponse(
            new CompleteTwoFactorCommandResponse(
                '',
                $this->faker->sha256()
            )
        );
        $this->cookieFactory->expects($this->never())->method('create');
        $response = $this->processDto($dto, $request);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testProcessDelegatesContextRequestToResolver(): void
    {
        $request = $this->createMock(Request::class);
        [$ipAddress, $userAgent] = $this->expectResolvedRequestContext(
            $this->requestContextResolver,
            $request,
            $request
        );
        $dto = $this->makeRandomDto();
        $accessToken = $this->faker->sha256();
        $this->expectDispatchWithRequestMetadata(
            $this->commandBus,
            CompleteTwoFactorCommand::class,
            new CompleteTwoFactorCommandResponse(
                $accessToken,
                $this->faker->sha256()
            ),
            $ipAddress,
            $userAgent
        );
        $response = $this->processDto($dto, $request);
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
        $dto = $this->makeRandomDto();
        $this->expectDispatchWithRequestMetadata(
            $this->commandBus,
            CompleteTwoFactorCommand::class,
            new CompleteTwoFactorCommandResponse(
                $this->faker->sha256(),
                $this->faker->sha256()
            ),
            $ipAddress,
            $userAgent
        );
        $response = $this->processDto($dto);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testProcessIncludesRecoveryCodeWarningFieldsInResponse(): void
    {
        $request = $this->stubRandomRequestContext($this->requestContextResolver);
        $recoveryCode = $this->faker->regexify('[A-Z0-9]{4}-[A-Z0-9]{4}');
        $dto = $this->makeDtoWithCode($recoveryCode);
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
        $request = $this->stubRandomRequestContext($this->requestContextResolver);
        $dto = $this->makeRandomDto();
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
                ): bool {
                    $this->assertSame($pendingSessionId, $cmd->pendingSessionId);
                    $this->assertSame($totpCode, $cmd->twoFactorCode);
                    $this->assertSame($ipAddress, $cmd->ipAddress);
                    $this->assertSame($userAgent, $cmd->userAgent);
                    return true;
                }
            ))
            ->willReturn($response);
    }

    /**
     * @param array{string, string, string, string, string, string} $data
     */
    private function expectDispatchForTokenScenario(array $data): void
    {
        [$ipAddress, $userAgent, $pendingSessionId, $totpCode, $accessToken, $refreshToken] = $data;
        $this->expectDispatchValidatingMetadata(
            $pendingSessionId,
            $totpCode,
            $ipAddress,
            $userAgent,
            $accessToken,
            $refreshToken
        );
    }

    private function expectDispatchSetsResponse(
        CompleteTwoFactorCommandResponse $response
    ): void {
        $this->commandBus->expects($this->once())->method('dispatch')
            ->with($this->callback(
                static function (CompleteTwoFactorCommand $cmd): bool {
                    return $cmd->pendingSessionId !== '';
                }
            ))
            ->willReturn($response);
    }

    private function processDto(
        CompleteTwoFactorDto $dto,
        ?Request $request = null
    ): mixed {
        $processor = new CompleteTwoFactorProcessor(
            $this->commandBus,
            new CompleteTwoFactorCommandFactory(),
            $this->requestContextResolver,
            $this->cookieFactory,
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
        return $this->makeDtoWithCode(
            (string) $this->faker->numberBetween(100000, 999999)
        );
    }

    private function makeDtoWithCode(string $code): CompleteTwoFactorDto
    {
        return new CompleteTwoFactorDto(
            $this->faker->lexify('??????????????????????????'),
            $code
        );
    }
}
