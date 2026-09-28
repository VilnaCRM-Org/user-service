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

    public function expectBatchDelete(): void
    {
        $this->repository->expects($this->testCase->once())
            ->method('deleteBatch')
            ->with($this->testCase->callback(
                static fn (mixed $collection): bool => $collection instanceof UserCollection
                    && $collection->users !== []
                    && array_is_list($collection->users)
                    && array_all(
                        $collection->users,
                        static fn (mixed $user): bool => $user instanceof UserInterface
                    )
            ));
    }
}
