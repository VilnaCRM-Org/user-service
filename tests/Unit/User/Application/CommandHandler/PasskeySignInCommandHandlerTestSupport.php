<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\User\Application\Command\CompletePasskeySignInCommand;
use App\User\Application\Command\StartPasskeySignInCommand;
use App\User\Application\CommandHandler\CompletePasskeySignInCommandHandler;
use App\User\Application\CommandHandler\PasskeyAuthenticationIssuer;
use App\User\Application\CommandHandler\PasskeyTwoFactorHandler;
use App\User\Application\CommandHandler\StartPasskeySignInCommandHandler;
use App\User\Application\DTO\PasskeyAuthenticationResult;
use App\User\Application\DTO\PasskeyConfiguration;
use App\User\Application\DTO\PasskeyOptionsResult;
use App\User\Application\Factory\IdFactoryInterface;
use App\User\Application\Factory\IssuedSessionFactoryInterface;
use App\User\Application\Factory\PasskeyAuthenticationResultFactory;
use App\User\Application\Factory\PasskeyOptionsFactory;
use App\User\Application\Factory\PasskeyPublicKeyOptionsFactory;
use App\User\Application\Factory\PasskeyWebauthnFactory;
use App\User\Application\Resolver\PasskeyChallengeResolver;
use App\User\Application\Resolver\PasskeyCredentialResolver;
use App\User\Application\Resolver\PasskeyUserResolver;
use App\User\Application\Transformer\PasskeyEncodingTransformer;
use App\User\Application\Transformer\PasskeyJsonTransformer;
use App\User\Application\Validator\PasskeyCredentialValidatorInterface;
use App\User\Domain\Factory\PendingTwoFactorFactory;
use App\User\Domain\Repository\PasskeyChallengeRepositoryInterface;
use App\User\Domain\Repository\PasskeyCredentialRepositoryInterface;
use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Publisher\SignInPublisherInterface;

final readonly class PasskeySignInCommandHandlerTestSupport
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasskeyCredentialRepositoryInterface $credentialRepository,
        private PasskeyChallengeRepositoryInterface $challengeRepository,
        private IdFactoryInterface $idFactory,
        private PasskeyCredentialValidatorInterface $credentialValidator,
        private IssuedSessionFactoryInterface $sessionFactory,
        private PendingTwoFactorRepositoryInterface $pendingTwoFactorRepository,
        private SignInPublisherInterface $signInPublisher,
        private PasskeyCommandHandlerTestObjects $objects
    ) {
    }

    public function start(string $email, bool $rememberMe): PasskeyOptionsResult
    {
        $command = new StartPasskeySignInCommand($email, $rememberMe);
        $this->createStartHandler()->__invoke($command);

        return $command->getResponse();
    }

    /**
     * @param array<string, scalar|array|null> $credentialPayload
     */
    public function complete(array $credentialPayload): PasskeyAuthenticationResult
    {
        $command = new CompletePasskeySignInCommand(
            $this->objects->token('challengeId'),
            $credentialPayload,
            $this->objects->user('ipAddress'),
            $this->objects->user('userAgent')
        );
        $this->createCompleteHandler()->__invoke($command);

        return $command->getResponse();
    }

    private function createStartHandler(): StartPasskeySignInCommandHandler
    {
        return new StartPasskeySignInCommandHandler(
            new PasskeyUserResolver($this->userRepository),
            $this->createOptionsFactory()
        );
    }

    private function createCompleteHandler(): CompletePasskeySignInCommandHandler
    {
        return new CompletePasskeySignInCommandHandler(
            new PasskeyChallengeResolver($this->challengeRepository),
            new PasskeyCredentialResolver($this->credentialRepository),
            new PasskeyUserResolver($this->userRepository),
            $this->credentialValidator,
            $this->credentialRepository,
            $this->challengeRepository,
            new PasskeyTwoFactorHandler(
                $this->pendingTwoFactorRepository,
                new PendingTwoFactorFactory(),
                $this->idFactory
            ),
            new PasskeyAuthenticationIssuer(
                new PasskeyAuthenticationResultFactory($this->sessionFactory),
                $this->signInPublisher
            )
        );
    }

    private function createOptionsFactory(): PasskeyOptionsFactory
    {
        $configuration = new PasskeyConfiguration(
            $this->objects->user('rpId'),
            $this->objects->user('rpName'),
            $this->objects->user('origin'),
            300,
            300
        );

        return new PasskeyOptionsFactory(
            $configuration,
            new PasskeyJsonTransformer(new PasskeyWebauthnFactory($configuration)),
            new PasskeyEncodingTransformer(),
            new PasskeyPublicKeyOptionsFactory($configuration, new PasskeyEncodingTransformer()),
            $this->challengeRepository,
            $this->idFactory
        );
    }
}
