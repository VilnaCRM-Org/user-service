<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\User\Application\Command\CompletePasskeySignInCommand;
use App\User\Application\Factory\PasskeyAuthenticationResultFactory;
use App\User\Application\Resolver\PasskeyChallengeResolver;
use App\User\Application\Resolver\PasskeyCredentialResolver;
use App\User\Application\Resolver\PasskeyUserResolver;
use App\User\Application\Validator\PasskeyCredentialValidatorInterface;
use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\Entity\PasskeyCredential;
use App\User\Domain\Repository\PasskeyChallengeRepositoryInterface;
use App\User\Domain\Repository\PasskeyCredentialRepositoryInterface;
use DateTimeImmutable;

final readonly class CompletePasskeySignInCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private PasskeyChallengeResolver $challengeResolver,
        private PasskeyCredentialResolver $credentialResolver,
        private PasskeyUserResolver $userResolver,
        private PasskeyCredentialValidatorInterface $credentialValidator,
        private PasskeyCredentialRepositoryInterface $credentialRepository,
        private PasskeyChallengeRepositoryInterface $challengeRepository,
        private PasskeyAuthenticationResultFactory $authenticationResultFactory
    ) {
    }

    public function __invoke(CompletePasskeySignInCommand $command): void
    {
        $challenge = $this->challengeResolver->resolveAuthentication($command->challengeId);
        $challengeUserId = $this->challengeResolver->requireUserId($challenge);
        $storedCredential = $this->resolveStoredCredential(
            $command->credential,
            $challengeUserId
        );
        $user = $this->userResolver->resolveCredentialOwner($challengeUserId);
        $now = $this->verifyAndMarkUsed($challenge, $command->credential, $storedCredential);

        $this->challengeRepository->delete($challenge);

        $command->setResponse($this->authenticationResultFactory->issue(
            $user,
            $challenge->isRememberMe(),
            $command->ipAddress,
            $command->userAgent,
            $now
        ));
    }

    /**
     * @param array<string, scalar|array|null> $credential
     */
    private function resolveStoredCredential(array $credential, string $userId): PasskeyCredential
    {
        $credentialId = $this->credentialValidator->extractCredentialId($credential);

        return $this->credentialResolver->resolveForUser($credentialId, $userId);
    }

    /**
     * @param array<string, scalar|array|null> $credential
     */
    private function verifyAndMarkUsed(
        PasskeyChallenge $challenge,
        array $credential,
        PasskeyCredential $storedCredential
    ): DateTimeImmutable {
        $verifiedCredential = $this->credentialValidator->verifyAssertion(
            $challenge,
            $credential,
            $storedCredential
        );
        $now = new DateTimeImmutable();

        $storedCredential->markUsed($verifiedCredential->getCredentialRecord(), $now);
        $this->credentialRepository->save($storedCredential);

        return $now;
    }
}
