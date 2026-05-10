<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Resolver;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RegisterUserCommand;
use App\User\Application\Factory\SignUpCommandFactory;
use App\User\Application\Factory\SignUpCommandFactoryInterface;
use App\User\Application\Query\FindUserByEmailQueryHandlerInterface;
use App\User\Application\Resolver\RegisterUserMutationResolver;
use App\User\Application\Transformer\CreateUserMutationInputTransformer;
use App\User\Application\Validator\MutationInputValidator;
use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;

final class RegisterUserMutationResolverTest extends UnitTestCase
{
    private SignUpCommandFactoryInterface $signUpCommandFactory;
    private UserFactoryInterface $userFactory;
    private UuidTransformer $uuidTransformer;
    private SignUpCommandFactoryInterface $mockSignUpCommandFactory;
    private CommandBusInterface $commandBus;
    private MutationInputValidator $validator;
    private CreateUserMutationInputTransformer $transformer;
    private FindUserByEmailQueryHandlerInterface $findUserByEmailQueryHandler;
    private RegisterUserMutationResolver $resolver;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->signUpCommandFactory = new SignUpCommandFactory();
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new UuidFactory());
        $this->mockSignUpCommandFactory =
            $this->createMock(SignUpCommandFactoryInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->validator = $this->createMock(MutationInputValidator::class);
        $this->transformer =
            $this->createMock(CreateUserMutationInputTransformer::class);
        $this->findUserByEmailQueryHandler = $this->createMock(
            FindUserByEmailQueryHandlerInterface::class
        );
        $this->resolver = new RegisterUserMutationResolver(
            $this->commandBus,
            $this->validator,
            $this->transformer,
            $this->mockSignUpCommandFactory,
            $this->findUserByEmailQueryHandler
        );
    }

    public function testInvokeReturnsExistingUserWithoutDispatch(): void
    {
        $email = $this->faker->email();
        $input = $this->createInput($email);
        $user = $this->createUserFromInput($input);

        $this->setValidationExpectations($input);
        $this->findUserByEmailQueryHandler->expects($this->once())
            ->method('find')
            ->with($email)
            ->willReturn($user);
        $this->mockSignUpCommandFactory->expects($this->never())
            ->method('create');
        $this->commandBus->expects($this->never())
            ->method('dispatch');

        $result = $this->resolver->__invoke(
            null,
            ['args' => ['input' => $input]]
        );

        $this->assertSame($user, $result);
    }

    public function testInvokeDispatchesRegistrationAndReturnsCreatedUser(): void
    {
        $email = $this->faker->email();
        $input = $this->createInput($email);
        $user = $this->createUserFromInput($input);

        $this->setValidationExpectations($input);
        $this->setRegistrationExpectations(
            $email,
            $input['initials'],
            $input['password'],
            $user
        );

        $result = $this->resolver->__invoke(
            null,
            ['args' => ['input' => $input]]
        );

        $this->assertSame($user, $result);
    }

    public function testInvokeThrowsWhenCreatedUserCannotBeLoaded(): void
    {
        $email = $this->faker->email();
        $input = $this->createInput($email);
        $command = $this->signUpCommandFactory->create(
            $email,
            $input['initials'],
            $input['password']
        );

        $this->expectException(UserNotFoundException::class);
        $this->setValidationExpectations($input);
        $this->setMissingCreatedUserExpectations($email, $command);

        $this->resolver->__invoke(null, ['args' => ['input' => $input]]);
    }

    /**
     * @return array{email:string,initials:string,password:string}
     */
    private function createInput(string $email): array
    {
        return [
            'email' => $email,
            'initials' => $this->faker->name(),
            'password' => $this->faker->password(),
        ];
    }

    /**
     * @param array<string,string> $input
     */
    private function setValidationExpectations(array $input): void
    {
        $this->transformer->expects($this->once())
            ->method('transform')
            ->with($input);

        $this->validator->expects($this->once())
            ->method('validate');
    }

    private function setMissingCreatedUserExpectations(
        string $email,
        RegisterUserCommand $command,
    ): void {
        $this->findUserByEmailQueryHandler->expects($this->exactly(2))
            ->method('find')
            ->with($email)
            ->willReturn(null);

        $this->mockSignUpCommandFactory->expects($this->once())
            ->method('create')
            ->willReturn($command);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($command);
    }

    private function setRegistrationExpectations(
        string $email,
        string $initials,
        string $password,
        User $user,
    ): void {
        $command =
            $this->signUpCommandFactory->create($email, $initials, $password);

        $this->findUserByEmailQueryHandler->expects($this->exactly(2))
            ->method('find')
            ->with($email)
            ->willReturnOnConsecutiveCalls(null, $user);

        $this->mockSignUpCommandFactory->expects($this->once())
            ->method('create')
            ->with($email, $initials, $password)
            ->willReturn($command);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($command);
    }

    /**
     * @param array{email:string,initials:string,password:string} $input
     */
    private function createUserFromInput(array $input): User
    {
        return $this->createUser(
            $input['email'],
            $input['initials'],
            $input['password']
        );
    }

    private function createUser(
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
}
