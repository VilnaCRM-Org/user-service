<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use App\User\Application\DTO\UserPatchDto;
use App\User\Application\Factory\UpdateUserCommandFactoryInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\ValueObject\UserUpdate;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class UserPatchProcessorSuccessTest extends UserPatchProcessorTestCase
{
    public function testProcess(): void
    {
        $testData = $this->createProcessTestData();
        $this->setupProcessExpectations(
            $testData['user'],
            $testData['updateData'],
            $testData['userId']
        );

        $result = $this->executeProcessWithNewData($testData);

        $this->assertInstanceOf(User::class, $result);
    }

    public function testProcessWithoutFullParams(): void
    {
        $testData = $this->setupUserForPatchTest();
        $this->setupProcessExpectations(
            $testData->user,
            new UserUpdate(
                $testData->email,
                $testData->initials,
                $testData->password,
                $testData->password
            ),
            $testData->userId
        );
        $result = $this->withRequest(
            ['oldPassword' => $testData->password],
            fn () => $this->processor->process(
                new UserPatchDto(null, null, $testData->password, null),
                $this->mockOperation,
                ['id' => $testData->userId]
            )
        );
        $this->assertInstanceOf(User::class, $result);
    }

    public function testProcessWithInvalidEmailPreservesOriginal(): void
    {
        $testData = $this->setupUserForPatchTest();
        $result = $this->processWithInvalidInput(
            $testData->user,
            $testData->initials,
            $testData->password,
            $testData->userId
        );
        $this->assertEquals(
            $testData->email,
            $result->getEmail()
        );
    }

    public function testProcessUsesDefaultInitialsWhenDtoValueIsNull(): void
    {
        $testData = $this->setupUserForPatchTest();
        $userUpdate = new UserUpdate(
            $testData->email,
            $testData->initials,
            $testData->password,
            $testData->password
        );
        $this->setupProcessExpectations($testData->user, $userUpdate, $testData->userId);

        $result = $this->withRequest(
            ['initials' => $this->faker->name(), 'oldPassword' => $testData->password],
            fn () => $this->processor->process(
                new UserPatchDto(null, null, $testData->password, null),
                $this->mockOperation,
                ['id' => $testData->userId]
            )
        );

        $this->assertSame($testData->initials, $result->getInitials());
    }

    public function testProcessUsesDefaultPasswordWhenDtoValueIsNull(): void
    {
        $testData = $this->setupUserForPatchTest();
        $userUpdate = new UserUpdate(
            $testData->email,
            $testData->initials,
            $testData->password,
            $testData->password
        );
        $this->setupProcessExpectations($testData->user, $userUpdate, $testData->userId);

        $result = $this->withRequest(
            ['newPassword' => $this->faker->password(), 'oldPassword' => $testData->password],
            fn () => $this->processor->process(
                new UserPatchDto(null, null, $testData->password, null),
                $this->mockOperation,
                ['id' => $testData->userId]
            )
        );

        $this->assertSame($testData->password, $result->getPassword());
    }

    public function testProcessWithoutCurrentRequestUsesExistingValues(): void
    {
        $testData = $this->setupUserForPatchTest();
        $this->setupProcessExpectations(
            $testData->user,
            new UserUpdate(
                $testData->email,
                $testData->initials,
                $testData->password,
                $testData->password
            ),
            $testData->userId
        );

        $result = $this->processor->process(
            new UserPatchDto(null, null, $testData->password, null),
            $this->mockOperation,
            ['id' => $testData->userId]
        );

        $this->assertSame($testData->user, $result);
    }

    public function testProcessWithEmptyRequestBody(): void
    {
        $testData = $this->setupUserForPatchTest();
        $this->setupProcessExpectations(
            $testData->user,
            new UserUpdate(
                $testData->email,
                $testData->initials,
                $testData->password,
                $testData->password
            ),
            $testData->userId
        );

        $result = $this->withRawRequest(
            '',
            fn () => $this->processor->process(
                new UserPatchDto(null, null, $testData->password, null),
                $this->mockOperation,
                ['id' => $testData->userId]
            )
        );

        $this->assertSame($testData->user, $result);
    }

    public function testProcessWithNullSecurityToken(): void
    {
        $testData = $this->setupUserForPatchTest();
        $userUpdate = new UserUpdate(
            $testData->email,
            $testData->initials,
            $testData->password,
            $testData->password
        );

        $this->getUserQueryHandler->expects($this->once())
            ->method('handle')
            ->with($testData->userId)
            ->willReturn($testData->user);

        $this->security->expects($this->once())
            ->method('getToken')
            ->willReturn(null);

        $command = $this->updateUserCommandFactory->create($testData->user, $userUpdate, '');
        $this->mockUpdateUserCommandFactory->expects($this->once())
            ->method('create')
            ->with(
                $testData->user,
                $this->callback(static fn (UserUpdate $u): bool => $u->newEmail === $userUpdate->newEmail),
                ''
            )
            ->willReturn($command);

        $this->commandBus->expects($this->once())->method('dispatch')->with($command);

        $result = $this->withRequest(
            ['oldPassword' => $testData->password],
            fn () => $this->processor->process(
                new UserPatchDto(null, null, $testData->password, null),
                $this->mockOperation,
                ['id' => $testData->userId]
            )
        );

        $this->assertInstanceOf(User::class, $result);
    }

    public function testProcessWithNonStringSessionId(): void
    {
        $testData = $this->setupUserForPatchTest();
        $userUpdate = new UserUpdate(
            $testData->email,
            $testData->initials,
            $testData->password,
            $testData->password
        );

        $this->getUserQueryHandler->expects($this->once())
            ->method('handle')
            ->with($testData->userId)
            ->willReturn($testData->user);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getAttribute')->with('sid')->willReturn(null);
        $this->security->expects($this->once())->method('getToken')->willReturn($token);

        $command = $this->updateUserCommandFactory->create($testData->user, $userUpdate, '');
        $this->mockUpdateUserCommandFactory->expects($this->once())
            ->method('create')
            ->with(
                $testData->user,
                $this->callback(static fn (UserUpdate $u): bool => $u->newEmail === $userUpdate->newEmail),
                ''
            )
            ->willReturn($command);

        $this->commandBus->expects($this->once())->method('dispatch')->with($command);

        $result = $this->withRequest(
            ['oldPassword' => $testData->password],
            fn () => $this->processor->process(
                new UserPatchDto(null, null, $testData->password, null),
                $this->mockOperation,
                ['id' => $testData->userId]
            )
        );

        $this->assertInstanceOf(User::class, $result);
    }
}
