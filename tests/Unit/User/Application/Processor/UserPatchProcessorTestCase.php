<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Shared\Application\Decoder\JsonBodyDecoder;
use App\Shared\Application\Provider\Http\JsonRequestContentProvider;
use App\Shared\Application\Provider\Http\JsonRequestPayloadProvider;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\UserPatchDto;
use App\User\Application\Factory\UpdateUserCommandFactory;
use App\User\Application\Factory\UpdateUserCommandFactoryInterface;
use App\User\Application\Processor\UserPatchProcessor;
use App\User\Application\Query\GetUserQueryHandler;
use App\User\Application\Resolver\UserPatchEmailResolver;
use App\User\Application\Resolver\UserPatchFieldResolver;
use App\User\Application\Resolver\UserPatchPasswordResolver;
use App\User\Application\Resolver\UserPatchUpdateResolver;
use App\User\Application\Validator\UserPatchPayloadValidator;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\ValueObject\UserUpdate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

abstract class UserPatchProcessorTestCase extends UnitTestCase
{
    protected Operation $mockOperation;
    protected UserFactoryInterface $userFactory;
    protected UuidTransformer $uuidTransformer;
    protected UpdateUserCommandFactoryInterface $updateUserCommandFactory;
    protected CommandBusInterface $commandBus;
    protected UpdateUserCommandFactoryInterface $mockUpdateUserCommandFactory;
    protected GetUserQueryHandler $getUserQueryHandler;
    protected UserPatchProcessor $processor;
    protected RequestStack $requestStack;
    protected JsonRequestPayloadProvider $payloadProvider;
    protected UserPatchUpdateResolver $updateResolver;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->faker->seed(12345);

        $this->mockOperation = $this->createMock(Operation::class);
        $this->getUserQueryHandler = $this->createMock(GetUserQueryHandler::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $updateFactory = UpdateUserCommandFactoryInterface::class;
        $this->mockUpdateUserCommandFactory = $this->createMock($updateFactory);

        $this->initializeRealObjectsAndProcessor();
    }

