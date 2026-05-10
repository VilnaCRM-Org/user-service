<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Passkey;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\PasskeyOptionsResult;
use App\User\Application\Factory\IdFactoryInterface;
use App\User\Application\Passkey\PasskeyConfiguration;
use App\User\Application\Passkey\PasskeyEncoding;
use App\User\Application\Passkey\PasskeyJsonCodec;
use App\User\Application\Passkey\PasskeyOptionsFactory;
use App\User\Application\Passkey\PasskeyPublicKeyOptionsFactory;
use App\User\Application\Passkey\PasskeyWebauthnFactory;
use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\Entity\PasskeyCredential;
use App\User\Domain\Repository\PasskeyChallengeRepositoryInterface;
use function array_column;
use Cose\Algorithms;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use function strlen;

use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialRequestOptions;

final class PasskeyOptionsFactoryTest extends UnitTestCase
{
    private PasskeyChallengeRepositoryInterface&MockObject $challengeRepository;
    private IdFactoryInterface&MockObject $idFactory;
    private PasskeyEncoding $encoding;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->challengeRepository = $this->createMock(PasskeyChallengeRepositoryInterface::class);
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->encoding = new PasskeyEncoding();
    }

    public function testCreateSignupOptionsPersistsChallengeAndReturnsBrowserOptions(): void
    {
        $this->idFactory->method('create')->willReturn('challenge-id');
        $this->expectSignupChallengeSaved();

        $result = $this->createFactory()->createSignupOptions(
            'person@example.com',
            'AB',
            'Ada Byron',
            'user-id'
        );

        $this->assertSignupResult($result);
    }

    public function testCreateAuthenticationOptionsIncludesExistingCredentialDescriptors(): void
    {
        $this->idFactory->method('create')->willReturn('challenge-id');
        $credentialId = $this->encoding->encode('raw-credential-id');
        $credential = $this->createCredential($credentialId);

        $this->expectAuthenticationChallengeSaved();
        $result = $this->createFactory()->createAuthenticationOptions(
            'person@example.com',
            true,
            'user-id',
            [$credential]
        );
        $publicKey = $result->getPublicKeyOptions();

        $this->assertAuthenticationResult($result, $credentialId);
        self::assertSame('localhost', $publicKey['rpId']);
        self::assertSame(
            PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            $publicKey['userVerification']
        );
    }

    public function testCreateRegistrationOptionsPersistsAuthenticatedUserChallenge(): void
    {
        $this->idFactory->method('create')->willReturn('registration-challenge-id');
        $credentialId = $this->encoding->encode('raw-credential-id');

        $this->expectRegistrationChallengeSaved();

        $result = $this->createFactory()->createRegistrationOptions(
            'person@example.com',
            'Person Example',
            'user-id',
            [$this->createCredential($credentialId)]
        );

        $this->assertRegistrationResult($result, $credentialId);
    }

    public function testCreateSignupOptionsFallsBackToInitialsForBlankDisplayName(): void
    {
        $this->idFactory->method('create')->willReturn('challenge-id');
        $this->challengeRepository->expects($this->once())->method('save');

        $result = $this->createFactory()->createSignupOptions(
            'person@example.com',
            'PE',
            ' ',
            'user-id'
        );

        self::assertSame('PE', $result->getPublicKeyOptions()['user']['displayName']);
    }

    private function assertSignupResult(PasskeyOptionsResult $result): void
    {
        $publicKey = $result->getPublicKeyOptions();
        self::assertSame('challenge-id', $result->getChallenge()->getId());
        $this->assertSignupPublicKeyOptions($publicKey);
    }

    /**
     * @param array<string, scalar|array> $publicKey
     */
    private function assertSignupPublicKeyOptions(array $publicKey): void
    {
        self::assertSame('VilnaCRM User Service', $publicKey['rp']['name']);
        self::assertSame('localhost', $publicKey['rp']['id']);
        self::assertSame('person@example.com', $publicKey['user']['name']);
        self::assertSame('Ada Byron', $publicKey['user']['displayName']);
        self::assertSame(
            AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            $publicKey['authenticatorSelection']['userVerification']
        );
        self::assertSame(300000, $publicKey['timeout']);
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
                self::assertSame('registration-challenge-id', $challenge->getId());
                self::assertSame(PasskeyChallenge::PURPOSE_REGISTRATION, $challenge->getPurpose());
                self::assertSame('person@example.com', $challenge->getEmail());
                self::assertSame('Person Example', $challenge->getDisplayName());
                self::assertSame('user-id', $challenge->getUserId());
                $this->assertChallengeHasThirtyTwoBytes($challenge);

                return true;
            }));
    }

    private function assertRegistrationResult(
        PasskeyOptionsResult $result,
        string $credentialId
    ): void {
        self::assertSame('registration-challenge-id', $result->getChallenge()->getId());
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
                self::assertSame('challenge-id', $challenge->getId());
                self::assertSame(PasskeyChallenge::PURPOSE_SIGNUP, $challenge->getPurpose());
                self::assertSame('person@example.com', $challenge->getEmail());
                self::assertSame('AB', $challenge->getInitials());
                self::assertSame('Ada Byron', $challenge->getDisplayName());
                self::assertSame('user-id', $challenge->getUserId());
                self::assertFalse($challenge->isRememberMe());
                $this->assertChallengeHasThirtyTwoBytes($challenge);

                return $challenge->getChallenge() !== '' && $challenge->getOptions() !== '';
            }));
    }

    private function expectAuthenticationChallengeSaved(): void
    {
        $this->challengeRepository->expects($this->once())->method('save')
            ->with(self::callback(function (PasskeyChallenge $challenge): bool {
                self::assertSame('challenge-id', $challenge->getId());
                self::assertSame(
                    PasskeyChallenge::PURPOSE_AUTHENTICATION,
                    $challenge->getPurpose()
                );
                self::assertSame('person@example.com', $challenge->getEmail());
                self::assertSame('user-id', $challenge->getUserId());
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
            'passkey-id',
            'user-id',
            $credentialId,
            '{}',
            'Laptop',
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
            'localhost',
            'VilnaCRM User Service',
            'https://localhost',
            300,
            300
        );

        return new PasskeyOptionsFactory(
            $configuration,
            new PasskeyJsonCodec(new PasskeyWebauthnFactory($configuration)),
            $this->encoding,
            new PasskeyPublicKeyOptionsFactory($configuration, $this->encoding),
            $this->challengeRepository,
            $this->idFactory
        );
    }
}
