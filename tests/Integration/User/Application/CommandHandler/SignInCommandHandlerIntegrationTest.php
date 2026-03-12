<?php

declare(strict_types=1);

namespace App\Tests\Integration\User\Application\CommandHandler;

use App\Shared\Domain\Factory\UuidFactoryInterface;
use App\Tests\Integration\JwtPayloadDecoder;
use App\Tests\Integration\User\UserIntegrationTestCase;
use App\User\Application\Command\SignInCommand;
use App\User\Application\CommandHandler\SignInCommandHandler;
use App\User\Application\Factory\Generator\IdGeneratorInterface;
use App\User\Application\Processor\Authenticator\UserAuthenticatorInterface;
use App\User\Application\Processor\EventPublisher\SignInEventsInterface;
use App\User\Application\Processor\Hasher\PasswordHasherInterface;
use App\User\Application\Processor\Issuer\SessionIssuerInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\PendingTwoFactorFactoryInterface;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\AuthRefreshTokenRepositoryInterface;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;

final class SignInCommandHandlerIntegrationTest extends UserIntegrationTestCase
{
    private UserFactoryInterface $userFactory;
    private UserRepositoryInterface $userRepository;
    private PasswordHasherInterface $passwordHasher;
    private UuidFactoryInterface $uuidFactory;
    private AuthSessionRepositoryInterface $authSessionRepository;
    private AuthRefreshTokenRepositoryInterface $authRefreshTokenRepository;
    private PendingTwoFactorRepositoryInterface $pendingTwoFactorRepository;
    private PendingTwoFactorFactoryInterface $pendingTwoFactorFactory;
    private UserAuthenticatorInterface $authService;
    private SessionIssuerInterface $sessionIssuanceService;
    private SignInEventsInterface $signInEventPublisher;
    private IdGeneratorInterface $idGenerator;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->userFactory = $this->container->get(UserFactoryInterface::class);
        $this->userRepository = $this->container->get(UserRepositoryInterface::class);
        $this->passwordHasher = $this->container->get(PasswordHasherInterface::class);
        $this->uuidFactory = $this->container->get(UuidFactoryInterface::class);
        $this->authSessionRepository = $this->container
            ->get(AuthSessionRepositoryInterface::class);
        $this->authRefreshTokenRepository = $this->container
            ->get(AuthRefreshTokenRepositoryInterface::class);
        $this->pendingTwoFactorRepository = $this->container
            ->get(PendingTwoFactorRepositoryInterface::class);
        $this->pendingTwoFactorFactory = $this->container
            ->get(PendingTwoFactorFactoryInterface::class);
        $this->authService = $this->container
            ->get(UserAuthenticatorInterface::class);
        $this->sessionIssuanceService = $this->container
            ->get(SessionIssuerInterface::class);
        $this->signInEventPublisher = $this->container->get(SignInEventsInterface::class);
        $this->idGenerator = $this->container->get(IdGeneratorInterface::class);
    }

    public function testInvokePerformsFullSignInFlowAndPersistsSessionData(): void
    {
        $plainPassword = $this->faker->password();
        $user = $this->createAndSaveUser($plainPassword);
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();
        $command = new SignInCommand(
            $user->getEmail(),
            $plainPassword,
            false,
            $ipAddress,
            $userAgent
        );
        $this->createSignInHandler()->__invoke($command);
        $response = $command->getResponse();
        $this->assertFalse($response->isTwoFactorEnabled());
        $this->assertNotEmpty($response->getAccessToken());
        $this->assertNotEmpty($response->getRefreshToken());
        $this->assertSessionAndTokenPersistence($command, $user, $ipAddress, $userAgent);
    }

    private function createAndSaveUser(string $plainPassword): User
    {
        $user = $this->userFactory->create(
            $this->faker->email(),
            strtoupper($this->faker->lexify('??')),
            $this->passwordHasher->hash($plainPassword),
            $this->uuidFactory->create($this->faker->uuid())
        );
        $this->userRepository->save($user);

        return $user;
    }

    private function createSignInHandler(): SignInCommandHandler
    {
        return new SignInCommandHandler(
            $this->authService,
            $this->sessionIssuanceService,
            $this->signInEventPublisher,
            $this->pendingTwoFactorRepository,
            $this->pendingTwoFactorFactory,
            $this->idGenerator,
        );
    }

    private function assertSessionAndTokenPersistence(
        SignInCommand $command,
        User $user,
        string $ipAddress,
        string $userAgent
    ): void {
        $response = $command->getResponse();
        $payload = JwtPayloadDecoder::decode($response->getAccessToken());
        $this->assertSame($user->getId(), $payload['sub'] ?? null);
        $sessionId = (string) ($payload['sid'] ?? '');
        $this->assertNotSame('', $sessionId);
        $session = $this->authSessionRepository->findById($sessionId);
        $this->assertNotNull($session);
        $this->assertSame($user->getId(), $session->getUserId());
        $this->assertSame($ipAddress, $session->getIpAddress());
        $this->assertSame($userAgent, $session->getUserAgent());
        $this->assertFalse($session->isRememberMe());
        $this->assertRefreshTokenPersisted($response->getRefreshToken(), $sessionId);
    }

    private function assertRefreshTokenPersisted(string $refreshToken, string $sessionId): void
    {
        $hash = hash('sha256', $refreshToken);
        $token = $this->authRefreshTokenRepository->findByTokenHash($hash);
        $this->assertNotNull($token);
        $this->assertSame($sessionId, $token->getSessionId());
    }
}
