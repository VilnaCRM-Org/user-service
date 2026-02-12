<?php

declare(strict_types=1);

namespace App\Tests\Integration\User\Application\CommandHandler;

use App\Shared\Domain\Factory\UuidFactoryInterface;
use App\Tests\Integration\IntegrationTestCase;
use App\User\Application\Command\UpdateUserCommand;
use App\User\Application\CommandHandler\UpdateUserCommandHandler;
use App\User\Domain\Entity\AuthRefreshToken;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Domain\ValueObject\UserUpdate;
use DateTimeImmutable;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Uid\Factory\UlidFactory;

final class UpdateUserCommandHandlerIntegrationTest extends IntegrationTestCase
{
    private UserFactoryInterface $userFactory;
    private UserRepositoryInterface $userRepository;
    private AuthSessionRepositoryInterface $authSessionRepository;
    private AuthRefreshTokenRepositoryInterface $authRefreshTokenRepository;
    private PasswordHasherFactoryInterface $hasherFactory;
    private UuidFactoryInterface $uuidFactory;
    private UlidFactory $ulidFactory;
    private UpdateUserCommandHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = $this->container->get(UserFactoryInterface::class);
        $this->userRepository = $this->container->get(UserRepositoryInterface::class);
        $this->authSessionRepository = $this->container->get(AuthSessionRepositoryInterface::class);
        $this->authRefreshTokenRepository = $this->container->get(AuthRefreshTokenRepositoryInterface::class);
        $this->hasherFactory = $this->container->get(PasswordHasherFactoryInterface::class);
        $this->uuidFactory = $this->container->get(UuidFactoryInterface::class);
        $this->ulidFactory = $this->container->get(UlidFactory::class);
        $this->handler = $this->container->get(UpdateUserCommandHandler::class);
    }

    public function testPasswordChangeRevokesAllOtherSessionsAndTheirRefreshTokens(): void
    {
        $oldPassword = $this->faker->password();
        $newPassword = $this->faker->password();
        $user = $this->createUserWithHashedPassword($oldPassword);

        $currentSessionId = (string) $this->ulidFactory->create();
        $otherSessionOneId = (string) $this->ulidFactory->create();
        $otherSessionTwoId = (string) $this->ulidFactory->create();

        $this->authSessionRepository->save(
            $this->createSession($currentSessionId, $user->getId())
        );
        $this->authSessionRepository->save(
            $this->createSession($otherSessionOneId, $user->getId())
        );
        $this->authSessionRepository->save(
            $this->createSession($otherSessionTwoId, $user->getId())
        );

        $this->authRefreshTokenRepository->save(
            $this->createRefreshToken($currentSessionId)
        );
        $this->authRefreshTokenRepository->save(
            $this->createRefreshToken($otherSessionOneId)
        );
        $this->authRefreshTokenRepository->save(
            $this->createRefreshToken($otherSessionTwoId)
        );

        $command = new UpdateUserCommand(
            $user,
            new UserUpdate(
                $user->getEmail(),
                strtoupper($this->faker->lexify('????')),
                $newPassword,
                $oldPassword
            ),
            $currentSessionId
        );

        $this->handler->__invoke($command);

        $currentSession = $this->authSessionRepository->findById($currentSessionId);
        $otherSessionOne = $this->authSessionRepository->findById($otherSessionOneId);
        $otherSessionTwo = $this->authSessionRepository->findById($otherSessionTwoId);

        $this->assertNotNull($currentSession);
        $this->assertNotNull($otherSessionOne);
        $this->assertNotNull($otherSessionTwo);
        $this->assertFalse($currentSession->isRevoked());
        $this->assertTrue($otherSessionOne->isRevoked());
        $this->assertTrue($otherSessionTwo->isRevoked());

        $this->assertTokensNotRevokedForSession($currentSessionId);
        $this->assertTokensRevokedForSession($otherSessionOneId);
        $this->assertTokensRevokedForSession($otherSessionTwoId);
    }

    private function createUserWithHashedPassword(string $plainPassword): User
    {
        $hashedPassword = $this->hasherFactory
            ->getPasswordHasher(User::class)
            ->hash($plainPassword);

        $user = $this->userFactory->create(
            $this->faker->email(),
            strtoupper($this->faker->lexify('????')),
            $hashedPassword,
            $this->uuidFactory->create($this->faker->uuid())
        );
        $this->userRepository->save($user);

        return $user;
    }

    private function createSession(string $sessionId, string $userId): AuthSession
    {
        return new AuthSession(
            $sessionId,
            $userId,
            $this->faker->ipv4(),
            $this->faker->userAgent(),
            new DateTimeImmutable('-5 minutes'),
            new DateTimeImmutable('+1 hour'),
            false
        );
    }

    private function createRefreshToken(string $sessionId): AuthRefreshToken
    {
        return new AuthRefreshToken(
            (string) $this->ulidFactory->create(),
            $sessionId,
            $this->faker->sha256(),
            new DateTimeImmutable('+1 day')
        );
    }

    private function assertTokensRevokedForSession(string $sessionId): void
    {
        $tokens = $this->authRefreshTokenRepository->findBySessionId($sessionId);

        $this->assertNotEmpty($tokens);
        foreach ($tokens as $token) {
            $this->assertTrue($token->isRevoked());
        }
    }

    private function assertTokensNotRevokedForSession(string $sessionId): void
    {
        $tokens = $this->authRefreshTokenRepository->findBySessionId($sessionId);

        $this->assertNotEmpty($tokens);
        foreach ($tokens as $token) {
            $this->assertFalse($token->isRevoked());
        }
    }
}
