<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\User\Application\Command\CompletePasskeyRegistrationCommand;
use App\User\Application\Factory\PasskeyCredentialFactory;
use App\User\Application\Resolver\PasskeyChallengeResolver;
use App\User\Application\Resolver\PasskeyCredentialResolver;
use App\User\Application\Validator\PasskeyCredentialValidatorInterface;
use DateTimeImmutable;

final readonly class CompletePasskeyRegistrationCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private PasskeyChallengeResolver $challengeResolver,
        private PasskeyCredentialValidatorInterface $credentialValidator,
        private PasskeyCredentialFactory $credentialFactory,
        private PasskeyCredentialResolver $credentialResolver
    ) {
    }

    public function __invoke(CompletePasskeyRegistrationCommand $command): void
    {
        $challenge = $this->challengeResolver->resolveRegistration($command->challengeId);
        $this->challengeResolver->assertBelongsToUser($challenge, $command->currentUserId);

        $credential = $this->credentialFactory->create(
            $command->currentUserId,
            $this->credentialValidator->verifyAttestation($challenge, $command->credential),
            $command->label,
            new DateTimeImmutable()
        );
        $this->credentialResolver->saveUnique($credential);
        $this->challengeResolver->delete($challenge);

        $command->setResponse($credential);
    }
}
