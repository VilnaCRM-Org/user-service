<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Passkey;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Infrastructure\Factory\UuidFactory as SharedUuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\User\Application\DTO\PasskeyAuthenticationResult;
use App\User\Application\DTO\PasskeyOptionsResult;
use App\User\Application\Factory\EventIdFactoryInterface;
use App\User\Application\Factory\IdFactoryInterface;
use App\User\Application\Passkey\PasskeyChallengeStore;
use App\User\Application\Passkey\PasskeyConfiguration;
use App\User\Application\Passkey\PasskeyCredentialStore;
use App\User\Application\Passkey\PasskeyCredentialVerifierInterface;
use App\User\Application\Passkey\PasskeyEncoding;
use App\User\Application\Passkey\PasskeyJsonCodec;
use App\User\Application\Passkey\PasskeyOptionsFactory;
use App\User\Application\Passkey\PasskeyPublicKeyOptionsFactory;
use App\User\Application\Passkey\PasskeyRegistrationService;
use App\User\Application\Passkey\PasskeySessionIssuer;
use App\User\Application\Passkey\PasskeyUserCreator;
use App\User\Application\Passkey\PasskeyUserResolver;
use App\User\Application\Passkey\PasskeyWebauthnFactory;
use App\User\Domain\Contract\PasswordHasherInterface;
use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\Entity\PasskeyCredential;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\Event\UserRegisteredEventFactory;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Repository\PasskeyChallengeRepositoryInterface;
use App\User\Domain\Repository\PasskeyCredentialRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use PHPUnit\Framework\Assert;
use Symfony\Component\Uid\Factory\UuidFactory as SymfonyUuidFactory;

final readonly class PasskeyRegistrationServiceTestSupport
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasskeyCredentialRepositoryInterface $credentialRepository,
        private PasskeyChallengeRepositoryInterface $challengeRepository,
        private PasskeyCredentialVerifierInterface $credentialVerifier,
        private PasskeySessionIssuer $sessionIssuer,
        private PasswordHasherInterface $passwordHasher,
        private IdFactoryInterface $idFactory,
        private EventBusInterface $eventBus,
        private EventIdFactoryInterface $eventIdFactory
    ) {
    }

    public function createService(): PasskeyRegistrationService
    {
        return new PasskeyRegistrationService(
            new PasskeyUserResolver($this->userRepository),
            new PasskeyCredentialStore($this->credentialRepository, $this->idFactory),
            new PasskeyChallengeStore($this->challengeRepository),
            $this->createOptionsFactory(),
            $this->credentialVerifier,
            $this->sessionIssuer,
            $this->createUserCreator(),
            new SymfonyUuidFactory()
        );
    }

    public function createPasskeyCredential(User $user): PasskeyCredential
    {
        return new PasskeyCredential(
            'passkey-id',
            $user->getId(),
            (new PasskeyEncoding())->encode('raw-credential-id'),
            '{}',
            'Laptop',
            new DateTimeImmutable()
        );
    }

    public function assertRegistrationOptionsStarted(PasskeyOptionsResult $result): void
    {
        Assert::assertSame('challenge-id', $result->getChallenge()->getId());
        Assert::assertSame(
            'public-key',
            $result->getPublicKeyOptions()['excludeCredentials'][0]['type']
        );
    }

    /**
     * @param array<string, scalar|array|null> $credentialPayload
     */
    public function completeSignup(array $credentialPayload): PasskeyAuthenticationResult
    {
        return $this->createService()->completeSignup(
            'challenge-id',
            $credentialPayload,
            'Work laptop',
            true,
            '203.0.113.10',
            'Test Browser'
        );
    }

    public function assertSignupCompleted(
        PasskeyAuthenticationResult $result,
        PasskeyChallenge $challenge
    ): void {
        Assert::assertSame('access-token', $result->getAccessToken());
        Assert::assertSame('refresh-token', $result->getRefreshToken());
        Assert::assertTrue($result->isRememberMe());
        Assert::assertTrue($challenge->isConsumed());
    }

    private function createOptionsFactory(): PasskeyOptionsFactory
    {
        $configuration = $this->createConfiguration();

        return new PasskeyOptionsFactory(
            $configuration,
            new PasskeyJsonCodec(new PasskeyWebauthnFactory($configuration)),
            new PasskeyEncoding(),
            new PasskeyPublicKeyOptionsFactory($configuration, new PasskeyEncoding()),
            $this->challengeRepository,
            $this->idFactory
        );
    }

    private function createConfiguration(): PasskeyConfiguration
    {
        return new PasskeyConfiguration(
            'localhost',
            'VilnaCRM User Service',
            'https://localhost',
            300,
            300
        );
    }

    private function createUserCreator(): PasskeyUserCreator
    {
        return new PasskeyUserCreator(
            $this->userRepository,
            $this->passwordHasher,
            new UserFactory(),
            new UuidTransformer(new SharedUuidFactory()),
            $this->eventBus,
            $this->eventIdFactory,
            new UserRegisteredEventFactory()
        );
    }
}
