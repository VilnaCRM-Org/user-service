<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\User\Application\Command\SetupTwoFactorCommand;
use App\User\Application\Command\SetupTwoFactorCommandResponse;
use App\User\Domain\Contract\TOTPSecretGeneratorInterface;
use App\User\Domain\Contract\TwoFactorSecretEncryptorInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
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

        $totpData = $this->totpSecretGenerator->generate($user->getEmail());
        $secret = $totpData['secret'];

        $user->setTwoFactorSecret(
            $this->twoFactorSecretEncryptor->encrypt($secret)
        );
        $user->setTwoFactorEnabled(false);
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
