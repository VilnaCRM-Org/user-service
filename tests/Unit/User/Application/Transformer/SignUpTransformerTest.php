<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Transformer;

use App\Shared\Application\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RegisterUserCommand;
use App\User\Application\Transformer\SignUpTransformer;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

final class SignUpTransformerTest extends UnitTestCase
{
    private UserFactoryInterface $userFactory;
    private UuidTransformer $transformer;
    private UuidFactory $symfonyUuidFactory;
    private UuidTransformer $uuidTransformerMock;
    private UuidFactory $uuidFactoryMock;
    private UserFactoryInterface $userFactoryMock;
    private SignUpTransformer $signUpTransformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = new UserFactory();
        $this->transformer = new UuidTransformer();
        $this->symfonyUuidFactory = new UuidFactory();
        $this->uuidTransformerMock =
            $this->createMock(UuidTransformer::class);
        $this->uuidFactoryMock =
            $this->createMock(UuidFactory::class);
        $this->userFactoryMock =
            $this->createMock(UserFactoryInterface::class);
        $this->signUpTransformer = new SignUpTransformer(
            $this->userFactoryMock,
            $this->uuidTransformerMock,
            $this->uuidFactoryMock
        );
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
        $this->setExpectations($user);

        $command = new RegisterUserCommand($email, $initials, $password);
        $user = $this->signUpTransformer->transformToUser($command);

        $this->assertEquals($email, $user->getEmail());
        $this->assertEquals($initials, $user->getInitials());
        $this->assertEquals($password, $user->getPassword());
        $this->assertEquals($uuid, $user->getId());
    }

    private function setExpectations(UserInterface $user): void
    {
        $this->userFactoryMock->expects($this->once())
            ->method('create')
            ->with(
                $user->getEmail(),
                $user->getInitials(),
                $user->getPassword(),
                $user->getId()
            )
            ->willReturn($user);

        $this->uuidTransformerMock->expects($this->once())
            ->method('transformFromSymfonyUuid')
            ->willReturn(
                $this->transformer->transformFromString($user->getId())
            );

        $this->uuidFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->symfonyUuidFactory->create());
    }
}
