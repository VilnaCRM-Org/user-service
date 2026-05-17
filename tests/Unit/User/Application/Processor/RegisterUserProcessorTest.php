<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\UserRegisterDto;
use App\User\Application\Processor\RegisterUserProcessor;
use App\User\Application\Registration\RegisterUserOrchestrator;
use App\User\Domain\Entity\UserInterface;
use PHPUnit\Framework\MockObject\MockObject;

final class RegisterUserProcessorTest extends UnitTestCase
{
    private Operation&MockObject $mockOperation;
    private RegisterUserOrchestrator&MockObject $registerUserOrchestrator;
    private RegisterUserProcessor $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockOperation = $this->createMock(Operation::class);
        $this->registerUserOrchestrator =
            $this->createMock(RegisterUserOrchestrator::class);
        $this->processor =
            new RegisterUserProcessor($this->registerUserOrchestrator);
    }

    public function testProcessDelegatesRegistration(): void
    {
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();
        $userRegisterDto = new UserRegisterDto($email, $initials, $password);
        $user = $this->createMock(UserInterface::class);

        $this->registerUserOrchestrator->expects($this->once())
            ->method('register')
            ->with($email, $initials, $password)
            ->willReturn($user);

        $returnedUser =
            $this->processor->process($userRegisterDto, $this->mockOperation);

        $this->assertSame($user, $returnedUser);
    }
}
