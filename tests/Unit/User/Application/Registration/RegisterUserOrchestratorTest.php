<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Registration;

use App\Tests\Unit\User\Application\Support\RegisterUserCommandTestCase;
use App\User\Application\Command\RegisterUserCommand;
use App\User\Application\Query\FindUserByEmailQueryHandlerInterface;
use App\User\Application\Registration\RegisterUserOrchestrator;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Exception\DuplicateEmailException;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use Throwable;

final class RegisterUserOrchestratorTest extends RegisterUserCommandTestCase
{
    private RegisterUserOrchestrator $orchestrator;
    private FindUserByEmailQueryHandlerInterface&MockObject $findUserByEmailQueryHandler;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpRegisterUserCommandContext();
        $this->findUserByEmailQueryHandler =
            $this->createMock(FindUserByEmailQueryHandlerInterface::class);
        $this->orchestrator = new RegisterUserOrchestrator(
            $this->commandBus,
            $this->mockSignUpCommandFactory,
            $this->findUserByEmailQueryHandler
        );
    }

    public function testRegisterRejectsExistingUserWithoutDispatching(): void
    {
        [$email, $initials, $password] = $this->createInputFixture();
        $existingUser = $this->createMock(UserInterface::class);

        $this->expectEmailLookups($email, $existingUser);
        $this->mockSignUpCommandFactory->expects($this->never())
            ->method('create');
        $this->commandBus->expects($this->never())
            ->method('dispatch');

        $this->expectException(DuplicateEmailException::class);
        $this->expectExceptionMessage(
            sprintf('Email "%s" is already registered', $email)
        );

        $this->orchestrator->register(
            $email,
            $initials,
            $password
        );
    }

    public function testRegisterDispatchesAndReturnsPostDispatchUser(): void
    {
        [$email, $initials, $password] = $this->createInputFixture();
        $signUpCommand =
            $this->signUpCommandFactory->create($email, $initials, $password);
        $createdUser = $this->createUser($email, $initials, $password);

        $this->expectEmailLookups($email, null, $createdUser);
        $this->commandExpectationHelper->expectRegistration(
            $email,
            $initials,
            $password,
            $signUpCommand
        );

        $returnedUser = $this->orchestrator->register(
            $email,
            $initials,
            $password
        );

        $this->assertSame($createdUser, $returnedUser);
    }

    public function testRegisterRethrowsDuplicateEmailFailureWithoutRaceLookup(): void
    {
        [$email, $initials, $password] = $this->createInputFixture();
        $signUpCommand =
            $this->signUpCommandFactory->create($email, $initials, $password);
        $error = new DuplicateEmailException($email);

        $this->expectEmailLookups($email, null);
        $this->expectDispatchFailure(
            $email,
            $initials,
            $password,
            $signUpCommand,
            $error
        );

        $this->expectExceptionObject($error);

        $this->orchestrator->register($email, $initials, $password);
    }

    public function testRegisterRethrowsNonDuplicateDispatchFailureWithoutRaceLookup(): void
    {
        [$email, $initials, $password] = $this->createInputFixture();
        $signUpCommand =
            $this->signUpCommandFactory->create($email, $initials, $password);
        $error = new RuntimeException('Event publish failed.');

        $this->expectEmailLookups($email, null);
        $this->expectDispatchFailure(
            $email,
            $initials,
            $password,
            $signUpCommand,
            $error
        );

        $this->expectExceptionObject($error);

        $this->orchestrator->register($email, $initials, $password);
    }

    public function testRegisterThrowsWhenPostDispatchUserCannotBeLoaded(): void
    {
        [$email, $initials, $password] = $this->createInputFixture();
        $signUpCommand =
            $this->signUpCommandFactory->create($email, $initials, $password);

        $this->expectEmailLookups($email, null, null);
        $this->commandExpectationHelper->expectRegistration(
            $email,
            $initials,
            $password,
            $signUpCommand
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Registered user could not be loaded.');

        $this->orchestrator->register($email, $initials, $password);
    }

    /**
     * @return array{string,string,string}
     */
    private function createInputFixture(): array
    {
        return [
            $this->faker->email(),
            $this->faker->name(),
            $this->faker->password(),
        ];
    }

    private function expectEmailLookups(
        string $email,
        ?UserInterface ...$results,
    ): void {
        $this->findUserByEmailQueryHandler
            ->expects($this->exactly(count($results)))
            ->method('find')
            ->with($email)
            ->willReturnOnConsecutiveCalls(...$results);
    }

    private function expectDispatchFailure(
        string $email,
        string $initials,
        string $password,
        RegisterUserCommand $signUpCommand,
        Throwable $error,
    ): void {
        $this->mockSignUpCommandFactory->expects($this->once())
            ->method('create')
            ->with($email, $initials, $password)
            ->willReturn($signUpCommand);
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($signUpCommand)
            ->willThrowException($error);
    }
}
