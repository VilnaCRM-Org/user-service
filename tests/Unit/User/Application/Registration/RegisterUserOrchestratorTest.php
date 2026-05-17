<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Registration;

use App\Tests\Unit\User\Application\Support\RegisterUserCommandTestCase;
use App\User\Application\Registration\RegisterUserOrchestrator;
use App\User\Domain\Exception\UserNotFoundException;

final class RegisterUserOrchestratorTest extends RegisterUserCommandTestCase
{
    private RegisterUserOrchestrator $orchestrator;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpRegisterUserCommandContext();
        $this->orchestrator = new RegisterUserOrchestrator(
            $this->commandBus,
            $this->mockSignUpCommandFactory,
            $this->findUserByEmailQueryHandler
        );
    }

    public function testRegisterReturnsExistingUserWithNormalizedEmail(): void
    {
        [$email, $normalizedEmail] = $this->createEmailFixture();
        $initials = $this->faker->name();
        $password = $this->faker->password();
        $existingUser =
            $this->createUser($normalizedEmail, $initials, $password);

        $this->findUserByEmailQueryHandler->expects($this->once())
            ->method('find')
            ->with($normalizedEmail)
            ->willReturn($existingUser);
        $this->mockSignUpCommandFactory->expects($this->never())
            ->method('create');
        $this->commandBus->expects($this->never())
            ->method('dispatch');

        $returnedUser = $this->orchestrator->register($email, $initials, $password);

        $this->assertSame($existingUser, $returnedUser);
    }

    public function testRegisterDispatchesRegistrationWithNormalizedEmail(): void
    {
        [$email, $normalizedEmail] = $this->createEmailFixture();
        $initials = $this->faker->name();
        $password = $this->faker->password();
        $signUpCommand =
            $this->signUpCommandFactory->create($normalizedEmail, $initials, $password);
        $user = $this->createUser($normalizedEmail, $initials, $password);

        $this->commandExpectationHelper->expectRegistration(
            $normalizedEmail,
            $initials,
            $password,
            $signUpCommand,
            $user
        );

        $returnedUser = $this->orchestrator->register($email, $initials, $password);

        $this->assertSame($user, $returnedUser);
    }

    public function testRegisterThrowsWhenCreatedUserCannotBeLoaded(): void
    {
        [$email, $normalizedEmail] = $this->createEmailFixture();
        $initials = $this->faker->name();
        $password = $this->faker->password();
        $signUpCommand = $this->signUpCommandFactory->create(
            $normalizedEmail,
            $initials,
            $password
        );

        $this->expectException(UserNotFoundException::class);
        $this->commandExpectationHelper->expectMissingCreatedUser(
            $normalizedEmail,
            $signUpCommand
        );

        $this->orchestrator->register($email, $initials, $password);
    }

    /**
     * @return array{string,string}
     */
    private function createEmailFixture(): array
    {
        $email = ' ' . "\u{00C4}" . strtoupper($this->faker->safeEmail()) . ' ';

        return [$email, mb_strtolower(trim($email))];
    }
}
