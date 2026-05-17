<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Command\CompletePasskeySignUpCommand;
use App\User\Application\DTO\PasskeyAuthenticationResult;
use App\User\Application\Factory\PasskeyUserFactory;
use App\User\Application\Resolver\PasskeyChallengeResolver;
use App\User\Application\Resolver\PasskeyUserResolver;
use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\Entity\User;
use DateTimeImmutable;
use Throwable;

final readonly class PasskeySignUpCompletionHandler
{
    public function __construct(
        private PasskeyUserResolver $userResolver,
        private PasskeyUserFactory $userFactory,
        private EventBusInterface $eventBus,
        private PasskeyChallengeResolver $challengeResolver,
        private PasskeyAuthenticationIssuer $authenticationIssuer
    ) {
    }

    public function assertEmailIsAvailable(PasskeyChallenge $challenge): void
    {
        $this->userResolver->assertEmailIsAvailable((string) $challenge->getEmail());
    }

    public function createUser(PasskeyChallenge $challenge): User
    {
        return $this->userFactory->createFromSignupChallenge($challenge);
    }

    public function persistUserAndIssueAuthentication(
        User $user,
        PasskeyChallenge $challenge,
        CompletePasskeySignUpCommand $command,
        DateTimeImmutable $now
    ): PasskeyAuthenticationResult {
        $this->userResolver->save($user);

        try {
            $this->eventBus->publish($this->userFactory->createRegisteredEvent($user));
            $this->challengeResolver->delete($challenge);

            return $this->authenticationIssuer->issue(
                $user,
                $command->rememberMe,
                $command->ipAddress,
                $command->userAgent,
                $now
            );
        } catch (Throwable $exception) {
            $this->userResolver->delete($user);

            throw $exception;
        }
    }
}
