<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\User\Domain\Factory\PasswordResetTokenFactoryInterface;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use App\User\Domain\Repository\TokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

final class UserContextUserManagementServices
{
    public function __construct(
        public readonly UserRepositoryInterface $userRepository,
        public readonly PasswordHasherFactoryInterface $hasherFactory,
        public readonly TokenRepositoryInterface $tokenRepository,
        public readonly UserFactoryInterface $userFactory,
        public readonly UuidTransformer $transformer,
        public readonly UuidFactory $uuidFactory,
        public readonly PasswordResetTokenRepositoryInterface $passwordResetTokenRepository,
        public readonly PasswordResetTokenFactoryInterface $passwordResetTokenFactory,
    ) {
    }
}