    protected function initializeRealObjectsAndProcessor(): void
    {
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new UuidFactory());
        $this->updateUserCommandFactory = new UpdateUserCommandFactory();
        $this->initializePayloadProviderAndResolver();
        $this->initializeProcessor();
    }

    protected function initializePayloadProviderAndResolver(): void
    {
        $this->requestStack = new RequestStack();
        $serializer = new \Symfony\Component\Serializer\Serializer(
            [],
            [new \Symfony\Component\Serializer\Encoder\JsonEncoder()]
        );
        $this->payloadProvider = new JsonRequestPayloadProvider(
            new JsonRequestContentProvider($this->requestStack),
            new JsonBodyDecoder($serializer)
        );
        $this->updateResolver = new UserPatchUpdateResolver(
            new UserPatchEmailResolver(),
            new UserPatchFieldResolver(),
            new UserPatchPasswordResolver()
        );
    }

    protected function initializeProcessor(): void
    {
        $this->processor = new UserPatchProcessor(
            $this->commandBus,
            $this->mockUpdateUserCommandFactory,
            $this->getUserQueryHandler,
            $this->payloadProvider,
            $this->updateResolver,
            new UserPatchPayloadValidator()
        );
    }

    protected function processWithInvalidInput(
        UserInterface $user,
        string $initials,
        string $password,
        string $userId,
        ?string $invalidEmail = null,
        ?string $invalidInitials = null,
        ?string $invalidPassword = null
    ): UserInterface {
        $invalidEmail = $invalidEmail ?? $this->faker->word();
        $effectiveInitials = $invalidInitials ?? $initials;
        $effectivePassword = $invalidPassword ?? $password;

        $updateData = new UserUpdate(
            $invalidEmail,
            $effectiveInitials,
            $effectivePassword,
            $password
        );
        $this->setupProcessExpectations($user, $updateData, $userId);

        return $this->executeProcessWithPayload(
            $invalidEmail,
            $effectiveInitials,
            $password,
            $effectivePassword,
            $userId
        );
    }

    protected function executeProcessWithPayload(
        string $email,
        string $initials,
        string $oldPassword,
        string $newPassword,
        string $userId
    ): UserInterface {
        return $this->withRequest(
            [
                'email' => $email,
                'initials' => $initials,
                'oldPassword' => $oldPassword,
                'newPassword' => $newPassword,
            ],
            fn () => $this->processor->process(
                new UserPatchDto($email, $initials, $oldPassword, $newPassword),
                $this->mockOperation,
                ['id' => $userId]
            )
        );
    }

    protected function setupProcessExpectations(
        UserInterface $user,
        UserUpdate $updateData,
        string $userId
    ): void {
        $command = $this->updateUserCommandFactory->create($user, $updateData);

        $this->getUserQueryHandler->expects($this->once())
            ->method('handle')
            ->with($userId)
            ->willReturn($user);

        $this->mockUpdateUserCommandFactory->expects($this->once())
            ->method('create')
            ->with(
                $user,
                $this->callback(function (UserUpdate $actual) use ($updateData) {
                    $this->assertSame($updateData->newEmail, $actual->newEmail);
                    $this->assertSame($updateData->newInitials, $actual->newInitials);
                    $this->assertSame($updateData->newPassword, $actual->newPassword);
                    $this->assertSame($updateData->oldPassword, $actual->oldPassword);

                    return true;
                })
            )
            ->willReturn($command);

        $this->commandBus->expects($this->once())->method('dispatch')->with($command);
    }

    protected function setupUserForPatchTest(): UserPatchTestData
    {
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();
        $userId = $this->faker->uuid();

        $user = $this->userFactory->create(
            $email,
            $initials,
            $password,
            $this->uuidTransformer->transformFromString($userId)
        );

        return new UserPatchTestData(
            $user,
            $email,
            $initials,
            $password,
            $userId
        );
    }

    /**
     * @param array<string, string|null> $payload
     * @param callable(): array|bool|float|int|object|string|null $callback
     */
    protected function withRequest(
        array $payload,
        callable $callback
    ): array|bool|float|int|object|string|null {
        $request = Request::create(
            '/',
            'PATCH',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload, JSON_THROW_ON_ERROR)
        );

        $this->requestStack->push($request);

        try {
            return $callback();
        } finally {
            $this->requestStack->pop();
        }
    }

    /**
     * @param callable(): array|bool|float|int|object|string|null $callback
     */
    protected function withRawRequest(
        string $content,
        callable $callback
    ): array|bool|float|int|object|string|null {
        $request = Request::create(
            '/',
            'PATCH',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $content
        );

        $this->requestStack->push($request);

        try {
            return $callback();
        } finally {
            $this->requestStack->pop();
        }
    }

    protected function setupNoUpdateExpectations(UserPatchTestData $testData): void
    {
        $this->getUserQueryHandler->expects($this->once())
            ->method('handle')
            ->with($testData->userId)
            ->willReturn($testData->user);
        $this->mockUpdateUserCommandFactory->expects($this->never())->method('create');
        $this->commandBus->expects($this->never())->method('dispatch');
    }

    /**
     * @return array<string, string|UserInterface|UserUpdate>
     */
    protected function createProcessTestData(): array
    {
        $email = $this->faker->email();
        $initials = $this->faker->name();
        $password = $this->faker->password();
        $userId = $this->faker->uuid();
        $newPassword = $this->faker->password();
        $newInitials = $this->faker->name();
        $newEmail = $this->faker->email();
        $uuid = $this->uuidTransformer->transformFromString($userId);

        $updateData = new UserUpdate($newEmail, $newInitials, $newPassword, $password);
        $user = $this->userFactory->create($email, $initials, $password, $uuid);

        return [
            'user' => $user,
            'updateData' => $updateData,
            'userId' => $userId,
            'newEmail' => $newEmail,
            'newInitials' => $newInitials,
            'password' => $password,
            'newPassword' => $newPassword,
        ];
    }

    /**
     * @param array<string, string|UserInterface|UserUpdate> $testData
     */
    protected function executeProcessWithNewData(array $testData): UserInterface
    {
        return $this->withRequest(
            [
                'email' => $testData['newEmail'],
                'initials' => $testData['newInitials'],
                'oldPassword' => $testData['password'],
                'newPassword' => $testData['newPassword'],
            ],
            fn () => $this->processor->process(
                new UserPatchDto(
                    $testData['newEmail'],
                    $testData['newInitials'],
                    $testData['password'],
                    $testData['newPassword']
                ),
                $this->mockOperation,
                ['id' => $testData['userId']]
            )
        );
    }
}
