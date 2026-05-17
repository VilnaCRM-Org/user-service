<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\User\Application\Command\CompletePasskeySignUpCommand;
use App\User\Application\DTO\PasskeyAuthenticationResult;
use App\User\Application\Factory\PasskeyCredentialFactory;
use App\User\Application\Resolver\PasskeyChallengeResolver;
use App\User\Application\Resolver\PasskeyCredentialResolver;
use App\User\Application\Validator\PasskeyCredentialValidatorInterface;
use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\Entity\PasskeyCredential;
use App\User\Domain\Entity\User;
use DateTimeImmutable;

final readonly class CompletePasskeySignUpCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private PasskeyChallengeResolver $challengeResolver,
        private PasskeyCredentialValidatorInterface $credentialValidator,
        private PasskeyCredentialFactory $credentialFactory,
        private PasskeyCredentialResolver $credentialResolver,
        private PasskeySignUpCompletionHandler $completionHandler
    ) {
    }

    public function __invoke(CompletePasskeySignUpCommand $command): void
    {
        $challenge = $this->challengeResolver->resolveSignup($command->challengeId);
        $this->challengeResolver->assertSignupChallengeIsComplete($challenge);
        $this->completionHandler->assertEmailIsAvailable($challenge);

        $now = new DateTimeImmutable();
        $user = $this->completionHandler->createUser($challenge);
        $credential = $this->createCredential($challenge, $command, $user, $now);

        $command->setResponse($this->saveSignupAndIssueAuthentication(
            $user,
            $challenge,
            $credential,
            $command,
            $now
        ));
    }

    private function saveSignupAndIssueAuthentication(
        User $user,
        PasskeyChallenge $challenge,
        PasskeyCredential $credential,
        CompletePasskeySignUpCommand $command,
        DateTimeImmutable $now
    ): PasskeyAuthenticationResult {
        $result = null;

        $this->credentialResolver->saveUniqueAndRun(
            $credential,
            function () use ($user, $challenge, $command, $now, &$result): void {
                $result = $this->completionHandler->persistUserAndIssueAuthentication(
                    $user,
                    $challenge,
                    $command,
                    $now
                );
            },
            function () use ($challenge): void {
                $this->challengeResolver->release($challenge);
            }
        );

        assert($result instanceof PasskeyAuthenticationResult);
        return $result;
    }

    private function createCredential(
        PasskeyChallenge $challenge,
        CompletePasskeySignUpCommand $command,
        User $user,
        DateTimeImmutable $now
    ): PasskeyCredential {
        return $this->credentialFactory->create(
            $user->getId(),
            $this->credentialValidator->verifyAttestation($challenge, $command->credential),
            $command->label,
            $now
        );
    }
}
