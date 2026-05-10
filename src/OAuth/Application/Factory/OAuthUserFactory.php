<?php

declare(strict_types=1);

namespace App\OAuth\Application\Factory;

use App\OAuth\Domain\ValueObject\OAuthUserProfile;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\User\Application\Factory\EventIdFactoryInterface;
use App\User\Domain\Contract\PasswordHasherInterface;
use App\User\Domain\Entity\User;

final readonly class OAuthUserFactory
{
    public function __construct(
        private PasswordHasherInterface $passwordHasher,
        private EventIdFactoryInterface $eventIdFactory,
        private UuidTransformer $uuidTransformer,
    ) {
    }

    public function create(OAuthUserProfile $profile): User
    {
        $user = new User(
            $profile->email,
            $this->deriveInitials($profile->name, $profile->email),
            $this->passwordHasher->hash(bin2hex(random_bytes(32))),
            $this->uuidTransformer->transformFromString(
                $this->eventIdFactory->generate()
            ),
        );

        if ($profile->emailVerified) {
            $user->setConfirmed(true);
        }

        return $user;
    }

    private function deriveInitials(string $name, string $email): string
    {
        if (trim($name) !== '') {
            return mb_substr(trim($name), 0, 2);
        }

        $localPart = strstr($email, '@', true);

        return mb_substr($localPart !== false ? $localPart : $email, 0, 2);
    }
}
