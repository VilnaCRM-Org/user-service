<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\EventListener;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Collection\UserCollection;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Repository\UserRepositoryInterface;

final class SchemathesisCleanupListenerTestExpectations
{
    /**
     * @var callable
     */
    private $expectSequential;

    public function __construct(
        private UnitTestCase $testCase,
        private UserRepositoryInterface $repository,
        callable $expectSequential
    ) {
        $this->expectSequential = $expectSequential;
    }

    public function expectNoRepositoryCalls(): void
    {
        $this->repository->expects($this->testCase->never())->method('findByEmail');
        $this->repository->expects($this->testCase->never())->method('deleteBatch');
    }

    /**
     * @param array<int, string> $emails
     * @param array<int, UserInterface|null> $users
     */
    public function expectBatchFindByEmail(array $emails, array $users): void
    {
        $expectedCalls = array_map(
            /**
             * @return array<string>
             *
             * @psalm-return list{string}
             */
            static fn (string $email): array => [$email],
            $emails
        );

        $this->repository->expects($this->testCase->exactly(count($emails)))
            ->method('findByEmail')
            ->willReturnCallback(($this->expectSequential)($expectedCalls, $users));
    }

    /**
     * @param array<int, UserInterface> $expectedUsers
     */
    public function expectBatchDelete(array $expectedUsers): void
    {
        $expectedUsers = array_values($expectedUsers);

        $this->repository->expects($this->testCase->once())
            ->method('deleteBatch')
            ->with($this->testCase->callback(
                static fn (mixed $collection): bool => $collection instanceof UserCollection
                    && array_is_list($collection->users)
                    && $collection->users === $expectedUsers
            ));
    }
}
