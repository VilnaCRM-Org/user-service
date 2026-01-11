<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RequestPasswordResetCommand;
use App\User\Application\Command\RequestPasswordResetCommandResponse;
use App\User\Application\CommandHandler\RequestPasswordResetCommandHandler;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\PasswordResetRequestedEvent;
use App\User\Domain\Factory\Event\PasswordResetRequestedEventFactoryInterface;
use App\User\Domain\Factory\PasswordResetTokenFactoryInterface;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Uid\Factory\UuidFactory;
use Symfony\Component\Uid\Uuid;

final class RequestPasswordResetCommandHandlerTest extends UnitTestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private PasswordResetTokenRepositoryInterface&MockObject $passwordResetTokenRepository;
    private PasswordResetTokenFactoryInterface&MockObject $passwordResetTokenFactory;
    private EventBusInterface&MockObject $eventBus;
    private UuidFactory&MockObject $uuidFactory;
    private PasswordResetRequestedEventFactoryInterface&MockObject $eventFactory;

    private RequestPasswordResetCommandHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->passwordResetTokenRepository = $this->createMock(
            PasswordResetTokenRepositoryInterface::class
        );
        $this->passwordResetTokenFactory = $this->createMock(
            PasswordResetTokenFactoryInterface::class
        );
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->uuidFactory = $this->createMock(UuidFactory::class);
        $this->eventFactory = $this->createMock(PasswordResetRequestedEventFactoryInterface::class);

        $this->handler = new RequestPasswordResetCommandHandler(
            $this->userRepository,
            $this->passwordResetTokenRepository,
            $this->passwordResetTokenFactory,
            $this->eventBus,
            $this->uuidFactory,
            $this->eventFactory,
        );
    }

    public function testRequestPasswordResetForExistingUser(): void
    {
        $testData = $this->createPasswordResetTestData();
        $mocks = $this->createPasswordResetMocks($testData);

        $this->setupPasswordResetExpectations($testData, $mocks);

        $command = new RequestPasswordResetCommand($testData['email']);
        $this->handler->__invoke($command);

        $this->assertPasswordResetResponse($command);
    }

    public function testRequestPasswordResetForNonExistingUser(): void
    {
        $email = $this->faker->email();

        $this->setupUserNotFoundExpectations($email);
        $this->setupNeverCalledExpectations();

        $command = new RequestPasswordResetCommand($email);
        $this->handler->__invoke($command);

        $this->assertInstanceOf(
            RequestPasswordResetCommandResponse::class,
            $command->getResponse()
        );
    }

    private function setupUserNotFoundExpectations(string $email): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);
    }

    private function setupNeverCalledExpectations(): void
    {
        $this->passwordResetTokenFactory
            ->expects($this->never())
            ->method('create');

        $this->eventFactory
            ->expects($this->never())
            ->method('create');

        $this->eventBus
            ->expects($this->never())
            ->method('publish');
    }

    /**
     * @return array<string, string|Uuid>
     */
    private function createPasswordResetTestData(): array
    {
        return [
            'email' => $this->faker->email(),
            'userId' => $this->faker->uuid(),
            'tokenValue' => $this->faker->sha256(),
            'uuid' => Uuid::fromString($this->faker->uuid()),
        ];
    }

    /**
     * @param array<string, string|Uuid> $testData
     *
     * @return array<string, PasswordResetTokenInterface|UserInterface|PasswordResetRequestedEvent>
     */
    private function createPasswordResetMocks(array $testData): array
    {
        $token = $this->createMock(PasswordResetTokenInterface::class);
        $token->method('getTokenValue')->willReturn($testData['tokenValue']);

        $user = $this->createMock(UserInterface::class);
        $user->method('getId')->willReturn($testData['userId']);

        $event = $this->createMock(PasswordResetRequestedEvent::class);

        return [
            'token' => $token,
            'user' => $user,
            'event' => $event,
        ];
    }

    /**
     * @param array<string, string|Uuid> $testData
     * @param array<string, PasswordResetTokenInterface|UserInterface|PasswordResetRequestedEvent> $mocks
     */
    private function setupPasswordResetExpectations(array $testData, array $mocks): void
    {
        $this->setupUserRepositoryExpectations($testData['email'], $mocks['user']);
        $this->setupTokenFactoryExpectations($testData['userId'], $mocks['token']);
        $this->setupEventExpectations($testData, $mocks);
    }

    private function setupUserRepositoryExpectations(string $email, UserInterface $user): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);
    }

    private function setupTokenFactoryExpectations(
        string $userId,
        PasswordResetTokenInterface $token
    ): void {
        $this->passwordResetTokenFactory
            ->expects($this->once())
            ->method('create')
            ->with($userId)
            ->willReturn($token);

        $this->passwordResetTokenRepository
            ->expects($this->once())
            ->method('save')
            ->with($token);
    }

    /**
     * @param array<string, string|Uuid> $testData
     * @param array<string, PasswordResetTokenInterface|UserInterface|PasswordResetRequestedEvent> $mocks
     */
    private function setupEventExpectations(array $testData, array $mocks): void
    {
        $this->uuidFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($testData['uuid']);

        $this->eventFactory
            ->expects($this->once())
            ->method('create')
            ->with($mocks['user'], $testData['tokenValue'], (string) $testData['uuid'])
            ->willReturn($mocks['event']);

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($mocks['event']);
    }

    private function assertPasswordResetResponse(RequestPasswordResetCommand $command): void
    {
        $this->assertInstanceOf(
            RequestPasswordResetCommandResponse::class,
            $command->getResponse()
        );
    }
}
