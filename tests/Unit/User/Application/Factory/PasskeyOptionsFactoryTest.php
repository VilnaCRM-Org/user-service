<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Factory;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\PasskeyConfiguration;
use App\User\Application\DTO\PasskeyOptionsResult;
use App\User\Application\Factory\IdFactoryInterface;
use App\User\Application\Factory\PasskeyOptionsFactory;
use App\User\Application\Factory\PasskeyPublicKeyOptionsFactory;
use App\User\Application\Factory\PasskeyWebauthnFactory;
use App\User\Application\Transformer\PasskeyEncodingTransformer;
use App\User\Application\Transformer\PasskeyJsonTransformer;
use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\Entity\PasskeyCredential;
use App\User\Domain\Repository\PasskeyChallengeRepositoryInterface;
use function array_column;
use Cose\Algorithms;
use DateTimeImmutable;
use const JSON_THROW_ON_ERROR;
use PHPUnit\Framework\MockObject\MockObject;
use function strlen;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialRequestOptions;

final class PasskeyOptionsFactoryTest extends UnitTestCase
{
    private PasskeyChallengeRepositoryInterface&MockObject $challengeRepository;
    private IdFactoryInterface&MockObject $idFactory;
    private PasskeyEncodingTransformer $encoding;
    private string $email;
    private string $initials;
    private string $displayName;
    private string $userId;
    private string $challengeId;
    private string $registrationChallengeId;
    private string $rawCredentialId;
    private string $rpId;
    private string $rpName;
    private string $origin;
    private int $timeoutSeconds;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->challengeRepository = $this->createMock(PasskeyChallengeRepositoryInterface::class);
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->encoding = new PasskeyEncodingTransformer();
        $this->email = $this->faker->safeEmail();
        $this->initials = $this->faker->lexify('??');
        $this->displayName = $this->faker->name();
        $this->userId = $this->faker->uuid();
        $this->challengeId = $this->faker->uuid();
        $this->registrationChallengeId = $this->faker->uuid();
        $this->rawCredentialId = $this->faker->sha256();
        $this->rpId = $this->faker->domainName();
        $this->rpName = $this->faker->company();
        $this->origin = sprintf('https://%s', $this->rpId);
        $this->timeoutSeconds = $this->faker->numberBetween(60, 600);
    }

    public function testCreateSignupOptionsPersistsChallengeAndReturnsBrowserOptions(): void
    {
        $this->idFactory->method('create')->willReturn($this->challengeId);
        $this->expectSignupChallengeSaved();

        $result = $this->createFactory()->createSignupOptions(
            $this->email,
            $this->initials,
            $this->displayName,
            $this->userId
        );

        $this->assertSignupResult($result);
    }

    public function testCreateAuthenticationOptionsIncludesExistingCredentialDescriptors(): void
    {
        $this->idFactory->method('create')->willReturn($this->challengeId);
        $credentialId = $this->encoding->encode($this->rawCredentialId);
        $credential = $this->createCredential($credentialId);
        $secondCredentialId = $this->encoding->encode($this->faker->sha256());
        $secondCredential = $this->createCredential($secondCredentialId);

        $this->expectAuthenticationChallengeSaved();
        $result = $this->createFactory()->createAuthenticationOptions(
            $this->email,
            true,
            $this->userId,
            [$credential, $secondCredential]
        );
        $publicKey = $result->getPublicKeyOptions();

        $this->assertAuthenticationResult($result, $credentialId);
        self::assertSame($secondCredentialId, $publicKey['allowCredentials'][1]['id']);
        self::assertSame($this->rpId, $publicKey['rpId']);
        self::assertSame(
            PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            $publicKey['userVerification']
        );
    }

    public function testCreateRegistrationOptionsPersistsAuthenticatedUserChallenge(): void
    {
        $this->idFactory->method('create')->willReturn($this->registrationChallengeId);
        $credentialId = $this->encoding->encode($this->rawCredentialId);

        $this->expectRegistrationChallengeSaved();

        $result = $this->createFactory()->createRegistrationOptions(
            $this->email,
            $this->displayName,
            $this->userId,
            [$this->createCredential($credentialId)]
        );

        $this->assertRegistrationResult($result, $credentialId);
    }

    public function testCreateSignupOptionsFallsBackToInitialsForBlankDisplayName(): void
    {
        $this->idFactory->method('create')->willReturn($this->challengeId);
        $this->challengeRepository->expects($this->once())->method('save');

        $result = $this->createFactory()->createSignupOptions(
            $this->email,
            $this->initials,
            ' ',
            $this->userId
        );

        self::assertSame($this->initials, $result->getPublicKeyOptions()['user']['displayName']);
    }

    private function assertSignupResult(PasskeyOptionsResult $result): void
    {
        $publicKey = $result->getPublicKeyOptions();
        self::assertSame($this->challengeId, $result->getChallenge()->getId());
        $this->assertSignupPublicKeyOptions($publicKey);
    }

    /**
     * @param array<string, scalar|array> $publicKey
     */
    private function assertSignupPublicKeyOptions(array $publicKey): void
    {
        self::assertSame($this->rpName, $publicKey['rp']['name']);
        self::assertSame($this->rpId, $publicKey['rp']['id']);
        self::assertSame($this->email, $publicKey['user']['name']);
        self::assertSame($this->displayName, $publicKey['user']['displayName']);
        self::assertSame(
            AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            $publicKey['authenticatorSelection']['userVerification']
        );
        self::assertSame($this->timeoutSeconds * 1000, $publicKey['timeout']);
        self::assertNotEmpty($publicKey['challenge']);
        self::assertSame(
            [
                Algorithms::COSE_ALGORITHM_ES256,
                Algorithms::COSE_ALGORITHM_RS256,
            ],
            array_column($publicKey['pubKeyCredParams'], 'alg')
        );
    }

    private function expectRegistrationChallengeSaved(): void
    {
        $this->challengeRepository->expects($this->once())->method('save')
            ->with(self::callback(function (PasskeyChallenge $challenge): bool {
                self::assertSame($this->registrationChallengeId, $challenge->getId());
                self::assertSame(PasskeyChallenge::PURPOSE_REGISTRATION, $challenge->getPurpose());
                self::assertSame($this->email, $challenge->getEmail());
                self::assertSame($this->displayName, $challenge->getDisplayName());
                self::assertSame($this->userId, $challenge->getUserId());
                $this->assertChallengeHasThirtyTwoBytes($challenge);

                return true;
            }));
    }

    private function assertRegistrationResult(
        PasskeyOptionsResult $result,
        string $credentialId
    ): void {
        self::assertSame($this->registrationChallengeId, $result->getChallenge()->getId());
        self::assertSame(
            $credentialId,
            $result->getPublicKeyOptions()['excludeCredentials'][0]['id']
        );
        self::assertSame(
            AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            $result->getPublicKeyOptions()['authenticatorSelection']['userVerification']
        );
    }

    private function expectSignupChallengeSaved(): void
    {
        $this->challengeRepository
            ->expects($this->once())
            ->method('save')
            ->with(self::callback(function (PasskeyChallenge $challenge): bool {
                self::assertSame($this->challengeId, $challenge->getId());
                self::assertSame(PasskeyChallenge::PURPOSE_SIGNUP, $challenge->getPurpose());
                self::assertSame($this->email, $challenge->getEmail());
                self::assertSame($this->initials, $challenge->getInitials());
                self::assertSame($this->displayName, $challenge->getDisplayName());
                self::assertSame($this->userId, $challenge->getUserId());
                self::assertFalse($challenge->isRememberMe());
                $this->assertChallengeHasThirtyTwoBytes($challenge);

                return $challenge->getChallenge() !== '' && $challenge->getOptions() !== '';
            }));
    }

    private function expectAuthenticationChallengeSaved(): void
    {
        $this->challengeRepository->expects($this->once())->method('save')
            ->with(self::callback(function (PasskeyChallenge $challenge): bool {
                self::assertSame($this->challengeId, $challenge->getId());
                self::assertSame(
                    PasskeyChallenge::PURPOSE_AUTHENTICATION,
                    $challenge->getPurpose()
                );
                self::assertSame($this->email, $challenge->getEmail());
                self::assertSame($this->userId, $challenge->getUserId());
                self::assertTrue($challenge->isRememberMe());
                $this->assertChallengeHasThirtyTwoBytes($challenge);

                return true;
            }));
    }

    private function assertChallengeHasThirtyTwoBytes(PasskeyChallenge $challenge): void
    {
        self::assertSame(32, strlen($this->encoding->decode($challenge->getChallenge())));
    }

    private function createCredential(string $credentialId): PasskeyCredential
    {
        return new PasskeyCredential(
            $this->faker->uuid(),
            $this->userId,
            $credentialId,
            json_encode(['record' => $this->faker->boolean()], JSON_THROW_ON_ERROR),
            $this->faker->words(2, true),
            new DateTimeImmutable()
        );
    }

    private function assertAuthenticationResult(
        PasskeyOptionsResult $result,
        string $credentialId
    ): void {
        $publicKey = $result->getPublicKeyOptions();

        self::assertSame(
            PasskeyChallenge::PURPOSE_AUTHENTICATION,
            $result->getChallenge()->getPurpose()
        );
        self::assertTrue($result->getChallenge()->isRememberMe());
        self::assertSame($credentialId, $publicKey['allowCredentials'][0]['id']);
        self::assertSame('public-key', $publicKey['allowCredentials'][0]['type']);
    }

    private function createFactory(): PasskeyOptionsFactory
    {
        $configuration = new PasskeyConfiguration(
            $this->rpId,
            $this->rpName,
            $this->origin,
            $this->timeoutSeconds,
            300
        );

        return new PasskeyOptionsFactory(
            $configuration,
            new PasskeyJsonTransformer(new PasskeyWebauthnFactory($configuration)),
            $this->encoding,
            new PasskeyPublicKeyOptionsFactory($configuration, $this->encoding),
            $this->challengeRepository,
            $this->idFactory
        );
    }
}
