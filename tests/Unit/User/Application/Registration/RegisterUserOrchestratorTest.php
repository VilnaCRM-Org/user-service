<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Registration;

use App\Tests\Unit\User\Application\Support\RegisterUserCommandTestCase;
use App\User\Application\Registration\RegisterUserOrchestrator;

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
            $this->commandResponseTypeGuard
        );
    }

    public function testRegisterDispatchesRegistrationAndReturnsCommandResponseUser(): void
    {
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();
        $signUpCommand =
            $this->signUpCommandFactory->create($email, $initials, $password);
        $user = $this->createUser($email, $initials, $password);

        $this->commandExpectationHelper->expectRegistration(
            $email,
            $initials,
            $password,
            $signUpCommand,
            $user
        );

        $returnedUser = $this->orchestrator->register(
            $email,
            $initials,
            $password
        );

        $this->assertSame($user, $returnedUser);
    }
}
