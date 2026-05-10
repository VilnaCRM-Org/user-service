<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Support;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Factory\SignUpCommandFactory;
use App\User\Application\Factory\SignUpCommandFactoryInterface;
use App\User\Application\Query\FindUserByEmailQueryHandlerInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use PHPUnit\Framework\MockObject\MockObject;

abstract class RegisterUserCommandTestCase extends UnitTestCase
{
    protected SignUpCommandFactoryInterface $signUpCommandFactory;
    protected UserFactoryInterface $userFactory;
    protected UuidTransformer $uuidTransformer;
    protected SignUpCommandFactoryInterface&MockObject $mockSignUpCommandFactory;
    protected CommandBusInterface&MockObject $commandBus;
    protected FindUserByEmailQueryHandlerInterface&MockObject $findUserByEmailQueryHandler;
    protected RegisterUserCommandExpectationHelper $commandExpectationHelper;

    protected function setUpRegisterUserCommandContext(): void
    {
        $this->createRegistrationCollaborators();
        $this->createRegistrationMocks();
        $this->commandExpectationHelper =
            new RegisterUserCommandExpectationHelper(
                $this->findUserByEmailQueryHandler,
                $this->mockSignUpCommandFactory,
                $this->commandBus
            );
    }

    protected function createUser(
        string $email,
        string $initials,
        string $password
    ): User {
        return $this->userFactory->create(
            $email,
            $initials,
            $password,
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );
    }

    private function createRegistrationCollaborators(): void
    {
        $this->signUpCommandFactory = new SignUpCommandFactory();
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new UuidFactory());
    }

    private function createRegistrationMocks(): void
    {
        $this->mockSignUpCommandFactory =
            $this->createMock(SignUpCommandFactoryInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->findUserByEmailQueryHandler = $this->createMock(
            FindUserByEmailQueryHandlerInterface::class
        );
    }
}
