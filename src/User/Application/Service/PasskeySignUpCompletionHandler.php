<?php

declare(strict_types=1);

namespace App\User\Application\Service;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\User\Application\Command\CompletePasskeySignUpCommand;
use App\User\Application\DTO\PasskeyAuthenticationResult;
use App\User\Application\Factory\PasskeyUserFactory;
use App\User\Application\Resolver\PasskeyChallengeResolver;
use App\User\Application\Resolver\PasskeyUserResolver;
use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\Entity\User;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class PasskeySignUpCompletionHandler
{
    public function __construct(
        private PasskeyUserResolver $userResolver,
        private PasskeyUserFactory $userFactory,
        private EventBusInterface $eventBus,
        private PasskeyChallengeResolver $challengeResolver,
        private PasskeyAuthenticationIssuer $authenticationIssuer,
        private LoggerInterface $logger
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

        $result = $this->authenticationIssuer->issue(
            $user,
            $command->rememberMe,
            $command->ipAddress,
            $command->userAgent,
            $now
        );

        $this->deleteChallenge($challenge);
        $this->publishRegisteredEvent($user);

        return $result;
    }

    private function deleteChallenge(PasskeyChallenge $challenge): void
    {
        try {
            $this->challengeResolver->delete($challenge);
        } catch (Throwable $exception) {
            $this->logger->warning('Passkey signup challenge cleanup failed.', [
                'challenge_id' => $challenge->getId(),
                'exception' => $exception,
            ]);
        }
    }

    private function publishRegisteredEvent(User $user): void
    {
        try {
            $this->eventBus->publish($this->userFactory->createRegisteredEvent($user));
        } catch (Throwable $exception) {
            $this->logger->warning('Passkey signup registered event dispatch failed.', [
                'exception' => $exception,
                'user_id' => $user->getId(),
            ]);
        }
    }
}
