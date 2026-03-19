<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\User\Application\Command\SetupTwoFactorCommand;
use App\User\Application\DTO\SetupTwoFactorCommandResponse;
use App\User\Application\Factory\TOTPSecretFactoryInterface;
use App\User\Application\TwoFactorSecretEncryptorInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final readonly class SetupTwoFactorCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private TwoFactorSecretEncryptorInterface $twoFactorSecretEncryptor,
        private TOTPSecretFactoryInterface $totpSecretFactory,
    ) {
    }

    public function __invoke(SetupTwoFactorCommand $command): void
    {
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

        $command->setResponse(
            new SetupTwoFactorCommandResponse(
                $totpData['otpauth_uri'],
                $secret
            )
        );
    }

    private function resolveUser(string $email): User
    {
        $user = $this->userRepository->findByEmail($email);
        if (!$user instanceof User) {
            throw new UnauthorizedHttpException('Bearer', 'Authentication required.');
        }

        return $user;
    }
}
