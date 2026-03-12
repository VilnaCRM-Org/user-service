<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\User\Application\Command\SetupTwoFactorCommand;
use App\User\Application\DTO\SetupTwoFactorCommandResponse;
use App\User\Application\Factory\Generator\TOTPSecretGeneratorInterface;
use App\User\Application\Processor\Encryptor\TwoFactorSecretEncryptorInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final readonly class SetupTwoFactorCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private TwoFactorSecretEncryptorInterface $twoFactorSecretEncryptor,
        private TOTPSecretGeneratorInterface $totpSecretGenerator,
    ) {
    }

    public function __invoke(SetupTwoFactorCommand $command): void
    {
        $user = $this->resolveUser($command->userEmail);

        if ($user->isTwoFactorEnabled()) {
            throw new ConflictHttpException('Two-factor authentication is already enabled.');
        }

        $totpData = $this->totpSecretGenerator->generate($user->getEmail());
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
