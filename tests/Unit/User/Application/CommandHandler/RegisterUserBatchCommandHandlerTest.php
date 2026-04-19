<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Infrastructure\Factory\UuidFactory as UuidFactoryInterface;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RegisterUserBatchCommand;
use App\User\Application\CommandHandler\RegisterUserBatchCommandHandler;
use App\User\Application\DTO\RegisterUserBatchCommandResponse;
use App\User\Application\Factory\BatchUserRegistrationFactory;
use App\User\Domain\Collection\UserCollection;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Event\UserRegisteredEvent;
use App\User\Domain\Factory\Event\UserRegisteredEventFactoryInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Uid\Factory\UuidFactory;
use Symfony\Component\Uid\UuidV4;

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

    #[\Override]
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
        $testData = $this->createBatchRegistrationTestData();
        $command = new RegisterUserBatchCommand(
            new UserCollection($testData['usersData'])
        );

        $this->expectSuccessfulBatchRegistration($testData);

        $this->handler->__invoke($command);

        $this->assertCreatedUsersResponse($command, $testData['users']);
    }

    public function testInvokeReturnsEmptyResponseForEmptyBatch(): void
    {
        $command = new RegisterUserBatchCommand(new UserCollection());

        $this->userRepository->expects($this->never())
            ->method('findByEmails');
        $this->hasherFactory->expects($this->never())
            ->method('getPasswordHasher');
        $this->userRepository->expects($this->never())
            ->method('saveBatch');
        $this->eventBus->expects($this->never())
            ->method('publish');

        $this->handler->__invoke($command);

        $response = $command->getResponse();
        $this->assertInstanceOf(RegisterUserBatchCommandResponse::class, $response);
        $this->assertCount(0, $response->users);
    }

    public function testInvokeReturnsExistingUsersWhenAlreadyRegistered(): void
    {
        $testData = $this->createExistingUserTestData();
        $existingUser = $testData['existingUser'];
        $email = $testData['email'];
        $command = $this->createBatchCommandWithUser($testData);

        $this->setupExistingUserBatchExpectations($email, $existingUser);
        $this->setupNeverCalledForBatchRegistration();

        $this->handler->__invoke($command);

        $this->assertBatchResponse($command, $existingUser);
    }

    public function testInvokeDeduplicatesNewUsersWithinSameBatch(): void
    {
        $testData = $this->createDuplicateBatchRegistrationTestData();
        $command = new RegisterUserBatchCommand(
            new UserCollection($testData['usersData'])
        );

        $this->expectDuplicateBatchRegistration($testData);

        $this->handler->__invoke($command);

        $this->assertDuplicateBatchResponse($command);
    }

    /**
     * Initialize the command handler
     */
    private function setHandler(): void
    {
        $batchUserRegistrationFactory = new BatchUserRegistrationFactory(
            $this->hasherFactory,
            $this->uuidFactory,
            $this->userFactory,
            $this->mockTransformer,
            $this->registeredEventFactory
        );

        $this->handler = new RegisterUserBatchCommandHandler(
            $this->userRepository,
            $this->eventBus,
            $batchUserRegistrationFactory
        );
    }

    /**
     * @return array{
     *     events: list<UserRegisteredEvent>,
     *     hashedPasswords: list<string>,
     *     symfonyUuids: list<UuidV4>,
     *     userIds: list<\App\Shared\Domain\ValueObject\Uuid>,
     *     users: list<UserInterface>,
     *     usersData: list<array{email: string, initials: string, password: string}>
     * }
     */
    private function createBatchRegistrationTestData(): array
    {
        $testData = [
            'events' => [],
            'hashedPasswords' => [],
            'symfonyUuids' => [],
            'userIds' => [],
            'users' => [],
            'usersData' => [],
        ];

        for ($i = 0; $i < self::BATCH_SIZE; ++$i) {
            $this->appendBatchRegistrationFixtures($testData);
        }

        return $testData;
    }

    /**
     * @param array{
     *     events: list<UserRegisteredEvent>,
     *     hashedPasswords: list<string>,
     *     symfonyUuids: list<UuidV4>,
     *     userIds: list<\App\Shared\Domain\ValueObject\Uuid>,
     *     users: list<UserInterface>,
     *     usersData: list<array{email: string, initials: string, password: string}>
     * } $testData
     */
    private function appendBatchRegistrationFixtures(array &$testData): void
    {
        $hashedPassword = $this->faker->password();
        $email = $this->faker->email();
        $initials = $this->faker->word();
        $userId = $this->transformer->transformFromString($this->faker->uuid());

        $testData['usersData'][] = [
            'email' => $email,
            'initials' => $initials,
            'password' => $this->faker->password(),
        ];
        $testData['users'][] = $this->userFactory->create(
            $email,
            $initials,
            $hashedPassword,
            $userId
        );
        $testData['events'][] = $this->createMock(UserRegisteredEvent::class);
        $testData['hashedPasswords'][] = $hashedPassword;
        $testData['userIds'][] = $userId;
        $testData['symfonyUuids'][] = new UuidV4();
        $testData['symfonyUuids'][] = new UuidV4();
    }

    /**
     * @param array{
     *     events: list<UserRegisteredEvent>,
     *     hashedPasswords: list<string>,
     *     symfonyUuids: list<UuidV4>,
     *     userIds: list<\App\Shared\Domain\ValueObject\Uuid>,
     *     users: list<UserInterface>,
     *     usersData: list<array{email: string, initials: string, password: string}>
     * } $testData
     */
    private function expectSuccessfulBatchRegistration(array $testData): void
    {
        $this->expectBatchLookup($testData['usersData']);
        $this->expectBatchCreation($testData);
        $this->expectBatchPersistenceAndEvents(
            $testData['users'],
            $testData['events']
        );
    }

    /**
     * @param list<UserInterface> $users
     */
    private function assertCreatedUsersResponse(
        RegisterUserBatchCommand $command,
        array $users
    ): void {
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
     * @param list<array{email: string, initials: string, password: string}> $usersData
     */
    private function expectBatchLookup(array $usersData): void
    {
        $this->userRepository->expects($this->once())
            ->method('findByEmails')
            ->with(array_column($usersData, 'email'))
            ->willReturn(new UserCollection());
    }

    /**
     * @param array{
     *     events: list<UserRegisteredEvent>,
     *     hashedPasswords: list<string>,
     *     symfonyUuids: list<UuidV4>,
     *     userIds: list<\App\Shared\Domain\ValueObject\Uuid>,
     *     users: list<UserInterface>,
     *     usersData: list<array{email: string, initials: string, password: string}>
     * } $testData
     */
    private function expectBatchCreation(array $testData): void
    {
        $this->uuidFactory->expects($this->exactly(self::BATCH_SIZE * 2))
            ->method('create')
            ->willReturnOnConsecutiveCalls(...$testData['symfonyUuids']);
        $this->mockTransformer->expects($this->exactly(self::BATCH_SIZE))
            ->method('transformFromSymfonyUuid')
            ->willReturnOnConsecutiveCalls(...$testData['userIds']);
        $this->hasherFactory->expects($this->once())
            ->method('getPasswordHasher')
            ->with(User::class)
            ->willReturn($this->hasher);
        $this->hasher->expects($this->exactly(self::BATCH_SIZE))
            ->method('hash')
            ->willReturnOnConsecutiveCalls(...$testData['hashedPasswords']);
        $this->registeredEventFactory->expects($this->exactly(self::BATCH_SIZE))
            ->method('create')
            ->with($this->isInstanceOf(UserInterface::class), $this->isType('string'))
            ->willReturnOnConsecutiveCalls(...$testData['events']);
    }

    /**
     * @param list<UserInterface> $users
     * @param list<UserRegisteredEvent> $events
     */
    private function expectBatchPersistenceAndEvents(
        array $users,
        array $events
    ): void {
        $this->userRepository->expects($this->once())
            ->method('saveBatch')
            ->with($this->callback(
                fn (UserCollection $collection): bool => $this->assertSavedUsers(
                    $collection,
                    $users
                )
            ));
        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with(...$events);
    }

    /**
     * @param list<UserInterface> $expectedUsers
     */
    private function assertSavedUsers(
        UserCollection $collection,
        array $expectedUsers
    ): bool {
        $this->assertEquals($expectedUsers, iterator_to_array($collection));

        return true;
    }

    /**
     * @return array{
     *     createdUser: UserInterface,
     *     event: UserRegisteredEvent,
     *     hashedPassword: string,
     *     userId: \App\Shared\Domain\ValueObject\Uuid,
     *     usersData: list<array{email: string, initials: string, password: string}>
     * }
     */
    private function createDuplicateBatchRegistrationTestData(): array
    {
        $email = $this->faker->email();
        $initials = $this->faker->word();
        $hashedPassword = $this->faker->password();
        $userId = $this->transformer->transformFromString($this->faker->uuid());

        return [
            'createdUser' => $this->createUserWithCredentials(
                $email,
                $initials,
                $hashedPassword,
                $userId
            ),
            'event' => $this->createMock(UserRegisteredEvent::class),
            'hashedPassword' => $hashedPassword,
            'userId' => $userId,
            'usersData' => $this->createDuplicateUsersData($email, $initials),
        ];
    }

    /**
     * @param array{
     *     createdUser: UserInterface,
     *     event: UserRegisteredEvent,
     *     hashedPassword: string,
     *     userId: \App\Shared\Domain\ValueObject\Uuid,
     *     usersData: list<array{email: string, initials: string, password: string}>
     * } $testData
     */
    private function expectDuplicateBatchRegistration(array $testData): void
    {
        $this->expectBatchLookup($testData['usersData']);
        $this->expectDuplicateBatchCreation($testData);
        $this->expectDuplicateBatchPersistence(
            $testData['createdUser'],
            $testData['event']
        );
    }

    /**
     * @param array{
     *     createdUser: UserInterface,
     *     event: UserRegisteredEvent,
     *     hashedPassword: string,
     *     userId: \App\Shared\Domain\ValueObject\Uuid,
     *     usersData: list<array{email: string, initials: string, password: string}>
     * } $testData
     */
    private function expectDuplicateBatchCreation(array $testData): void
    {
        $this->uuidFactory->expects($this->exactly(2))
            ->method('create')
            ->willReturnOnConsecutiveCalls(new UuidV4(), new UuidV4());
        $this->mockTransformer->expects($this->once())
            ->method('transformFromSymfonyUuid')
            ->willReturn($testData['userId']);
        $this->hasherFactory->expects($this->once())
            ->method('getPasswordHasher')
            ->with(User::class)
            ->willReturn($this->hasher);
        $this->hasher->expects($this->once())
            ->method('hash')
            ->willReturn($testData['hashedPassword']);
        $this->registeredEventFactory->expects($this->once())
            ->method('create')
            ->with($this->isInstanceOf(UserInterface::class), $this->isType('string'))
            ->willReturn($testData['event']);
    }

    private function expectDuplicateBatchPersistence(
        UserInterface $createdUser,
        UserRegisteredEvent $event
    ): void {
        $this->userRepository->expects($this->once())
            ->method('saveBatch')
            ->with($this->callback(
                fn (UserCollection $collection): bool => $this->assertSavedUsers(
                    $collection,
                    [$createdUser]
                )
            ));
        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($event);
    }

    private function assertDuplicateBatchResponse(
        RegisterUserBatchCommand $command
    ): void {
        $response = $command->getResponse();
        $this->assertInstanceOf(RegisterUserBatchCommandResponse::class, $response);
        $users = iterator_to_array($response->users);
        $this->assertCount(2, $users);
        $this->assertSame($users[0], $users[1]);
    }

    /**
     * @return list<array{email: string, initials: string, password: string}>
     */
    private function createDuplicateUsersData(
        string $email,
        string $initials
    ): array {
        return [
            [
                'email' => $email,
                'initials' => $initials,
                'password' => $this->faker->password(),
            ],
            [
                'email' => $email,
                'initials' => $this->faker->word(),
                'password' => $this->faker->password(),
            ],
        ];
    }

    private function createUserWithCredentials(
        string $email,
        string $initials,
        string $hashedPassword,
        \App\Shared\Domain\ValueObject\Uuid $userId
    ): UserInterface {
        return $this->userFactory->create(
            $email,
            $initials,
            $hashedPassword,
            $userId
        );
    }

    /**
     * @return array<\App\User\Domain\Entity\User|string>
     *
     * @psalm-return array{password: string, email: string, initials: string, existingUser: \App\User\Domain\Entity\User}
     */
    private function createExistingUserTestData(): array
    {
        $password = $this->faker->password();
        $email = $this->faker->email();
        $initials = $this->faker->word();
        $userId = $this->transformer->transformFromString($this->faker->uuid());
        $existingUser = $this->userFactory->create($email, $initials, $password, $userId);

        return [
            'password' => $password,
            'email' => $email,
            'initials' => $initials,
            'existingUser' => $existingUser,
        ];
    }

    /**
     * @param array<string, string|UserInterface> $testData
     */
    private function createBatchCommandWithUser(array $testData): RegisterUserBatchCommand
    {
        return new RegisterUserBatchCommand(
            new UserCollection([
                [
                    'email' => $testData['email'],
                    'initials' => $testData['initials'],
                    'password' => $testData['password'],
                ],
            ])
        );
    }

    private function setupExistingUserBatchExpectations(
        string $email,
        UserInterface $existingUser
    ): void {
        $this->userRepository->expects($this->once())
            ->method('findByEmails')
            ->with([$email])
            ->willReturn(new UserCollection([$existingUser]));
    }

    private function setupNeverCalledForBatchRegistration(): void
    {
        $this->hasherFactory->expects($this->never())
            ->method('getPasswordHasher');
        $this->uuidFactory->expects($this->never())
            ->method('create');
        $this->userRepository->expects($this->never())
            ->method('saveBatch');
        $this->registeredEventFactory->expects($this->never())
            ->method('create');
        $this->eventBus->expects($this->never())
            ->method('publish');
    }

    private function assertBatchResponse(
        RegisterUserBatchCommand $command,
        UserInterface $existingUser
    ): void {
        $response = $command->getResponse();
        $this->assertInstanceOf(RegisterUserBatchCommandResponse::class, $response);
        $users = iterator_to_array($response->users);
        $this->assertCount(1, $users);
        $this->assertSame($existingUser, $users[0]);
    }
}
