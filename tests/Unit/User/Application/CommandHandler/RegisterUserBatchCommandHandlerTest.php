<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\UuidInterface;
use App\Shared\Infrastructure\Factory\UuidFactory as UuidFactoryInterface;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RegisterUserBatchCommand;
use App\User\Application\Command\RegisterUserBatchCommandResponse;
use App\User\Application\CommandHandler\RegisterUserBatchCommandHandler;
use App\User\Domain\Collection\UserCollection;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\UserRegisteredEvent;
use App\User\Domain\Factory\Event\UserRegisteredEventFactoryInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Uid\Factory\UuidFactory;

final class RegisterUserBatchCommandHandlerTest extends UnitTestCase
{
    private const BATCH_SIZE = 2;

    private PasswordHasherFactoryInterface $hasherFactory;
    private UserRepositoryInterface $userRepository;
    private EventBusInterface $eventBus;
    private UuidFactory $uuidFactory;
    private UserFactory $userFactory;
    private UuidTransformer $transformer;
    private UuidTransformer $mockTransformer;
    private UserRegisteredEventFactoryInterface $registeredEventFactory;
    private RegisterUserBatchCommandHandler $handler;
    private PasswordHasherInterface $hasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hasherFactory =
            $this->createMock(PasswordHasherFactoryInterface::class);
        $this->userRepository =
            $this->createMock(UserRepositoryInterface::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->uuidFactory = $this->createMock(UuidFactory::class);
        $this->userFactory = new UserFactory();
        $this->transformer = new UuidTransformer(new UuidFactoryInterface());
        $this->mockTransformer = $this->createMock(UuidTransformer::class);
        $this->registeredEventFactory =
            $this->createMock(UserRegisteredEventFactoryInterface::class);
        $this->hasher = $this->createMock(PasswordHasherInterface::class);

        $this->setHandler();
    }

    public function testInvoke(): void
    {
        $hashedPasswords = [];
        $uuids = [];
        $usersData = [];
        $users = [];

        $this->prepareData($hashedPasswords, $uuids, $users, $usersData);

        $command = new RegisterUserBatchCommand(new UserCollection($usersData));

        $this->setExpectations($uuids, $hashedPasswords, $users);

        $this->handler->__invoke($command);

        $response = $command->getResponse();
        $this->assertInstanceOf(
            RegisterUserBatchCommandResponse::class,
            $response
        );
        $this->assertCount(self::BATCH_SIZE, $response->users);
        $this->assertEquals($users[0], $response->users[0]);
        $this->assertEquals($users[1], $response->users[1]);
    }

    /**
     * Prepare data for testing
     *
     * @param array<string> $hashedPasswords
     * @param array<UuidInterface> $uuids
     * @param array<UserInterface> $users
     * @param array<string, array<string>> $usersData
     */
    private function prepareData(
        array &$hashedPasswords,
        array &$uuids,
        array &$users,
        array &$usersData
    ): void {
        for ($i = 0; $i < self::BATCH_SIZE; ++$i) {
            $hashedPassword = $this->faker->password();
            $uuid =
                $this->transformer->transformFromString($this->faker->uuid());
            $email = $this->faker->email();
            $initials = $this->faker->word();

            $usersData[] = [
                'email' => $email,
                'initials' => $initials,
                'password' => $this->faker->password(),
            ];
            $users[] = $this->userFactory->create(
                $email,
                $initials,
                $hashedPassword,
                $uuid
            );

            $hashedPasswords[] = $hashedPassword;
            $uuids[] = $uuid;
        }
    }

    /**
     * Set expectations for the test
     *
     * @param array<UuidInterface> $uuids
     * @param array<string> $hashedPasswords
     * @param array<UserInterface> $users
     */
    private function setExpectations(
        array $uuids,
        array $hashedPasswords,
        array $users
    ): void {
        $this->mockTransformer->expects($this->exactly(self::BATCH_SIZE))
            ->method('transformFromSymfonyUuid')
            ->willReturnOnConsecutiveCalls(...$uuids);

        $this->setExpectationsForHasher($hashedPasswords);

        $events = [];
        for ($i = 0; $i < self::BATCH_SIZE; ++$i) {
            $events[] = $this->createMock(UserRegisteredEvent::class);
        }

        $this->registeredEventFactory->expects($this->exactly(self::BATCH_SIZE))
            ->method('create')
            ->willReturnOnConsecutiveCalls(...$events);

        $this->userRepository->expects($this->once())
            ->method('saveBatch')
            ->with($users);
        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with(...$events);
    }

    /**
     * Set expectations for the password hasher
     *
     * @param array<string> $hashedPasswords
     */
    private function setExpectationsForHasher(array $hashedPasswords): void
    {
        $this->hasher->expects($this->exactly(self::BATCH_SIZE))
            ->method('hash')
            ->willReturnOnConsecutiveCalls(...$hashedPasswords);
        $this->hasherFactory->expects($this->exactly(self::BATCH_SIZE))
            ->method('getPasswordHasher')
            ->willReturn($this->hasher);
    }

    /**
     * Initialize the command handler
     */
    private function setHandler(): void
    {
        $this->handler = new RegisterUserBatchCommandHandler(
            $this->hasherFactory,
            $this->userRepository,
            $this->eventBus,
            $this->uuidFactory,
            $this->userFactory,
            $this->mockTransformer,
            $this->registeredEventFactory
        );
    }
}
