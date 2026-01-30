<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use App\User\Application\DTO\UserPatchDto;
use App\User\Domain\Entity\User;
use App\User\Domain\ValueObject\UserUpdate;

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
            ['initials' => 'Provided Initials', 'oldPassword' => $testData->password],
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
            ['newPassword' => 'Provided New Password', 'oldPassword' => $testData->password],
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
}
