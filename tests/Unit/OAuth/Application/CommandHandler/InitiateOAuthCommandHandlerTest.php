<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Application\CommandHandler;

use App\OAuth\Application\Collection\OAuthProviderCollection;
use App\OAuth\Application\Command\InitiateOAuthCommand;
use App\OAuth\Application\CommandHandler\InitiateOAuthCommandHandler;
use App\OAuth\Application\Provider\OAuthProviderInterface;
use App\OAuth\Application\Provider\OAuthProviderRegistry;
use App\OAuth\Domain\Repository\OAuthStateRepositoryInterface;
use App\OAuth\Domain\ValueObject\OAuthProvider;
use App\OAuth\Domain\ValueObject\OAuthStatePayload;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class InitiateOAuthCommandHandlerTest extends UnitTestCase
{
    private OAuthStateRepositoryInterface&MockObject $stateRepository;
    private OAuthProviderInterface&MockObject $oAuthProvider;
    private string $providerName;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->providerName = 'github';
        $this->stateRepository = $this->createMock(OAuthStateRepositoryInterface::class);
        $this->oAuthProvider = $this->createMock(OAuthProviderInterface::class);
        $this->oAuthProvider->method('getProvider')
            ->willReturn(OAuthProvider::fromString($this->providerName));
    }

    public function testInvokeStoresStateAndReturnsResponse(): void
    {
        $redirectUri = $this->faker->url();
        $authUrl = $this->faker->url();
        $capturedPayload = null;

        $this->oAuthProvider->method('supportsPkce')->willReturn(true);
        $this->oAuthProvider->method('getAuthorizationUrl')
            ->willReturn($authUrl);
        $this->expectStateSavedForRedirect($redirectUri, $capturedPayload);

        $command = new InitiateOAuthCommand($this->providerName, $redirectUri);
        $this->createHandler()->__invoke($command);

        $this->assertInstanceOf(OAuthStatePayload::class, $capturedPayload);
        $this->assertAuthorizationResponse($command, $authUrl, $capturedPayload);
    }

    public function testInvokeWithPkceProviderGeneratesCodeChallenge(): void
    {
        $redirectUri = $this->faker->url();
        $authUrl = $this->faker->url();
        $capturedPayload = null;

        $this->oAuthProvider->method('supportsPkce')->willReturn(true);
        $this->expectPkceAuthorizationUrl($authUrl, $capturedPayload);
        $this->expectCapturedStatePayload($capturedPayload);

        $command = new InitiateOAuthCommand($this->providerName, $redirectUri);
        $this->createHandler()->__invoke($command);

        $this->assertInstanceOf(OAuthStatePayload::class, $capturedPayload);
    }

    public function testInvokeWithoutPkcePassesNullCodeChallenge(): void
    {
        $redirectUri = $this->faker->url();
        $authUrl = $this->faker->url();

        $this->oAuthProvider->method('supportsPkce')->willReturn(false);
        $this->oAuthProvider->expects($this->once())
            ->method('getAuthorizationUrl')
            ->with(
                $this->isType('string'),
                $this->isNull(),
            )
            ->willReturn($authUrl);

        $this->stateRepository->method('save');

        $command = new InitiateOAuthCommand($this->providerName, $redirectUri);
        $this->createHandler()->__invoke($command);
    }

    public function testInvokeStoresCorrectProviderInPayload(): void
    {
        $redirectUri = $this->faker->url();

        $this->oAuthProvider->method('supportsPkce')->willReturn(true);
        $this->oAuthProvider->method('getAuthorizationUrl')
            ->willReturn($this->faker->url());

        $this->stateRepository->expects($this->once())
            ->method('save')
            ->with(
                $this->isType('string'),
                $this->callback(function (OAuthStatePayload $payload): bool {
                    return $payload->provider === $this->providerName;
                }),
                $this->isType('int'),
            );

        $command = new InitiateOAuthCommand($this->providerName, $redirectUri);
        $this->createHandler()->__invoke($command);
    }

    private function createHandler(): InitiateOAuthCommandHandler
    {
        $registry = new OAuthProviderRegistry(
            new OAuthProviderCollection($this->oAuthProvider)
        );

        return new InitiateOAuthCommandHandler(
            $registry,
            $this->stateRepository,
        );
    }

    private function isHexTokenWithExpectedLength(string $token): bool
    {
        return strlen($token) === 64 && ctype_xdigit($token);
    }

    private function expectPkceAuthorizationUrl(
        string $authUrl,
        ?OAuthStatePayload &$capturedPayload,
    ): void {
        $this->oAuthProvider->expects($this->once())
            ->method('getAuthorizationUrl')
            ->with(
                $this->isType('string'),
                $this->callback(
                    function (?string $codeChallenge) use (&$capturedPayload): bool {
                        return $this->matchesPkceChallenge(
                            $codeChallenge,
                            $capturedPayload,
                        );
                    }
                ),
            )
            ->willReturn($authUrl);
    }

    private function expectCapturedStatePayload(
        ?OAuthStatePayload &$capturedPayload,
    ): void {
        $this->stateRepository->expects($this->once())
            ->method('save')
            ->with(
                $this->isType('string'),
                $this->callback(
                    static function (OAuthStatePayload $payload) use (
                        &$capturedPayload
                    ): bool {
                        $capturedPayload = $payload;

                        return true;
                    }
                ),
                $this->isType('int'),
            );
    }

    private function matchesPkceChallenge(
        ?string $codeChallenge,
        ?OAuthStatePayload $capturedPayload,
    ): bool {
        if (!is_string($codeChallenge) || $capturedPayload === null) {
            return false;
        }

        return $codeChallenge === rtrim(
            strtr(
                base64_encode(
                    hash(
                        'sha256',
                        $capturedPayload->codeVerifier,
                        true,
                    )
                ),
                '+/',
                '-_'
            ),
            '='
        )
            && !str_contains($codeChallenge, '=')
            && !str_contains($codeChallenge, '+')
            && !str_contains($codeChallenge, '/');
    }

    private function expectStateSavedForRedirect(
        string $redirectUri,
        ?OAuthStatePayload &$capturedPayload,
    ): void {
        $this->stateRepository->expects($this->once())
            ->method('save')
            ->with(
                $this->callback(
                    fn (string $state): bool => $this->isHexTokenWithExpectedLength($state)
                ),
                $this->callback(
                    function (OAuthStatePayload $payload) use (
                        $redirectUri,
                        &$capturedPayload,
                    ): bool {
                        $capturedPayload = $payload;

                        return $this->matchesStoredRedirectPayload(
                            $payload,
                            $redirectUri,
                        );
                    }
                ),
                $this->isType('int'),
            );
    }

    private function assertAuthorizationResponse(
        InitiateOAuthCommand $command,
        string $authUrl,
        OAuthStatePayload $capturedPayload,
    ): void {
        $response = $command->getResponse();

        $this->assertSame($authUrl, $response->authorizationUrl);
        $this->assertNotEmpty($response->state);
        $this->assertNotEmpty($response->flowBindingToken);
        $this->assertTrue(
            $this->isHexTokenWithExpectedLength($response->state)
        );
        $this->assertTrue(
            $this->isHexTokenWithExpectedLength($response->flowBindingToken)
        );
        $this->assertSame(
            hash('sha256', $response->flowBindingToken),
            $capturedPayload->flowBindingHash,
        );
    }

    private function matchesStoredRedirectPayload(
        OAuthStatePayload $payload,
        string $redirectUri,
    ): bool {
        return $payload->redirectUri === $redirectUri
            && $this->isHexTokenWithExpectedLength($payload->codeVerifier)
            && $this->isHexTokenWithExpectedLength($payload->flowBindingHash);
    }
}
