<?php

declare(strict_types=1);

namespace App\Tests\Integration\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\Factory\UuidFactoryInterface;
use App\Tests\Integration\IntegrationTestCase;
use App\User\Application\Command\SignInCommand;
use App\User\Application\CommandHandler\SignInCommandHandler;
use App\User\Domain\Contract\AccessTokenGeneratorInterface;
use App\User\Domain\Contract\AccountLockoutServiceInterface;
use App\User\Domain\Contract\PasswordHasherInterface;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Uid\Factory\UlidFactory;
use Symfony\Component\Uid\Factory\UuidFactory;

final class SignInCommandHandlerIntegrationTest extends IntegrationTestCase
{
    private UserFactoryInterface $userFactory;
    private UserRepositoryInterface $userRepository;
    private PasswordHasherInterface $passwordHasher;
    private UuidFactoryInterface $uuidFactory;
    private PasswordHasherFactoryInterface $hasherFactory;
    private AccessTokenGeneratorInterface $accessTokenGenerator;
    private UuidFactory $symfonyUuidFactory;
    private UlidFactory $ulidFactory;
    private EventBusInterface $eventBus;
    private AuthSessionRepositoryInterface $authSessionRepository;
    private AuthRefreshTokenRepositoryInterface $authRefreshTokenRepository;
    private PendingTwoFactorRepositoryInterface $pendingTwoFactorRepository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = $this->container->get(UserFactoryInterface::class);
        $this->userRepository = $this->container->get(UserRepositoryInterface::class);
        $this->passwordHasher = $this->container->get(PasswordHasherInterface::class);
        $this->uuidFactory = $this->container->get(UuidFactoryInterface::class);
        $this->hasherFactory = $this->container->get(PasswordHasherFactoryInterface::class);
        $this->accessTokenGenerator = $this->container->get(AccessTokenGeneratorInterface::class);
        $this->symfonyUuidFactory = $this->container->get(UuidFactory::class);
        $this->ulidFactory = $this->container->get(UlidFactory::class);
        $this->eventBus = new class() implements EventBusInterface {
            #[\Override]
            public function publish(DomainEvent ...$events): void
            {
            }
        };
        $this->authSessionRepository = $this->container->get(AuthSessionRepositoryInterface::class);
        $this->authRefreshTokenRepository = $this->container->get(AuthRefreshTokenRepositoryInterface::class);
        $this->pendingTwoFactorRepository = $this->container->get(PendingTwoFactorRepositoryInterface::class);
    }

    public function testInvokePerformsFullSignInFlowAndPersistsSessionData(): void
    {
        $email = $this->faker->email();
        $plainPassword = $this->faker->password();
        $userId = $this->faker->uuid();
        $initials = strtoupper($this->faker->lexify('??'));

        $user = $this->userFactory->create(
            $email,
            $initials,
            $this->passwordHasher->hash($plainPassword),
            $this->uuidFactory->create($userId)
        );
        $this->userRepository->save($user);

        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();

        $signInCommandHandler = new SignInCommandHandler(
            $this->userRepository,
            $this->authSessionRepository,
            $this->authRefreshTokenRepository,
            $this->pendingTwoFactorRepository,
            $this->hasherFactory,
            $this->container->get(AccountLockoutServiceInterface::class),
            $this->accessTokenGenerator,
            $this->eventBus,
            $this->symfonyUuidFactory,
            $this->ulidFactory,
        );

        $command = new SignInCommand($email, $plainPassword, false, $ipAddress, $userAgent);
        $signInCommandHandler->__invoke($command);
        $response = $command->getResponse();

        $this->assertFalse($response->isTwoFactorEnabled());
        $this->assertNotEmpty($response->getAccessToken());
        $this->assertNotEmpty($response->getRefreshToken());

        $accessTokenPayload = $this->decodeJwtPayload($response->getAccessToken());
        $this->assertSame($user->getId(), $accessTokenPayload['sub'] ?? null);

        $sessionId = (string) ($accessTokenPayload['sid'] ?? '');
        $this->assertNotSame('', $sessionId);

        $session = $this->authSessionRepository->findById($sessionId);
        $this->assertNotNull($session);
        $this->assertSame($user->getId(), $session->getUserId());
        $this->assertSame($ipAddress, $session->getIpAddress());
        $this->assertSame($userAgent, $session->getUserAgent());
        $this->assertFalse($session->isRememberMe());

        $refreshTokenHash = hash('sha256', $response->getRefreshToken());
        $refreshToken = $this->authRefreshTokenRepository->findByTokenHash($refreshTokenHash);
        $this->assertNotNull($refreshToken);
        $this->assertSame($sessionId, $refreshToken->getSessionId());
    }

    /**
     * @return array<string, array<string>|int|string>
     */
    private function decodeJwtPayload(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return [];
        }

        $payload = $this->base64UrlDecode($parts[1]);
        if ($payload === '') {
            return [];
        }

        $decoded = json_decode($payload, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function base64UrlDecode(string $input): string
    {
        $remainder = strlen($input) % 4;
        if ($remainder > 0) {
            $input .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($input, '-_', '+/'), true);

        return is_string($decoded) ? $decoded : '';
    }
}
