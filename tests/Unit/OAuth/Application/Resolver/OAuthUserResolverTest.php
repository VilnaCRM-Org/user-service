<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Application\Resolver;

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

        $this->idFactory->method('create')
            ->willReturnCallback(fn () => $this->faker->uuid());

        $this->eventIdFactory->method('generate')
            ->willReturnCallback(fn () => $this->faker->uuid());

        $this->resolver = new OAuthUserResolver(
            $this->socialIdentityRepo,
            $this->userRepo,
            $this->passwordHasher,
            $this->idFactory,
            $this->eventIdFactory,
            $this->uuidTransformer,
        );
    }

    public function testResolveReturnsExistingUserWhenIdentityExists(): void
    {
        $provider = OAuthProvider::fromString($this->faker->word());
        $providerId = $this->faker->uuid();
        $userId = $this->faker->uuid();
        $user = $this->createUser($this->faker->safeEmail());

        $identity = $this->createIdentity($provider, $providerId, $userId);
        $profile = $this->createProfile($this->faker->safeEmail(), $providerId);

        $this->socialIdentityRepo->method('findByProviderAndProviderId')
            ->with($provider, $providerId)
            ->willReturn($identity);

        $this->userRepo->method('findById')
            ->with($userId)->willReturn($user);

        $this->socialIdentityRepo->expects($this->once())
            ->method('save');

        $result = $this->resolver->resolve($provider, $profile);

        $this->assertSame($user, $result->user);
        $this->assertFalse($result->newlyCreated);
    }

    public function testResolveUpdatesIdentityLastUsedAtWhenIdentityExists(): void
    {
        $provider = OAuthProvider::fromString($this->faker->word());
        $providerId = $this->faker->uuid();
        $userId = $this->faker->uuid();
        $createdAt = new DateTimeImmutable('-1 day');
        $identity = new SocialIdentity(
            $this->faker->uuid(),
            $provider,
            $providerId,
            $userId,
            $createdAt,
        );
        $user = $this->createUser($this->faker->safeEmail());

        $this->socialIdentityRepo->method('findByProviderAndProviderId')
            ->with($provider, $providerId)
            ->willReturn($identity);
        $this->userRepo->method('findById')
            ->with($userId)
            ->willReturn($user);
        $this->socialIdentityRepo->expects($this->once())
            ->method('save')
            ->with($identity);

        $this->resolver->resolve(
            $provider,
            $this->createProfile($this->faker->safeEmail(), $providerId)
        );

        $this->assertGreaterThan($createdAt, $identity->getLastUsedAt());
    }

    public function testResolveThrowsWhenIdentityUserCannotBeFound(): void
    {
        $provider = OAuthProvider::fromString($this->faker->word());
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
            $this->createProfile($this->faker->safeEmail(), $providerId)
        );
    }

    public function testResolveAutoLinksExistingUserByEmail(): void
    {
        $provider = OAuthProvider::fromString($this->faker->word());
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
        $provider = OAuthProvider::fromString($this->faker->word());
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
        $provider = OAuthProvider::fromString($this->faker->word());
        $email = $this->faker->safeEmail();
        $hashedPassword = $this->faker->sha256();

        $this->arrangeNoIdentityMatch();
        $this->userRepo->method('findByEmail')->willReturn(null);
        $this->passwordHasher->method('hash')
            ->willReturn($hashedPassword);

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

    public function testResolveCreatesNewUserWithTrimmedMultibyteInitials(): void
    {
        $provider = OAuthProvider::fromString($this->faker->word());
        $hashedPassword = $this->faker->sha256();
        $name = '  АБВГ  ';

        $this->arrangeNoIdentityMatch();
        $this->userRepo->method('findByEmail')->willReturn(null);
        $this->passwordHasher->expects($this->once())
            ->method('hash')
            ->with($this->callback(
                fn (string $password): bool => strlen($password) === 64
                    && ctype_xdigit($password)
            ))
            ->willReturn($hashedPassword);

        $result = $this->resolver->resolve(
            $provider,
            new OAuthUserProfile(
                $this->faker->safeEmail(),
                $name,
                $this->faker->uuid(),
                true,
            )
        );

        $this->assertSame('АБ', $result->user->getInitials());
    }

    public function testResolveUsesEmailPrefixWhenNameIsEmpty(): void
    {
        $provider = OAuthProvider::fromString($this->faker->word());
        $email = $this->faker->safeEmail();

        $this->arrangeNoIdentityMatch();
        $this->userRepo->method('findByEmail')->willReturn(null);
        $this->passwordHasher->method('hash')
            ->willReturn($this->faker->sha256());

        $profile = new OAuthUserProfile(
            $email,
            '',
            $this->faker->uuid(),
            true,
        );
        $result = $this->resolver->resolve($provider, $profile);

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
        $provider = OAuthProvider::fromString($this->faker->word());
        $email = 'ж@example.com';

        $this->arrangeNoIdentityMatch();
        $this->userRepo->method('findByEmail')->willReturn(null);
        $this->passwordHasher->method('hash')
            ->willReturn($this->faker->sha256());

        $result = $this->resolver->resolve(
            $provider,
            new OAuthUserProfile(
                $email,
                '   ',
                $this->faker->uuid(),
                true,
            )
        );

        $this->assertSame('ж', $result->user->getInitials());
    }

    public function testResolveUsesMultibyteEmailPrefixWhenNameIsBlank(): void
    {
        $provider = OAuthProvider::fromString($this->faker->word());
        $email = 'жя@example.com';

        $this->arrangeNoIdentityMatch();
        $this->userRepo->method('findByEmail')->willReturn(null);
        $this->passwordHasher->method('hash')
            ->willReturn($this->faker->sha256());

        $result = $this->resolver->resolve(
            $provider,
            new OAuthUserProfile(
                $email,
                '',
                $this->faker->uuid(),
                true,
            )
        );

        $this->assertSame('жя', $result->user->getInitials());
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

        $profile = $this->createProfile($email, null, false);
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

        $profile = $this->createProfile(
            $this->faker->safeEmail(), null, false
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

    private function createProfile(
        string $email,
        ?string $providerId = null,
        bool $emailVerified = true,
    ): OAuthUserProfile {
        return new OAuthUserProfile(
            $email,
            $this->faker->name(),
            $providerId ?? $this->faker->uuid(),
            $emailVerified,
        );
    }

    private function createIdentity(
        OAuthProvider $provider,
        string $providerId,
        string $userId,
    ): SocialIdentity {
        return new SocialIdentity(
            $this->faker->uuid(),
            $provider,
            $providerId,
            $userId,
            new DateTimeImmutable(),
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
