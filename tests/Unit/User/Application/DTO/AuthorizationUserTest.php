<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\DTO;

use App\Shared\Domain\ValueObject\UuidInterface;
use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\AuthorizationUserDto;

final class AuthorizationUserTest extends UnitTestCase
{
    private AuthorizationUserDto $authUser;
    private UuidTransformer $transformer;
    private string $email;
    private string $initials;
    private string $password;
    private bool $confirmed;
    private UuidInterface $uuid;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->transformer = new UuidTransformer(new UuidFactory());

        $this->email = $this->faker->email();
        $this->initials = $this->faker->name();
        $this->password = $this->faker->password();
        $this->uuid = $this->transformer->transformFromString(
            $this->faker->uuid()
        );
        $this->confirmed = true;

        $this->authUser = new AuthorizationUserDto(
            $this->email,
            $this->initials,
            $this->password,
            $this->uuid,
            $this->confirmed
        );
    }

    public function testGetRoles(): void
    {
        $this->assertEquals([], $this->authUser->getRoles());
    }

    public function testEraseCredentials(): void
    {
        $this->authUser->eraseCredentials();

        $this->addToAssertionCount(1);
    }

    public function testGetUserIdentifier(): void
    {
        $this->assertEquals($this->email, $this->authUser->getUserIdentifier());
    }

    public function testExposesCredentialsAndProfile(): void
    {
        $this->assertSame($this->password, $this->authUser->getPassword());
        $this->assertSame($this->initials, $this->authUser->getInitials());
        $this->assertSame($this->uuid, $this->authUser->getId());
        $this->assertTrue($this->authUser->isConfirmed());
    }
}
