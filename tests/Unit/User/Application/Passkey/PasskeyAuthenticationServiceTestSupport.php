<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Passkey;

use App\User\Application\DTO\PasskeyAuthenticationResult;
use App\User\Application\Factory\IdFactoryInterface;
use App\User\Application\Factory\IssuedSessionFactoryInterface;
use App\User\Application\Passkey\PasskeyAuthenticationService;
use App\User\Application\Passkey\PasskeyChallengeStore;
use App\User\Application\Passkey\PasskeyConfiguration;
use App\User\Application\Passkey\PasskeyCredentialStore;
use App\User\Application\Passkey\PasskeyCredentialVerifierInterface;
use App\User\Application\Passkey\PasskeyEncoding;
use App\User\Application\Passkey\PasskeyJsonCodec;
use App\User\Application\Passkey\PasskeyOptionsFactory;
use App\User\Application\Passkey\PasskeyPublicKeyOptionsFactory;
use App\User\Application\Passkey\PasskeySessionIssuer;
use App\User\Application\Passkey\PasskeyUserResolver;
use App\User\Application\Passkey\PasskeyWebauthnFactory;
use App\User\Domain\Repository\PasskeyChallengeRepositoryInterface;
use App\User\Domain\Repository\PasskeyCredentialRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Publisher\SignInPublisherInterface;

final readonly class PasskeyAuthenticationServiceTestSupport
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasskeyCredentialRepositoryInterface $credentialRepository,
        private PasskeyChallengeRepositoryInterface $challengeRepository,
        private IdFactoryInterface $idFactory,
        private PasskeyCredentialVerifierInterface $credentialVerifier,
        private IssuedSessionFactoryInterface $sessionFactory,
        private SignInPublisherInterface $signInPublisher
    ) {
    }

    public function createService(): PasskeyAuthenticationService
    {
        return new PasskeyAuthenticationService(
            new PasskeyUserResolver($this->userRepository),
            new PasskeyCredentialStore($this->credentialRepository, $this->idFactory),
            new PasskeyChallengeStore($this->challengeRepository),
            $this->createOptionsFactory(),
            $this->credentialVerifier,
            new PasskeySessionIssuer($this->sessionFactory, $this->signInPublisher)
        );
    }

    /**
     * @param array<string, scalar|array|null> $credentialPayload
     */
    public function complete(array $credentialPayload): PasskeyAuthenticationResult
    {
        return $this->createService()->complete(
            'challenge-id',
            $credentialPayload,
            '203.0.113.10',
            'Test Browser'
        );
    }

    private function createOptionsFactory(): PasskeyOptionsFactory
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
            new PasskeyEncoding(),
            new PasskeyPublicKeyOptionsFactory($configuration, new PasskeyEncoding()),
            $this->challengeRepository,
            $this->idFactory
        );
    }
}
