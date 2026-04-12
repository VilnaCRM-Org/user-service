<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Application\Resolver;

use App\OAuth\Application\Factory\OAuthUserFactory;
use App\OAuth\Application\Resolver\OAuthUserResolver;
use App\OAuth\Domain\Entity\SocialIdentity;
use App\OAuth\Domain\Repository\SocialIdentityRepositoryInterface;
use App\OAuth\Domain\ValueObject\OAuthProvider;
use App\OAuth\Domain\ValueObject\OAuthUserProfile;
use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Factory\EventIdFactoryInterface;
use App\User\Application\Factory\IdFactoryInterface;
use App\User\Domain\Contract\PasswordHasherInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;

final class OAuthUserResolverTest extends UnitTestCase
{
    private const MULTIBYTE_PREFIX = "\u{0104}\u{017E}";

    private SocialIdentityRepositoryInterface&MockObject $socialIdentityRepo;
    private UserRepositoryInterface&MockObject $userRepo;
    private PasswordHasherInterface&MockObject $passwordHasher;
    private IdFactoryInterface&MockObject $idFactory;
    private EventIdFactoryInterface&MockObject $eventIdFactory;
    private OAuthUserResolver $resolver;
    private UserFactory $userFactory;
    private UuidTransformer $uuidTransformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->socialIdentityRepo = $this->createMock(SocialIdentityRepositoryInterface::class);
        $this->userRepo = $this->createMock(UserRepositoryInterface::class);
        $this->passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->eventIdFactory = $this->createMock(EventIdFactoryInterface::class);
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new UuidFactory());

        $this->configureFactories();
        $this->resolver = $this->createResolver();
    }

    public function testResolveReturnsExistingUserWhenIdentityExists(): void
    {
        $provider = $this->createProvider();
        $providerId = $this->faker->uuid();
        $userId = $this->faker->uuid();
        $user = $this->createUser($this->faker->safeEmail());
        $createdAt = new DateTimeImmutable('-1 day');
        $identity = $this->createIdentity($provider, $providerId, $userId, $createdAt);
        $profile = $this->createProfile($this->faker->safeEmail(), $providerId);

        $this->arrangeExistingIdentityResolution(
            $provider,
            $providerId,
            $userId,
            $user,
            $identity,
            $createdAt,
        );

        $result = $this->resolver->resolve($provider, $profile);

        $this->assertSame($user, $result->user);
        $this->assertFalse($result->newlyCreated);
    }

    public function testResolveThrowsWhenIdentityUserCannotBeFound(): void
    {
        $provider = $this->createProvider();
        $providerId = $this->faker->uuid();
        $userId = $this->faker->uuid();
        $identity = $this->createIdentity($provider, $providerId, $userId);

        $this->socialIdentityRepo->method('findByProviderAndProviderId')
            ->with($provider, $providerId)
            ->willReturn($identity);
        $this->socialIdentityRepo->expects($this->once())
            ->method('save')
            ->with($identity);
        $this->userRepo->method('findById')
            ->with($userId)
            ->willReturn(null);

        $this->expectException(UserNotFoundException::class);

        $this->resolver->resolve(
            $provider,
            $this->createProfile($this->faker->safeEmail(), $providerId),
        );
    }

    public function testResolveAutoLinksExistingUserByEmail(): void
    {
        $provider = $this->createProvider();
        $email = $this->faker->safeEmail();
        $user = $this->createUser($email);
        $profile = $this->createProfile($email);

        $this->arrangeNoIdentityMatch();
        $this->userRepo->method('findByEmail')
            ->with($email)->willReturn($user);

        $this->socialIdentityRepo->expects($this->once())
            ->method('save');

        $result = $this->resolver->resolve($provider, $profile);

        $this->assertSame($user, $result->user);
        $this->assertFalse($result->newlyCreated);
    }

    public function testResolveAutoLinkConfirmsUnconfirmedUser(): void
    {
        $provider = $this->createProvider();
        $email = $this->faker->safeEmail();
        $user = $this->createUser($email);
        $this->assertFalse($user->isConfirmed());

        $this->arrangeNoIdentityMatch();
        $this->userRepo->method('findByEmail')
            ->with($email)->willReturn($user);

        $this->userRepo->expects($this->once())->method('save');

        $result = $this->resolver->resolve(
            $provider,
            $this->createProfile($email)
        );

        $this->assertTrue($result->user->isConfirmed());
    }

    public function testResolveCreatesNewUserWhenNoMatch(): void
    {
        $provider = $this->createProvider();
        $email = $this->faker->safeEmail();
        $hashedPassword = $this->faker->sha256();

        $this->arrangeNewUserResolution($hashedPassword);

        $this->userRepo->expects($this->once())->method('save');
        $this->socialIdentityRepo->expects($this->once())
            ->method('save');

        $result = $this->resolver->resolve(
            $provider,
            $this->createProfile($email)
        );

        $this->assertTrue($result->newlyCreated);
        $this->assertSame($email, $result->user->getEmail());
        $this->assertTrue($result->user->isConfirmed());
    }

    public function testResolveHashesGeneratedPasswordUsingExpectedEntropyLength(): void
    {
        $this->arrangeNoIdentityMatch();
        $this->userRepo->method('findByEmail')->willReturn(null);
        $this->passwordHasher->expects($this->once())
            ->method('hash')
            ->with($this->callback(
                static fn (string $plainPassword): bool => strlen($plainPassword) === 64
                    && ctype_xdigit($plainPassword)
            ))
            ->willReturn($this->faker->sha256());

        $this->resolver->resolve(
            $this->createProvider(),
            $this->createProfile($this->faker->safeEmail())
        );
    }

    public function testResolveUsesTrimmedMultibyteNameForInitials(): void
    {
        $name = sprintf(
            '  %s%s  ',
            self::MULTIBYTE_PREFIX,
            strtolower($this->faker->lexify('??????'))
        );
        $result = $this->resolveNewUser(
            new OAuthUserProfile(
                $this->faker->safeEmail(),
                $name,
                $this->faker->uuid(),
                true,
            )
        );

        $this->assertSame(self::MULTIBYTE_PREFIX, $result->user->getInitials());
    }

    public function testResolveUsesEmailPrefixWhenNameIsEmpty(): void
    {
        $email = $this->faker->safeEmail();
        $result = $this->resolveNewUser(
            new OAuthUserProfile(
                $email,
                '',
                $this->faker->uuid(),
                true,
            )
        );

        $localPart = strstr($email, '@', true);
        $expected = mb_substr(
            $localPart !== false ? $localPart : $email,
            0,
            2
        );
        $this->assertSame($expected, $result->user->getInitials());
    }

    public function testResolveUsesEmailPrefixWhenNameContainsOnlyWhitespace(): void
    {
        $email = $this->faker->safeEmail();
        $result = $this->resolveNewUser(
            new OAuthUserProfile(
                $email,
                '   ',
                $this->faker->uuid(),
                true,
            )
        );

        $localPart = strstr($email, '@', true);
        if ($localPart === false) {
            $localPart = $email;
        }

        $this->assertSame(
            mb_substr($localPart, 0, 2),
            $result->user->getInitials(),
        );
    }

    public function testResolveUsesWholeEmailWhenSeparatorIsMissing(): void
    {
        $email = sprintf(
            '%s%s',
            self::MULTIBYTE_PREFIX,
            strtolower($this->faker->lexify('????'))
        );
        $result = $this->resolveNewUser(
            new OAuthUserProfile(
                $email,
                '',
                $this->faker->uuid(),
                true,
            )
        );

        $this->assertSame(self::MULTIBYTE_PREFIX, $result->user->getInitials());
    }

    public function testResolveSkipsAutoLinkWhenEmailNotVerified(): void
    {
        $provider = OAuthProvider::fromString($this->faker->word());
        $email = $this->faker->safeEmail();
        $this->createUser($email);

        $this->arrangeNoIdentityMatch();
        $this->userRepo->expects($this->never())->method('findByEmail');
        $this->passwordHasher->method('hash')
            ->willReturn($this->faker->sha256());

        $profile = $this->createUnverifiedProfile($email);
        $result = $this->resolver->resolve($provider, $profile);

        $this->assertTrue($result->newlyCreated);
        $this->assertFalse($result->user->isConfirmed());
    }

    public function testResolveNewUserNotConfirmedWhenEmailNotVerified(): void
    {
        $provider = OAuthProvider::fromString($this->faker->word());

        $this->arrangeNoIdentityMatch();
        $this->userRepo->method('findByEmail')->willReturn(null);
        $this->passwordHasher->method('hash')
            ->willReturn($this->faker->sha256());

        $profile = $this->createUnverifiedProfile(
            $this->faker->safeEmail(),
        );
        $result = $this->resolver->resolve($provider, $profile);

        $this->assertTrue($result->newlyCreated);
        $this->assertFalse($result->user->isConfirmed());
    }

    public function testResolveAutoLinkSkipsSaveIfAlreadyConfirmed(): void
    {
        $provider = OAuthProvider::fromString($this->faker->word());
        $email = $this->faker->safeEmail();
        $user = $this->createUser($email);
        $user->setConfirmed(true);

        $this->arrangeNoIdentityMatch();
        $this->userRepo->method('findByEmail')
            ->willReturn($user);

        $this->userRepo->expects($this->never())->method('save');

        $this->resolver->resolve($provider, $this->createProfile($email));
    }

    private function arrangeNoIdentityMatch(): void
    {
        $this->socialIdentityRepo->method('findByProviderAndProviderId')
            ->willReturn(null);
    }

    private function arrangeExistingIdentityResolution(
        OAuthProvider $provider,
        string $providerId,
        string $userId,
        User $user,
        SocialIdentity $identity,
        DateTimeImmutable $createdAt,
    ): void {
        $this->socialIdentityRepo->method('findByProviderAndProviderId')
            ->with($provider, $providerId)
            ->willReturn($identity);
        $this->userRepo->method('findById')
            ->with($userId)->willReturn($user);
        $this->socialIdentityRepo->expects($this->once())
            ->method('save')
            ->with($this->callback(
                static function (SocialIdentity $savedIdentity) use ($identity, $createdAt): bool {
                    return $savedIdentity === $identity
                        && $savedIdentity->getLastUsedAt() > $createdAt;
                }
            ));
    }

    private function arrangeNewUserResolution(
        ?string $hashedPassword = null,
    ): void {
        $this->arrangeNoIdentityMatch();
        $this->userRepo->method('findByEmail')->willReturn(null);
        $this->passwordHasher->method('hash')
            ->willReturn($hashedPassword ?? $this->faker->sha256());
    }

    private function resolveNewUser(
        OAuthUserProfile $profile,
    ): \App\OAuth\Application\DTO\OAuthResolvedUser {
        $this->arrangeNewUserResolution();

        return $this->resolver->resolve($this->createProvider(), $profile);
    }

    private function createProfile(
        string $email,
        ?string $providerId = null,
    ): OAuthUserProfile {
        return new OAuthUserProfile(
            $email,
            $this->faker->name(),
            $providerId ?? $this->faker->uuid(),
            true,
        );
    }

    private function createUnverifiedProfile(
        string $email,
        ?string $providerId = null,
    ): OAuthUserProfile {
        return new OAuthUserProfile(
            $email,
            $this->faker->name(),
            $providerId ?? $this->faker->uuid(),
            false,
        );
    }

    private function createIdentity(
        OAuthProvider $provider,
        string $providerId,
        string $userId,
        ?DateTimeImmutable $createdAt = null,
    ): SocialIdentity {
        return new SocialIdentity(
            $this->faker->uuid(),
            $provider,
            $providerId,
            $userId,
            $createdAt ?? new DateTimeImmutable(),
        );
    }

    private function createProvider(): OAuthProvider
    {
        return OAuthProvider::fromString($this->faker->word());
    }

    private function configureFactories(): void
    {
        $this->idFactory->method('create')
            ->willReturnCallback(fn () => $this->faker->uuid());

        $this->eventIdFactory->method('generate')
            ->willReturnCallback(fn () => $this->faker->uuid());
    }

    private function createResolver(): OAuthUserResolver
    {
        return new OAuthUserResolver(
            $this->socialIdentityRepo,
            $this->userRepo,
            $this->idFactory,
            $this->createOAuthUserFactory(),
        );
    }

    private function createOAuthUserFactory(): OAuthUserFactory
    {
        return new OAuthUserFactory(
            $this->passwordHasher,
            $this->eventIdFactory,
            $this->uuidTransformer,
        );
    }

    private function createUser(string $email): User
    {
        return $this->userFactory->create(
            $email,
            $this->faker->firstName(),
            $this->faker->password(),
            $this->uuidTransformer->transformFromString($this->faker->uuid()),
        );
    }
}
