<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Transformer;

use App\Shared\Application\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\AuthorizationUser;
use App\User\Application\Transformer\UserTransformer;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;

class UserTransformerTest extends UnitTestCase
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
        $uuid = $this->faker->uuid();

        $user = $this->userFactory->create(
            $email,
            $initials,
            $password,
            $this->transformer->transformFromString($uuid)
        );

        $uuidTransformerMock = $this->createMock(UuidTransformer::class);
        $uuidTransformerMock->expects($this->once())
            ->method('transformFromString')
            ->with($uuid)
            ->willReturn($this->transformer->transformFromString($uuid));

        $transformer = new UserTransformer($uuidTransformerMock);
        $authorizationUser = $transformer->transformToAuthorizationUser($user);

        $this->assertInstanceOf(AuthorizationUser::class, $authorizationUser);
    }
}
