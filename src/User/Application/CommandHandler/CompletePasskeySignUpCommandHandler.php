<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Command\CompletePasskeySignUpCommand;
use App\User\Application\Factory\PasskeyAuthenticationResultFactory;
use App\User\Application\Factory\PasskeyCredentialFactory;
use App\User\Application\Factory\PasskeyUserFactory;
use App\User\Application\Resolver\PasskeyChallengeResolver;
use App\User\Application\Resolver\PasskeyCredentialResolver;
use App\User\Application\Resolver\PasskeyUserResolver;
use App\User\Application\Validator\PasskeyCredentialValidatorInterface;
use DateTimeImmutable;

final readonly class CompletePasskeySignUpCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private PasskeyChallengeResolver $challengeResolver,
        private PasskeyUserResolver $userResolver,
        private PasskeyCredentialValidatorInterface $credentialValidator,
        private PasskeyCredentialFactory $credentialFactory,
        private PasskeyCredentialResolver $credentialResolver,
        private PasskeyAuthenticationResultFactory $authenticationResultFactory,
        private PasskeyUserFactory $userFactory,
        private EventBusInterface $eventBus
    ) {
    }

    public function __invoke(CompletePasskeySignUpCommand $command): void
    {
        $challenge = $this->challengeResolver->resolveSignup($command->challengeId);
        $this->challengeResolver->assertSignupChallengeIsComplete($challenge);
        $this->userResolver->assertEmailIsAvailable((string) $challenge->getEmail());

        $now = new DateTimeImmutable();
        $user = $this->userFactory->createFromSignupChallenge($challenge);
        $credential = $this->createCredential($challenge, $command, $user, $now);

        $this->saveUserAfterCredential($user, $challenge, $credential);
        $this->eventBus->publish($this->userFactory->createRegisteredEvent($user));

        $this->challengeResolver->delete($challenge);
        $command->setResponse($this->authenticationResultFactory->issue(
            $user,
            $command->rememberMe,
            $command->ipAddress,
            $command->userAgent,
            $now
        ));
    }

    /**
     * @param \App\User\Domain\Entity\User $user
     * @param \App\User\Domain\Entity\PasskeyChallenge $challenge
     * @param \App\User\Domain\Entity\PasskeyCredential $credential
     */
    private function saveUserAfterCredential(
        object $user,
        object $challenge,
        object $credential
    ): void {
        $this->credentialResolver->saveUniqueAndRun(
            $credential,
            function () use ($user): void {
                $this->userResolver->save($user);
            },
            function () use ($challenge): void {
                $this->challengeResolver->release($challenge);
            }
        );
    }

    /**
     * @param \App\User\Domain\Entity\PasskeyChallenge $challenge
     * @param \App\User\Domain\Entity\User $user
     */
    private function createCredential(
        object $challenge,
        CompletePasskeySignUpCommand $command,
        object $user,
        DateTimeImmutable $now
    ): \App\User\Domain\Entity\PasskeyCredential {
        return $this->credentialFactory->create(
            $user->getId(),
            $this->credentialValidator->verifyAttestation($challenge, $command->credential),
            $command->label,
            $now
        );
    }
}
