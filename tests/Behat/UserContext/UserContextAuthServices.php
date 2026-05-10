<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\Tests\Shared\Auth\Factory\TestAccessTokenFactory;
use App\User\Application\Factory\AccessTokenFactoryInterface;
use App\User\Application\Provider\AccountLockoutProviderInterface;
use App\User\Domain\Contract\TwoFactorSecretEncryptorInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Uid\Factory\UlidFactory;

/**
 * @psalm-api
 */
final class UserContextAuthServices
{
    public function __construct(
        public readonly AccountLockoutProviderInterface $accountLockoutGuard,
        public readonly TokenStorageInterface $tokenStorage,
        public readonly TestAccessTokenFactory $testAccessTokenFactory,
        public readonly AccessTokenFactoryInterface $accessTokenFactory,
        public readonly AuthSessionRepositoryInterface $authSessionRepository,
        public readonly UlidFactory $ulidFactory,
        public readonly TwoFactorSecretEncryptorInterface $twoFactorSecretEncryptor,
    ) {
    }
}
