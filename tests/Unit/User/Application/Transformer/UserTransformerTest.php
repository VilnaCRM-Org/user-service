<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Transformer;

use App\Shared\Application\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\AuthorizationUserDto;
use App\User\Application\Transformer\UserTransformer;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;

final class UserTransformerTest extends UnitTestCase
{
    private UserFactoryInterface $userFactory;
    private UuidTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = new UserFactory();
        $this->transformer = new UuidTransformer();
    }

    public function testTransformToAuthorizationUser(): void
    {
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();
        $userId = $this->faker->uuid();
        $uuid = $this->transformer->transformFromString($userId);

        $user = $this->userFactory->create($email, $initials, $password, $uuid);

        $uuidTransformerMock =
            $this->createMock(UuidTransformer::class);
        $uuidTransformerMock->expects($this->once())
            ->method('transformFromString')
            ->with($userId)
            ->willReturn($uuid);

        $transformer = new UserTransformer($uuidTransformerMock);
        $authorizationUser = $transformer->transformToAuthorizationUser($user);

        $this->assertInstanceOf(
            AuthorizationUserDto::class,
            $authorizationUser
        );
    }
}
