<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Command\CompletePasskeyRegistrationCommand;
use App\User\Application\Command\CompletePasskeySignUpCommand;
use App\User\Application\Command\StartPasskeyRegistrationCommand;
use App\User\Application\Command\StartPasskeySignUpCommand;
use App\User\Application\CommandHandler\CompletePasskeyRegistrationCommandHandler;
use App\User\Application\CommandHandler\CompletePasskeySignUpCommandHandler;
use App\User\Application\CommandHandler\StartPasskeyRegistrationCommandHandler;
use App\User\Application\CommandHandler\StartPasskeySignUpCommandHandler;
use App\User\Application\DTO\PasskeyAuthenticationResult;
use App\User\Application\DTO\PasskeyConfiguration;
use App\User\Application\DTO\PasskeyOptionsResult;
use App\User\Application\Factory\IdFactoryInterface;
use App\User\Application\Factory\PasskeyCredentialFactory;
use App\User\Application\Factory\PasskeyOptionsFactory;
use App\User\Application\Factory\PasskeyPublicKeyOptionsFactory;
use App\User\Application\Factory\PasskeyWebauthnFactory;
use App\User\Application\Resolver\PasskeyChallengeResolver;
use App\User\Application\Resolver\PasskeyCredentialResolver;
use App\User\Application\Resolver\PasskeyUserResolver;
use App\User\Application\Service\PasskeyAuthenticationIssuer;
use App\User\Application\Service\PasskeySignUpCompletionHandler;
use App\User\Application\Transformer\PasskeyEncodingTransformer;
use App\User\Application\Transformer\PasskeyJsonTransformer;
use App\User\Application\Validator\PasskeyCredentialValidatorInterface;
use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\Entity\PasskeyCredential;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\PasskeyChallengeRepositoryInterface;
use App\User\Domain\Repository\PasskeyCredentialRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Publisher\SignInPublisherInterface;
use PHPUnit\Framework\Assert;
use Psr\Log\NullLogger;
use Symfony\Component\Uid\Factory\UuidFactory as SymfonyUuidFactory;

final readonly class PasskeyRegistrationCommandHandlerTestSupport
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasskeyCredentialRepositoryInterface $credentialRepository,
        private PasskeyChallengeRepositoryInterface $challengeRepository,
        private PasskeyCredentialValidatorInterface $credentialValidator,
        private PasskeyRegistrationCommandHandlerFactories $factories,
        private IdFactoryInterface $idFactory,
        private EventBusInterface $eventBus,
        private SignInPublisherInterface $signInPublisher,
        private PasskeyCommandHandlerTestObjects $objects
    ) {
    }

    public function startSignup(
        string $email,
        string $initials,
        string $displayName
    ): PasskeyOptionsResult {
        $command = new StartPasskeySignUpCommand($email, $initials, $displayName);
        $this->createStartSignUpHandler()->__invoke($command);

        return $command->getResponse();
    }

    public function startRegistration(string $userId): PasskeyOptionsResult
    {
        $command = new StartPasskeyRegistrationCommand($userId);
        $this->createStartRegistrationHandler()->__invoke($command);

        return $command->getResponse();
    }

    /**
     * @param array<string, scalar|array|null> $credentialPayload
     */
    public function completeSignup(array $credentialPayload): PasskeyAuthenticationResult
    {
        $command = new CompletePasskeySignUpCommand(
            $this->objects->token('challengeId'),
            $credentialPayload,
            $this->objects->user('signupLabel'),
            true,
            $this->objects->user('ipAddress'),
            $this->objects->user('userAgent')
        );
        $this->createCompleteSignUpHandler()->__invoke($command);

        return $command->getResponse();
    }

    /**
     * @param array<string, scalar|array|null> $credentialPayload
     */
    public function completeRegistration(
        string $challengeId,
        array $credentialPayload,
        string $label,
        string $currentUserId
    ): PasskeyCredential {
        $command = new CompletePasskeyRegistrationCommand(
            $challengeId,
            $credentialPayload,
            $label,
            $currentUserId
        );
        $this->createCompleteRegistrationHandler()->__invoke($command);

        return $command->getResponse();
    }

    public function createPasskeyCredential(User $user): PasskeyCredential
    {
        return $this->objects->createCredential($user->getId());
    }

    public function assertRegistrationOptionsStarted(PasskeyOptionsResult $result): void
    {
        Assert::assertSame($this->objects->token('challengeId'), $result->getChallenge()->getId());
        Assert::assertSame(
            'public-key',
            $result->getPublicKeyOptions()['excludeCredentials'][0]['type']
        );
    }

    public function assertSignupCompleted(
        PasskeyAuthenticationResult $result,
        PasskeyChallenge $challenge
    ): void {
        Assert::assertSame($this->objects->token('accessToken'), $result->getAccessToken());
        Assert::assertSame($this->objects->token('refreshToken'), $result->getRefreshToken());
        Assert::assertTrue($result->isRememberMe());
        Assert::assertTrue($challenge->isConsumed());
    }

    private function createStartSignUpHandler(): StartPasskeySignUpCommandHandler
    {
        return new StartPasskeySignUpCommandHandler(
            $this->createOptionsFactory(),
            new SymfonyUuidFactory()
        );
    }

    private function createStartRegistrationHandler(): StartPasskeyRegistrationCommandHandler
    {
        return new StartPasskeyRegistrationCommandHandler(
            new PasskeyUserResolver($this->userRepository),
            new PasskeyCredentialResolver($this->credentialRepository),
            $this->createOptionsFactory()
        );
    }

    private function createCompleteSignUpHandler(): CompletePasskeySignUpCommandHandler
    {
        $challengeResolver = new PasskeyChallengeResolver($this->challengeRepository);
        $userResolver = new PasskeyUserResolver($this->userRepository);

        return new CompletePasskeySignUpCommandHandler(
            $challengeResolver,
            $this->credentialValidator,
            new PasskeyCredentialFactory($this->idFactory),
            new PasskeyCredentialResolver($this->credentialRepository),
            new PasskeySignUpCompletionHandler(
                $userResolver,
                $this->factories->userFactory,
                $this->eventBus,
                $challengeResolver,
                new PasskeyAuthenticationIssuer(
                    $this->factories->authenticationResultFactory,
                    $this->signInPublisher
                ),
                $this->factories->logger ?? new NullLogger()
            )
        );
    }

    private function createCompleteRegistrationHandler(): CompletePasskeyRegistrationCommandHandler
    {
        return new CompletePasskeyRegistrationCommandHandler(
            new PasskeyChallengeResolver($this->challengeRepository),
            $this->credentialValidator,
            new PasskeyCredentialFactory($this->idFactory),
            new PasskeyCredentialResolver($this->credentialRepository)
        );
    }

    private function createOptionsFactory(): PasskeyOptionsFactory
    {
        $configuration = $this->createConfiguration();

        return new PasskeyOptionsFactory(
            $configuration,
            new PasskeyJsonTransformer(new PasskeyWebauthnFactory($configuration)),
            new PasskeyEncodingTransformer(),
            new PasskeyPublicKeyOptionsFactory($configuration, new PasskeyEncodingTransformer()),
            $this->challengeRepository,
            $this->idFactory
        );
    }

    private function createConfiguration(): PasskeyConfiguration
    {
        return new PasskeyConfiguration(
            $this->objects->user('rpId'),
            $this->objects->user('rpName'),
            $this->objects->user('origin'),
            300,
            300
        );
    }
}
