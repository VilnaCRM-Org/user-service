<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\User\Application\Command\CompletePasskeySignInCommand;
use App\User\Application\Resolver\PasskeyChallengeResolver;
use App\User\Application\Resolver\PasskeyCredentialResolver;
use App\User\Application\Resolver\PasskeyUserResolver;
use App\User\Application\Service\PasskeyAuthenticationIssuer;
use App\User\Application\Service\PasskeyTwoFactorHandler;
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
        private PasskeyTwoFactorHandler $twoFactorHandler,
        private PasskeyAuthenticationIssuer $authenticationIssuer
    ) {
    }

    public function __invoke(CompletePasskeySignInCommand $command): void
    {
        $challenge = $this->challengeResolver->resolveAuthentication($command->challengeId);
        $storedCredential = $this->resolveStoredCredential(
            $command->credential,
            $challenge->getUserId()
        );
        $user = $this->userResolver->resolveCredentialOwner($storedCredential->getUserId());
        $now = $this->verifyAndMarkUsed($challenge, $command->credential, $storedCredential);
        $rememberMe = $challenge->isRememberMe();

        $this->challengeRepository->delete($challenge);

        if ($user->isTwoFactorEnabled()) {
            $command->setResponse($this->twoFactorHandler->handle($user, $rememberMe, $now));

            return;
        }

        $command->setResponse($this->authenticationIssuer->issue(
            $user,
            $rememberMe,
            $command->ipAddress,
            $command->userAgent,
            $now
        ));
    }

    /**
     * @param array<string, scalar|array|null> $credential
     */
    private function resolveStoredCredential(array $credential, ?string $userId): PasskeyCredential
    {
        $credentialId = $this->credentialValidator->extractCredentialId($credential);

        return $this->credentialResolver->resolveForOptionalUser($credentialId, $userId);
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
