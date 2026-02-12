<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\EventListener;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\UserDeletedEvent;
use App\User\Domain\Factory\Event\UserDeletedEventFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Uid\Factory\UuidFactory;
use Symfony\Component\Uid\Uuid;

final class SchemathesisCleanupListenerTestExpectations
{
    /**
     * @var callable
     */
    private $expectSequential;

    public function __construct(
        private UnitTestCase $testCase,
        private UserRepositoryInterface $repository,
        private EventBusInterface $eventBus,
        private UuidFactory $uuidFactory,
        private UserDeletedEventFactoryInterface $eventFactory,
        private string $eventId,
        callable $expectSequential
    ) {
        $this->expectSequential = $expectSequential;
    }

    public function expectNoRepositoryCalls(): void
    {
        $this->repository->expects($this->testCase->never())->method('findByEmail');
        $this->repository->expects($this->testCase->never())->method('deleteBatch');
        $this->eventFactory->expects($this->testCase->never())->method('create');
        $this->eventBus->expects($this->testCase->never())->method('publish');
    }

    /**
     * @param array<int, string> $emails
     * @param array<int, UserInterface|null> $users
     */
    public function expectBatchFindByEmail(array $emails, array $users): void
    {
        $expectedCalls = array_map(
            /**
             * @return string[]
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
     * @param array<int, UserInterface> $users
     */
    public function expectBatchDeleteAndEvents(array $users): void
    {
        $this->expectBatchDelete($users);
        $this->expectUuidFactoryCreates(count($users));
        $events = $this->createEvents($users);
        $this->expectEventFactory($users, $events);
        $this->expectEventBus($events);
    }

    /**
     * @param array<int, UserInterface> $users
     */
    private function expectBatchDelete(array $users): void
    {
        $this->repository->expects($this->testCase->once())
            ->method('deleteBatch')
            ->with($users);
    }

    private function expectUuidFactoryCreates(int $count): void
    {
        $uuid = Uuid::fromString($this->eventId);

        $this->uuidFactory->expects($this->testCase->exactly($count))
            ->method('create')
            ->willReturn($uuid);
    }

    /**
     * @param array<int, UserInterface> $users
     *
     * @return array<int, UserDeletedEvent>
     */
    private function createEvents(array $users): array
    {
        return array_map(
            fn (UserInterface $user): UserDeletedEvent => new UserDeletedEvent(
                $user->getId(),
                $user->getEmail(),
                $this->eventId
            ),
            $users
        );
    }

    /**
     * @param array<int, UserInterface> $users
     * @param array<int, UserDeletedEvent> $events
     */
    private function expectEventFactory(array $users, array $events): void
    {
        $expectedArgs = array_map(
            /**
             * @return (\App\User\Domain\Entity\UserInterface|string)[]
             *
             * @psalm-return list{\App\User\Domain\Entity\UserInterface, string}
             */
            fn (UserInterface $user): array => [$user, $this->eventId],
            $users
        );

        $this->eventFactory->expects($this->testCase->exactly(count($users)))
            ->method('create')
            ->willReturnCallback(($this->expectSequential)($expectedArgs, $events));
    }

    /**
     * @param array<int, UserDeletedEvent> $events
     */
    private function expectEventBus(array $events): void
    {
        $expectedArgs = array_map(
            /**
             * @return \App\User\Domain\Event\UserDeletedEvent[]
             *
             * @psalm-return list{\App\User\Domain\Event\UserDeletedEvent}
             */
            static fn (UserDeletedEvent $event): array => [$event],
            $events
        );

        $this->eventBus->expects($this->testCase->exactly(count($events)))
            ->method('publish')
            ->willReturnCallback(($this->expectSequential)($expectedArgs));
    }
}
