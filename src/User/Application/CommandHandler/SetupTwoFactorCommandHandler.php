<?php

declare(strict_types=1);

namespace App\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\User\Application\Command\SetupTwoFactorCommand;
use App\User\Application\Command\SetupTwoFactorCommandResponse;
use App\User\Domain\Contract\TwoFactorSecretEncryptorInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use OTPHP\TOTP;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final readonly class SetupTwoFactorCommandHandler implements CommandHandlerInterface
{
    private const OTP_ISSUER = 'VilnaCRM';

    public function __construct(
        private UserRepositoryInterface $userRepository,
        private TwoFactorSecretEncryptorInterface $twoFactorSecretEncryptor
    ) {
    }

    public function __invoke(SetupTwoFactorCommand $command): void
    {
        $user = $this->resolveUser($command->userEmail);

        $totp = TOTP::create();
        $totp->setIssuer(self::OTP_ISSUER);
        $totp->setLabel($user->getEmail());

        $secret = $totp->getSecret();

        $user->setTwoFactorSecret(
            $this->twoFactorSecretEncryptor->encrypt($secret)
        );
        $user->setTwoFactorEnabled(false);
        $this->userRepository->save($user);

        $command->setResponse(
            new SetupTwoFactorCommandResponse(
                $totp->getProvisioningUri(),
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
