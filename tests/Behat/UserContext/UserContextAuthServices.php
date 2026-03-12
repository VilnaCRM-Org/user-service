<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\Tests\Shared\Auth\Factory\TestAccessTokenFactory;
use App\User\Application\Factory\Generator\AccessTokenGeneratorInterface;
use App\User\Application\Processor\Encryptor\TwoFactorSecretEncryptorInterface;
use App\User\Application\Processor\Lockout\AccountLockoutServiceInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Uid\Factory\UlidFactory;

/**
 * @psalm-api
 */
final class UserContextAuthServices
{
    public function __construct(
        public readonly AccountLockoutServiceInterface $accountLockoutService,
        public readonly TokenStorageInterface $tokenStorage,
        public readonly TestAccessTokenFactory $testAccessTokenFactory,
        public readonly AccessTokenGeneratorInterface $accessTokenGenerator,
        public readonly AuthSessionRepositoryInterface $authSessionRepository,
        public readonly UlidFactory $ulidFactory,
        public readonly TwoFactorSecretEncryptorInterface $twoFactorSecretEncryptor,
    ) {
    }
}
