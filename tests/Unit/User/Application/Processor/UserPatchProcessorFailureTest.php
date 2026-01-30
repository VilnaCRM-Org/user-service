<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use App\User\Application\DTO\UserPatchDto;
use App\User\Domain\Exception\UserNotFoundException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class UserPatchProcessorFailureTest extends UserPatchProcessorTestCase
{
    public function testProcessWithSpacesPassed(): void
    {
        $testData = $this->setupUserForPatchTest();
        $this->setupNoUpdateExpectations($testData);

        $this->expectException(BadRequestHttpException::class);

        $payload = [
            'email' => ' ',
            'initials' => ' ',
            'oldPassword' => $testData->password,
            'newPassword' => ' ',
        ];
        $this->withRequest(
            $payload,
            fn () => $this->processor->process(
                new UserPatchDto(' ', ' ', $testData->password, ' '),
                $this->mockOperation,
                ['id' => $testData->userId]
            )
        );
    }

    public function testProcessWithBlankInitialsThrowsBadRequest(): void
    {
        $testData = $this->setupUserForPatchTest();
        $this->setupNoUpdateExpectations($testData);

        $this->expectException(BadRequestHttpException::class);

        $payload = ['initials' => ' ', 'oldPassword' => $testData->password];
        $this->withRequest(
            $payload,
            fn () => $this->processor->process(
                new UserPatchDto(null, ' ', $testData->password, null),
                $this->mockOperation,
                ['id' => $testData->userId]
            )
        );
    }

    public function testProcessUserNotFound(): void
    {
        $testData = $this->setupUserForPatchTest();
        $this->getUserQueryHandler->expects($this->once())->method('handle')
            ->with($testData->userId)->willThrowException(new UserNotFoundException());
        $this->expectException(UserNotFoundException::class);

        $payload = ['oldPassword' => $testData->password];
        $this->withRequest(
            $payload,
            fn () => $this->processor->process(
                new UserPatchDto(null, null, $testData->password, null),
                $this->mockOperation,
                ['id' => $testData->userId]
            )
        );
    }

    public function testProcessThrowsWhenExplicitNullProvided(): void
    {
        $testData = $this->setupUserForPatchTest();

        $this->getUserQueryHandler->expects($this->never())
            ->method('handle');

        $this->expectException(BadRequestHttpException::class);

        $this->withRawRequest(
            json_encode(
                ['email' => null, 'oldPassword' => $testData->password],
                JSON_THROW_ON_ERROR
            ),
            fn () => $this->processor->process(
                new UserPatchDto(null, null, $testData->password, null),
                $this->mockOperation,
                ['id' => $testData->userId]
            )
        );
    }

    public function testProcessThrowsOnInvalidJsonBody(): void
    {
        $testData = $this->setupUserForPatchTest();

        $this->getUserQueryHandler->expects($this->never())
            ->method('handle');

        $this->expectException(BadRequestHttpException::class);

        $this->withRawRequest(
            '{invalid',
            fn () => $this->processor->process(
                new UserPatchDto(null, null, $testData->password, null),
                $this->mockOperation,
                ['id' => $testData->userId]
            )
        );
    }

    public function testProcessThrowsOnNonArrayJsonPayload(): void
    {
        $testData = $this->setupUserForPatchTest();

        $this->getUserQueryHandler->expects($this->never())
            ->method('handle');

        $this->expectException(BadRequestHttpException::class);

        $this->withRawRequest(
            '"string"',
            fn () => $this->processor->process(
                new UserPatchDto(null, null, $testData->password, null),
                $this->mockOperation,
                ['id' => $testData->userId]
            )
        );
    }
}
