<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Command;

use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RegisterUserCommand;
use App\User\Application\Command\RegisterUserCommandResponse;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;

final class SignUpCommandTest extends UnitTestCase
{
    private UserFactoryInterface $userFactory;
    private UuidTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = new UserFactory();
        $this->transformer = new UuidTransformer(new UuidFactory());
    }

    public function testConstructor(): void
    {
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();

        $command = new RegisterUserCommand($email, $initials, $password);

        $this->assertInstanceOf(RegisterUserCommand::class, $command);
        $this->assertSame($email, $command->email);
        $this->assertSame($initials, $command->initials);
        $this->assertSame($password, $command->password);
    }

    public function testGetResponse(): void
    {
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();

        $user = $this->userFactory->create(
            $email,
            $initials,
            $password,
            $this->transformer->transformFromString($this->faker->uuid())
        );

        $command = new RegisterUserCommand($email, $initials, $password);
        $response = new RegisterUserCommandResponse($user);

        $command->setResponse($response);

        $this->assertSame($response, $command->getResponse());
        $this->assertSame($user, $command->getResponse()->createdUser);
    }
}
