<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Application\CommandHandler;

use App\OAuth\Application\Collection\OAuthProviderCollection;
use App\OAuth\Application\Command\InitiateOAuthCommand;
use App\OAuth\Application\CommandHandler\InitiateOAuthCommandHandler;
use App\OAuth\Application\DTO\InitiateOAuthResponse;
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

        $this->oAuthProvider->method('supportsPkce')->willReturn(true);
        $this->oAuthProvider->method('getAuthorizationUrl')
            ->willReturn($authUrl);

        $this->stateRepository->expects($this->once())
            ->method('save')
            ->with(
                $this->isType('string'),
                $this->isInstanceOf(OAuthStatePayload::class),
                $this->isType('int'),
            );

        $command = new InitiateOAuthCommand($this->providerName, $redirectUri);
        $this->createHandler()->__invoke($command);

        $response = $command->getResponse();
        $this->assertSame($authUrl, $response->authorizationUrl);
        $this->assertNotEmpty($response->state);
        $this->assertNotEmpty($response->flowBindingToken);
    }

    public function testInvokeWithPkceProviderGeneratesCodeChallenge(): void
    {
        $redirectUri = $this->faker->url();
        $authUrl = $this->faker->url();

        $this->oAuthProvider->method('supportsPkce')->willReturn(true);
        $this->oAuthProvider->expects($this->once())
            ->method('getAuthorizationUrl')
            ->with(
                $this->isType('string'),
                $this->logicalNot($this->isNull()),
            )
            ->willReturn($authUrl);

        $this->stateRepository->method('save');

        $command = new InitiateOAuthCommand($this->providerName, $redirectUri);
        $this->createHandler()->__invoke($command);
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

    public function testInvokeGeneratesExpectedTokenLengthsAndPkceChallenge(): void
    {
        $redirectUri = $this->faker->url();
        $capture = [];

        $this->arrangeInitiationCapture($capture);

        $command = new InitiateOAuthCommand($this->providerName, $redirectUri);
        $this->createHandler()->__invoke($command);

        $this->assertInitiationCapture($command->getResponse(), $capture, $redirectUri);
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

    /**
     * @param array<string, int|OAuthStatePayload|string|null> $capture
     */
    private function arrangeInitiationCapture(array &$capture): void
    {
        $this->oAuthProvider->method('supportsPkce')->willReturn(true);
        $this->arrangeAuthorizationUrlCapture($capture);
        $this->arrangeStateRepositoryCapture($capture);
    }

    /**
     * @param array<string, int|OAuthStatePayload|string|null> $capture
     */
    private function assertInitiationCapture(
        InitiateOAuthResponse $response,
        array $capture,
        string $redirectUri,
    ): void {
        $payload = $this->extractCapturedPayload($capture);

        $this->assertGeneratedResponseMetadata($response, $capture);
        $this->assertCapturedPayload($payload, $response, $redirectUri, $capture);
        $this->assertCapturedCodeChallenge($payload->codeVerifier, $capture);
    }

    /**
     * @param array<string, int|OAuthStatePayload|string|null> $capture
     */
    private function arrangeAuthorizationUrlCapture(array &$capture): void
    {
        $this->oAuthProvider->expects($this->once())
            ->method('getAuthorizationUrl')
            ->with(
                $this->captureState($capture, 'authorization_state'),
                $this->captureNonNullChallenge($capture),
            )
            ->willReturn($this->faker->url());
    }

    /**
     * @param array<string, int|OAuthStatePayload|string|null> $capture
     */
    private function arrangeStateRepositoryCapture(array &$capture): void
    {
        $this->stateRepository->expects($this->once())
            ->method('save')
            ->with(
                $this->captureState($capture, 'state'),
                $this->capturePayload($capture),
                $this->captureTtl($capture),
            );
    }

    /**
     * @param array<string, int|OAuthStatePayload|string|null> $capture
     */
    private function assertGeneratedResponseMetadata(
        InitiateOAuthResponse $response,
        array $capture,
    ): void {
        $this->assertSame(64, strlen($response->state));
        $this->assertSame(64, strlen($response->flowBindingToken));
        $this->assertSame($response->state, $capture['state']);
        $this->assertSame($response->state, $capture['authorization_state']);
        $this->assertSame(600, $capture['ttl']);
    }

    /**
     * @param array<string, int|OAuthStatePayload|string|null> $capture
     */
    private function assertCapturedPayload(
        OAuthStatePayload $payload,
        InitiateOAuthResponse $response,
        string $redirectUri,
        array $capture,
    ): void {
        $this->assertSame($this->providerName, $payload->provider);
        $this->assertSame($redirectUri, $payload->redirectUri);
        $this->assertSame(64, strlen($payload->codeVerifier));
        $this->assertSame(hash('sha256', $response->flowBindingToken), $payload->flowBindingHash);
        $this->assertSame($capture['payload'], $payload);
    }

    /**
     * @param array<string, int|OAuthStatePayload|string|null> $capture
     */
    private function assertCapturedCodeChallenge(
        string $codeVerifier,
        array $capture,
    ): void {
        $expectedCodeChallenge = rtrim(
            strtr(
                base64_encode(hash('sha256', $codeVerifier, true)),
                '+/',
                '-_'
            ),
            '='
        );

        $this->assertSame($expectedCodeChallenge, $capture['code_challenge']);
        $this->assertStringNotContainsString('=', (string) $capture['code_challenge']);
    }

    /**
     * @param array<string, int|OAuthStatePayload|string|null> $capture
     */
    private function extractCapturedPayload(array $capture): OAuthStatePayload
    {
        $this->assertInstanceOf(OAuthStatePayload::class, $capture['payload']);

        return $capture['payload'];
    }

    /**
     * @param array<string, int|OAuthStatePayload|string|null> $capture
     */
    private function captureState(
        array &$capture,
        string $key,
    ): \PHPUnit\Framework\Constraint\Callback {
        return $this->callback(
            static function (string $state) use (&$capture, $key): bool {
                $capture[$key] = $state;

                return true;
            }
        );
    }

    /**
     * @param array<string, int|OAuthStatePayload|string|null> $capture
     */
    private function captureNonNullChallenge(
        array &$capture,
    ): \PHPUnit\Framework\Constraint\Callback {
        return $this->callback(
            static function (?string $challenge) use (&$capture): bool {
                $capture['code_challenge'] = $challenge;

                return $challenge !== null;
            }
        );
    }

    /**
     * @param array<string, int|OAuthStatePayload|string|null> $capture
     */
    private function capturePayload(array &$capture): \PHPUnit\Framework\Constraint\Callback
    {
        return $this->callback(
            static function (OAuthStatePayload $payload) use (&$capture): bool {
                $capture['payload'] = $payload;

                return true;
            }
        );
    }

    /**
     * @param array<string, int|OAuthStatePayload|string|null> $capture
     */
    private function captureTtl(array &$capture): \PHPUnit\Framework\Constraint\Callback
    {
        return $this->callback(
            static function (int $ttl) use (&$capture): bool {
                $capture['ttl'] = $ttl;

                return true;
            }
        );
    }
}
