<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Application\CommandHandler;

use App\OAuth\Application\Collection\OAuthProviderCollection;
use App\OAuth\Application\Command\HandleOAuthCallbackCommand;
use App\OAuth\Application\CommandHandler\HandleOAuthCallbackCommandHandler;
use App\OAuth\Application\CommandHandler\OAuthCallbackTwoFactorHandler;
use App\OAuth\Application\DTO\OAuthResolvedUser;
use App\OAuth\Application\Provider\OAuthProviderInterface;
use App\OAuth\Application\Provider\OAuthProviderRegistry;
use App\OAuth\Application\Resolver\OAuthUserResolverInterface;
use App\OAuth\Domain\Repository\OAuthStateRepositoryInterface;
use App\OAuth\Domain\ValueObject\OAuthProvider;
use App\OAuth\Domain\ValueObject\OAuthStatePayload;
use App\OAuth\Domain\ValueObject\OAuthUserProfile;
use App\OAuth\Infrastructure\Publisher\OAuthPublisherInterface;
use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\IssuedSession;
use App\User\Application\Factory\IdFactoryInterface;
use App\User\Application\Factory\IssuedSessionFactoryInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\PendingTwoFactorFactory;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Uid\Ulid;

final class HandleOAuthCallbackCommandHandlerTest extends UnitTestCase
{
    private OAuthStateRepositoryInterface&MockObject $stateRepository;
    private OAuthUserResolverInterface&MockObject $userResolver;
    private IssuedSessionFactoryInterface&MockObject $sessionFactory;
    private OAuthPublisherInterface&MockObject $oAuthPublisher;
    private PendingTwoFactorRepositoryInterface&MockObject $pendingTwoFactorRepo;
    private IdFactoryInterface&MockObject $idFactory;
    private OAuthProviderInterface&MockObject $oAuthProvider;
    private UserFactory $userFactory;
    private UuidTransformer $uuidTransformer;
    private string $providerName;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->providerName = 'github';
        $this->createAllMocks();
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new UuidFactory());

        $this->oAuthProvider->method('getProvider')
            ->willReturn(OAuthProvider::fromString($this->providerName));

        $this->idFactory->method('create')
            ->willReturn($this->faker->uuid());
    }

    public function testInvokeDirectSignInForExistingUser(): void
    {
        $user = $this->createUser();
        $command = $this->createCommand(ipAddress: '203.0.113.10', userAgent: 'OAuth Test Agent');

        $this->arrangeCommonMocks($user, false);
        $this->expectDirectSignInSessionCreation($user, $command);

        $this->oAuthPublisher->expects($this->never())
            ->method('publishUserCreated');

        $this->oAuthPublisher->expects($this->once())
            ->method('publishUserSignedIn')
            ->with(
                $user->getId(),
                $user->getEmail(),
                $this->providerName,
                $this->isType('string'),
            );

        $this->createHandler()->__invoke($command);

        $response = $command->getResponse();
        $this->assertFalse($response->isTwoFactorEnabled());
        $this->assertNotEmpty($response->getAccessToken());
        $this->assertNotEmpty($response->getRefreshToken());
    }

    public function testInvokePublishesUserCreatedForNewUser(): void
    {
        $user = $this->createUser();

        $this->arrangeCommonMocks($user, true);
        $this->stubDirectSignInSessionCreation();

        $this->oAuthPublisher->expects($this->once())
            ->method('publishUserCreated')
            ->with(
                $user->getId(),
                $user->getEmail(),
                $this->providerName,
            );

        $this->oAuthPublisher->expects($this->once())
            ->method('publishUserSignedIn');

        $command = $this->createCommand();
        $this->createHandler()->__invoke($command);
    }

    public function testInvokeTwoFactorPathReturnsPendingSession(): void
    {
        $user = $this->createUser();
        $user->setTwoFactorEnabled(true);

        $this->arrangeCommonMocks($user, false);
        $this->sessionFactory->expects($this->never())
            ->method('create');

        $this->pendingTwoFactorRepo->expects($this->once())
            ->method('save');

        $this->oAuthPublisher->expects($this->never())
            ->method('publishUserSignedIn');

        $command = $this->createCommand();
        $this->createHandler()->__invoke($command);

        $response = $command->getResponse();
        $this->assertTrue($response->isTwoFactorEnabled());
        $this->assertNull($response->getAccessToken());
        $this->assertNotEmpty($response->getPendingSessionId());
    }

    public function testInvokeExchangesCodeWithPkceVerifier(): void
    {
        $user = $this->createUser();
        $codeVerifier = $this->faker->sha256();

        $this->arrangeStatePayload($codeVerifier);
        $this->oAuthProvider->method('supportsPkce')->willReturn(true);
        $this->stubDirectSignInSessionCreation();

        $this->oAuthProvider->expects($this->once())
            ->method('exchangeCode')
            ->with($this->isType('string'), $codeVerifier)
            ->willReturn($this->faker->sha256());

        $this->arrangeProfileAndResolver($user, false);

        $command = $this->createCommand();
        $this->createHandler()->__invoke($command);
    }

    public function testInvokeExchangesCodeWithoutPkceWhenNotSupported(): void
    {
        $user = $this->createUser();

        $this->arrangeStatePayload();
        $this->oAuthProvider->method('supportsPkce')->willReturn(false);
        $this->stubDirectSignInSessionCreation();

        $this->oAuthProvider->expects($this->once())
            ->method('exchangeCode')
            ->with($this->isType('string'), null)
            ->willReturn($this->faker->sha256());

        $this->arrangeProfileAndResolver($user, false);

        $command = $this->createCommand();
        $this->createHandler()->__invoke($command);
    }

    private function createAllMocks(): void
    {
        $this->stateRepository = $this->createMock(OAuthStateRepositoryInterface::class);
        $this->userResolver = $this->createMock(OAuthUserResolverInterface::class);
        $this->sessionFactory = $this->createMock(IssuedSessionFactoryInterface::class);
        $this->oAuthPublisher = $this->createMock(OAuthPublisherInterface::class);
        $this->pendingTwoFactorRepo = $this->createMock(
            PendingTwoFactorRepositoryInterface::class
        );
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->oAuthProvider = $this->createMock(OAuthProviderInterface::class);
    }

    private function expectDirectSignInSessionCreation(
        User $user,
        HandleOAuthCallbackCommand $command,
    ): void {
        $this->sessionFactory->expects($this->once())
            ->method('create')
            ->with(
                $this->identicalTo($user),
                $command->ipAddress,
                $command->userAgent,
                false,
                $this->isInstanceOf(DateTimeImmutable::class),
            )
            ->willReturn($this->createIssuedSession());
    }

    private function stubDirectSignInSessionCreation(): void
    {
        $this->sessionFactory->method('create')
            ->willReturn($this->createIssuedSession());
    }

    private function arrangeCommonMocks(
        User $user,
        bool $newlyCreated,
    ): void {
        $this->arrangeStatePayload();
        $this->oAuthProvider->method('supportsPkce')->willReturn(true);
        $this->oAuthProvider->method('exchangeCode')
            ->willReturn($this->faker->sha256());
        $this->arrangeProfileAndResolver($user, $newlyCreated);
    }

    private function arrangeStatePayload(
        ?string $codeVerifier = null,
    ): void {
        $payload = new OAuthStatePayload(
            $this->providerName,
            $codeVerifier ?? $this->faker->sha256(),
            $this->faker->sha256(),
            $this->faker->url(),
            new DateTimeImmutable(),
        );

        $this->stateRepository->method('validateAndConsume')
            ->with(
                $this->isType('string'),
                $this->providerName,
                $this->isType('string'),
            )
            ->willReturn($payload);
    }

    private function arrangeProfileAndResolver(
        User $user,
        bool $newlyCreated,
    ): void {
        $profile = new OAuthUserProfile(
            $user->getEmail(),
            $this->faker->name(),
            $this->faker->uuid(),
            true,
        );
        $this->oAuthProvider->method('fetchProfile')
            ->willReturn($profile);

        $this->userResolver->method('resolve')
            ->willReturn(new OAuthResolvedUser($user, $newlyCreated));
    }

    private function createCommand(
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): HandleOAuthCallbackCommand {
        return new HandleOAuthCallbackCommand(
            $this->providerName,
            $this->faker->sha256(),
            $this->faker->sha256(),
            $this->faker->sha256(),
            $ipAddress ?? $this->faker->ipv4(),
            $userAgent ?? $this->faker->userAgent(),
        );
    }

    private function createUser(): User
    {
        return $this->userFactory->create(
            $this->faker->safeEmail(),
            $this->faker->firstName(),
            $this->faker->password(),
            $this->uuidTransformer->transformFromString($this->faker->uuid()),
        );
    }

    private function createIssuedSession(): IssuedSession
    {
        return new IssuedSession(
            (string) new Ulid(),
            $this->faker->sha256(),
            $this->faker->sha256(),
        );
    }

    private function createHandler(): HandleOAuthCallbackCommandHandler
    {
        $registry = new OAuthProviderRegistry(
            new OAuthProviderCollection($this->oAuthProvider)
        );

        $twoFactorHandler = new OAuthCallbackTwoFactorHandler(
            $this->pendingTwoFactorRepo,
            new PendingTwoFactorFactory(),
            $this->idFactory,
        );

        return new HandleOAuthCallbackCommandHandler(
            $registry,
            $this->stateRepository,
            $this->userResolver,
            $this->sessionFactory,
            $this->oAuthPublisher,
            $twoFactorHandler,
        );
    }
}
