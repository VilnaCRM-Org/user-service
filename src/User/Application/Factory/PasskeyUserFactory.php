<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\User\Domain\Contract\PasswordHasherInterface;
use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\Entity\User;
use App\User\Domain\Event\UserRegisteredEvent;
use App\User\Domain\Factory\Event\UserRegisteredEventFactoryInterface;
use App\User\Domain\Factory\UserFactoryInterface;

use function bin2hex;
use function random_bytes;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final readonly class PasskeyUserFactory
{
    public function __construct(
        private PasswordHasherInterface $passwordHasher,
        private UserFactoryInterface $userFactory,
        private UuidTransformer $uuidTransformer,
        private EventIdFactoryInterface $eventIdFactory,
        private UserRegisteredEventFactoryInterface $registeredEventFactory
    ) {
    }

    public function createFromSignupChallenge(PasskeyChallenge $challenge): User
    {
        return $this->createUserFromChallenge($challenge);
    }

    public function createRegisteredEvent(User $user): UserRegisteredEvent
    {
        return $this->registeredEventFactory->create($user, $this->eventIdFactory->generate());
    }

    private function createUserFromChallenge(PasskeyChallenge $challenge): User
    {
        $user = $this->userFactory->create(
            (string) $challenge->getEmail(),
            (string) $challenge->getInitials(),
            $this->passwordHasher->hash(bin2hex(random_bytes(32))),
            $this->uuidTransformer->transformFromString((string) $challenge->getUserId())
        );

        if (!$user instanceof User) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid or expired passkey challenge.');
        }

        return $user;
    }
}
