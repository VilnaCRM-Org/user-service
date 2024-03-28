<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Transformer;

use App\Shared\Application\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RegisterUserCommand;
use App\User\Application\Transformer\SignUpTransformer;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

class SignUpTransformerTest extends UnitTestCase
{
    private UserFactoryInterface $userFactory;
    private UuidTransformer $transformer;
    private UuidFactory $symfonyUuidFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = new UserFactory();
        $this->transformer = new UuidTransformer();
        $this->symfonyUuidFactory = new UuidFactory();
    }

    public function testTransformToUser(): void
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

        $userFactoryMock = $this->createMock(UserFactoryInterface::class);
        $userFactoryMock->expects($this->once())
            ->method('create')
            ->with($email, $initials, $password, $uuid)
            ->willReturn($user);

        $uuidTransformerMock = $this->createMock(UuidTransformer::class);
        $uuidTransformerMock->expects($this->once())
            ->method('transformFromSymfonyUuid')
            ->willReturn($this->transformer->transformFromString($uuid));

        $uuidFactoryMock = $this->createMock(UuidFactory::class);
        $uuidFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->symfonyUuidFactory->create());

        $transformer = new SignUpTransformer(
            $userFactoryMock,
            $uuidTransformerMock,
            $uuidFactoryMock
        );

        $command = new RegisterUserCommand($email, $initials, $password);
        $user = $transformer->transformToUser($command);

        $this->assertEquals($email, $user->getEmail());
        $this->assertEquals($initials, $user->getInitials());
        $this->assertEquals($password, $user->getPassword());
        $this->assertEquals($uuid, $user->getId());
    }
}
