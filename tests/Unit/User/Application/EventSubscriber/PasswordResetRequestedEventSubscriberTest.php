<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\EventSubscriber;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\SendPasswordResetEmailCommand;
use App\User\Application\EventSubscriber\PasswordResetRequestedEventSubscriber;
use App\User\Application\Factory\SendPasswordResetEmailCommandFactoryInterface;
use App\User\Domain\Aggregate\PasswordResetEmailInterface;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\PasswordResetRequestedEvent;
use App\User\Domain\Factory\PasswordResetEmailFactoryInterface;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;

final class PasswordResetRequestedEventSubscriberTest extends UnitTestCase
{
    private CommandBusInterface $commandBus;
    private PasswordResetTokenRepositoryInterface $tokenRepository;
    private PasswordResetEmailFactoryInterface $emailFactory;
    private SendPasswordResetEmailCommandFactoryInterface $cmdFactory;
    private UserRepositoryInterface $userRepository;
    private PasswordResetRequestedEventSubscriber $subscriber;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->tokenRepository = $this->createMock(PasswordResetTokenRepositoryInterface::class);
        $this->emailFactory = $this->createMock(PasswordResetEmailFactoryInterface::class);
        $this->cmdFactory = $this->createMock(SendPasswordResetEmailCommandFactoryInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);

        $this->subscriber = new PasswordResetRequestedEventSubscriber(
            $this->commandBus,
            $this->tokenRepository,
            $this->emailFactory,
            $this->cmdFactory,
            $this->userRepository
        );
    }

    public function testInvokeSuccessfully(): void
    {
        $tokenValue = $this->faker->sha256();
        $userId = $this->faker->uuid();
        $userEmail = $this->faker->safeEmail();
        $eventId = $this->faker->uuid();

        $mocks = $this->createMocks();
        $event = new PasswordResetRequestedEvent($userId, $userEmail, $tokenValue, $eventId);

        $this->setupSuccessfulFlow($tokenValue, $mocks);

        $this->subscriber->__invoke($event);
    }

    public function testInvokeWhenTokenNotFound(): void
    {
        $tokenValue = $this->faker->sha256();
        $userId = $this->faker->uuid();
        $userEmail = $this->faker->safeEmail();
        $eventId = $this->faker->uuid();

        $event = new PasswordResetRequestedEvent($userId, $userEmail, $tokenValue, $eventId);

        $this->tokenRepository->expects($this->once())
            ->method('findByToken')
            ->with($tokenValue)
            ->willReturn(null);

        $this->emailFactory->expects($this->never())
            ->method('create');

        $this->cmdFactory->expects($this->never())
            ->method('create');

        $this->commandBus->expects($this->never())
            ->method('dispatch');

        $this->subscriber->__invoke($event);
    }

    public function testSubscribedTo(): void
    {
        $subscribedEvents = $this->subscriber->subscribedTo();

        $this->assertIsArray($subscribedEvents);
        $this->assertContains(PasswordResetRequestedEvent::class, $subscribedEvents);
        $this->assertCount(1, $subscribedEvents);
    }

    /**
     * @return array<string, \PHPUnit\Framework\MockObject\MockObject>
     */
    private function createMocks(): array
    {
        return [
            'user' => $this->createMock(UserInterface::class),
            'token' => $this->createMock(PasswordResetTokenInterface::class),
            'passwordResetEmail' => $this->createMock(PasswordResetEmailInterface::class),
            'command' => $this->createMock(SendPasswordResetEmailCommand::class),
        ];
    }

    /**
     * @param array<string, \PHPUnit\Framework\MockObject\MockObject> $mocks
     */
    private function setupSuccessfulFlow(string $tokenValue, array $mocks): void
    {
        $this->userRepository->expects($this->once())
            ->method('findById')
            ->with($this->anything())
            ->willReturn($mocks['user']);

        $this->tokenRepository->expects($this->once())
            ->method('findByToken')
            ->with($tokenValue)
            ->willReturn($mocks['token']);

        $this->emailFactory->expects($this->once())
            ->method('create')
            ->with($mocks['token'], $mocks['user'])
            ->willReturn($mocks['passwordResetEmail']);

        $this->cmdFactory->expects($this->once())
            ->method('create')
            ->with($mocks['passwordResetEmail'])
            ->willReturn($mocks['command']);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($mocks['command']);
    }
}
