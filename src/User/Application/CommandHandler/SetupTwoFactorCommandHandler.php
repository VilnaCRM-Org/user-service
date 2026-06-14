<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\User\Application\Command\SetupTwoFactorCommand;
use App\User\Application\DTO\SetupTwoFactorCommandResponse;
use App\User\Application\Factory\TOTPSecretFactoryInterface;
use App\User\Application\Resolver\AuthenticatedUserResolver;
use App\User\Domain\Contract\TwoFactorSecretEncryptorInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final readonly class SetupTwoFactorCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private AuthenticatedUserResolver $authenticatedUserResolver,
        private TwoFactorSecretEncryptorInterface $twoFactorSecretEncryptor,
        private TOTPSecretFactoryInterface $totpSecretFactory,
    ) {
    }

    public function __invoke(
        SetupTwoFactorCommand $command
    ): SetupTwoFactorCommandResponse {
        $user = $this->resolveUser($command->userEmail);

        if ($user->isTwoFactorEnabled()) {
            throw new ConflictHttpException('Two-factor authentication is already enabled.');
        }

        $totpData = $this->totpSecretFactory->create($user->getEmail());
        $secret = $totpData['secret'];

        $user->setTwoFactorSecret(
            $this->twoFactorSecretEncryptor->encrypt($secret)
        );
        $this->userRepository->save($user);

        return new SetupTwoFactorCommandResponse(
            $totpData['otpauth_uri'],
            $secret
        );
    }

    private function resolveUser(string $email): User
    {
        return $this->authenticatedUserResolver->resolve($email);
    }
}
