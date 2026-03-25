<?php

declare(strict_types=1);

namespace App\Tests\Integration\User\Application\CommandHandler;

use App\Shared\Domain\Factory\UuidFactoryInterface;
use App\Tests\Integration\User\UserIntegrationTestCase;
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

final class UpdateUserCommandHandlerIntegrationTest extends UserIntegrationTestCase
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
        $this->authRefreshTokenRepository = $this->container->get(
            AuthRefreshTokenRepositoryInterface::class
        );
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
        $this->createSessionsWithTokens(
            $user->getId(),
            $currentSessionId,
            $otherSessionOneId,
            $otherSessionTwoId
        );
        $command = $this->buildUpdateCommand($user, $oldPassword, $newPassword, $currentSessionId);
        $this->handler->__invoke($command);
        $this->assertCurrentSessionPreserved($currentSessionId);
        $this->assertOtherSessionsRevoked($otherSessionOneId, $otherSessionTwoId);
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

    private function createSessionsWithTokens(string $userId, string ...$sessionIds): void
    {
        foreach ($sessionIds as $sessionId) {
            $this->authSessionRepository->save($this->createSession($sessionId, $userId));
            $this->authRefreshTokenRepository->save($this->createRefreshToken($sessionId));
        }
    }

    private function buildUpdateCommand(
        User $user,
        string $oldPassword,
        string $newPassword,
        string $currentSessionId
    ): UpdateUserCommand {
        return new UpdateUserCommand(
            $user,
            new UserUpdate(
                $user->getEmail(),
                strtoupper($this->faker->lexify('????')),
                $newPassword,
                $oldPassword
            ),
            $currentSessionId
        );
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

    private function assertCurrentSessionPreserved(string $currentSessionId): void
    {
        $currentSession = $this->authSessionRepository->findById($currentSessionId);
        $this->assertNotNull($currentSession);
        $this->assertFalse($currentSession->isRevoked());
        $this->assertTokensNotRevokedForSession($currentSessionId);
    }

    private function assertOtherSessionsRevoked(string ...$sessionIds): void
    {
        foreach ($sessionIds as $sessionId) {
            $session = $this->authSessionRepository->findById($sessionId);
            $this->assertNotNull($session);
            $this->assertTrue($session->isRevoked());
            $this->assertTokensRevokedForSession($sessionId);
        }
    }
}
