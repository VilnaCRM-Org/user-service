<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Shared\Application\Decoder\JsonBodyDecoder;
use App\Shared\Application\Http\JsonRequestContentProvider;
use App\Shared\Application\Http\JsonRequestPayloadProvider;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\UserPatchDto;
use App\User\Application\Factory\UpdateUserCommandFactory;
use App\User\Application\Factory\UpdateUserCommandFactoryInterface;
use App\User\Application\Processor\UserPatchProcessor;
use App\User\Application\Query\GetUserQueryHandler;
use App\User\Application\Resolver\UserPatchUpdateResolver;
use App\User\Application\Sanitizer\UserPatchEmailSanitizer;
use App\User\Application\Sanitizer\UserPatchNonEmptySanitizer;
use App\User\Application\Sanitizer\UserPatchPasswordSanitizer;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\ValueObject\UserUpdate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class UserPatchProcessorTest extends UnitTestCase
{
    private Operation $mockOperation;
    private UserFactoryInterface $userFactory;
    private UuidTransformer $uuidTransformer;
    private UpdateUserCommandFactoryInterface $updateUserCommandFactory;
    private CommandBusInterface $commandBus;
    private UpdateUserCommandFactoryInterface $mockUpdateUserCommandFactory;
    private GetUserQueryHandler $getUserQueryHandler;
    private UserPatchProcessor $processor;
    private RequestStack $requestStack;
    private JsonRequestPayloadProvider $payloadProvider;
    private UserPatchUpdateResolver $updateResolver;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->faker->seed(12345);

        $this->mockOperation = $this->createMock(Operation::class);
        $this->getUserQueryHandler = $this->createMock(GetUserQueryHandler::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->mockUpdateUserCommandFactory = $this->createMock(UpdateUserCommandFactoryInterface::class);

        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new UuidFactory());
        $this->updateUserCommandFactory = new UpdateUserCommandFactory();
        $this->requestStack = new RequestStack();
        $this->payloadProvider = new JsonRequestPayloadProvider(
            new JsonRequestContentProvider($this->requestStack),
            new JsonBodyDecoder()
        );
        $this->updateResolver = new UserPatchUpdateResolver(
            new UserPatchEmailSanitizer(),
            new UserPatchNonEmptySanitizer(),
            new UserPatchPasswordSanitizer()
        );

        $this->processor = new UserPatchProcessor(
            $this->commandBus,
            $this->mockUpdateUserCommandFactory,
            $this->getUserQueryHandler,
            $this->payloadProvider,
            $this->updateResolver
        );
    }

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

    public function testProcessWithSpacesPassed(): void
    {
        $testData = $this->setupUserForPatchTest();
        $this->setupNoUpdateExpectations($testData);

        $this->expectException(BadRequestHttpException::class);

        $this->processWithSpaces($testData);
    }

    public function testProcessWithBlankInitialsThrowsBadRequest(): void
    {
        $testData = $this->setupUserForPatchTest();
        $this->setupNoUpdateExpectations($testData);

        $this->expectException(BadRequestHttpException::class);

        $this->processWithBlankInitials($testData);
    }

    public function testProcessUserNotFound(): void
    {
        $userId = $this->faker->uuid();

        $this->getUserQueryHandler->expects($this->once())
            ->method('handle')
            ->with($userId)
            ->willThrowException(new UserNotFoundException());

        $this->expectException(UserNotFoundException::class);

        $this->processWithRandomData($userId);
    }

    public function testProcessWithInvalidEmailPreservesOriginal(): void
    {
        $testData = $this->setupUserForPatchTest();
        $result = $this->processWithInvalidInput(
            $testData->user,
            $testData->email,
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
        $this->setupDefaultProcessExpectations($testData);

        $result = $this->processWithProvidedInitials($testData);

        $this->assertSame($testData->initials, $result->getInitials());
    }

    public function testProcessUsesDefaultPasswordWhenDtoValueIsNull(): void
    {
        $testData = $this->setupUserForPatchTest();
        $this->setupDefaultProcessExpectations($testData);

        $result = $this->processWithProvidedPassword($testData);

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

    private function processWithInvalidInput(
        UserInterface $user,
        string $email,
        string $initials,
        string $password,
        string $userId,
        ?string $invalidEmail = null,
        ?string $invalidInitials = null,
        ?string $invalidPassword = null
    ): UserInterface {
        $invalidEmail = $invalidEmail ?? 'not-an-email';
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

    private function executeProcessWithPayload(
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

    private function setupProcessExpectations(
        UserInterface $user,
        UserUpdate $updateData,
        string $userId
    ): void {
        $command = $this->updateUserCommandFactory->create($user, $updateData);
        $this->expectUserQueryHandler($userId, $user);
        $this->expectUpdateUserCommandFactory($user, $updateData, $command);
        $this->expectCommandBusDispatch($command);
    }

    private function expectUserQueryHandler(
        string $userId,
        UserInterface $user
    ): void {
        $this->getUserQueryHandler->expects(
            $this->once()
        )
            ->method('handle')
            ->with($userId)
            ->willReturn($user);
    }

    private function expectUpdateUserCommandFactory(
        UserInterface $user,
        UserUpdate $updateData,
        object $command
    ): void {
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
    }

    private function expectCommandBusDispatch(object $command): void
    {
        $this->commandBus->expects(
            $this->once()
        )
            ->method('dispatch')
            ->with($command);
    }

    private function setupUserForPatchTest(): UserPatchTestData
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
     * @template T
     *
     * @param array<string, string|null> $payload
     * @param callable():T $callback
     *
     * @return T
     */
    private function withRequest(array $payload, callable $callback)
    {
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
     * @template T
     *
     * @param callable():T $callback
     *
     * @return T
     */
    private function withRawRequest(string $content, callable $callback)
    {
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

    private function setupDefaultProcessExpectations(UserPatchTestData $testData): void
    {
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
    }

    private function processWithProvidedInitials(UserPatchTestData $testData): UserInterface
    {
        return $this->withRequest(
            [
                'initials' => 'Provided Initials',
                'oldPassword' => $testData->password,
            ],
            fn () => $this->processor->process(
                new UserPatchDto(null, null, $testData->password, null),
                $this->mockOperation,
                ['id' => $testData->userId]
            )
        );
    }

    private function processWithProvidedPassword(UserPatchTestData $testData): UserInterface
    {
        return $this->withRequest(
            [
                'newPassword' => 'Provided New Password',
                'oldPassword' => $testData->password,
            ],
            fn () => $this->processor->process(
                new UserPatchDto(null, null, $testData->password, null),
                $this->mockOperation,
                ['id' => $testData->userId]
            )
        );
    }

    private function setupNoUpdateExpectations(UserPatchTestData $testData): void
    {
        $this->getUserQueryHandler->expects($this->once())
            ->method('handle')
            ->with($testData->userId)
            ->willReturn($testData->user);
        $this->mockUpdateUserCommandFactory->expects($this->never())->method('create');
        $this->commandBus->expects($this->never())->method('dispatch');
    }

    private function processWithSpaces(UserPatchTestData $testData): void
    {
        $this->withRequest(
            [
                'email' => ' ',
                'initials' => ' ',
                'oldPassword' => $testData->password,
                'newPassword' => ' ',
            ],
            fn () => $this->processor->process(
                new UserPatchDto(' ', ' ', $testData->password, ' '),
                $this->mockOperation,
                ['id' => $testData->userId]
            )
        );
    }

    private function processWithBlankInitials(UserPatchTestData $testData): void
    {
        $this->withRequest(
            [
                'initials' => ' ',
                'oldPassword' => $testData->password,
            ],
            fn () => $this->processor->process(
                new UserPatchDto(null, ' ', $testData->password, null),
                $this->mockOperation,
                ['id' => $testData->userId]
            )
        );
    }

    private function processWithRandomData(string $userId): void
    {
        $this->withRequest(
            [
                'email' => $this->faker->email(),
                'initials' => $this->faker->name(),
                'oldPassword' => $this->faker->password(),
                'newPassword' => $this->faker->password(),
            ],
            fn () => $this->processor->process(
                new UserPatchDto(
                    $this->faker->email(),
                    $this->faker->name(),
                    $this->faker->password(),
                    $this->faker->password()
                ),
                $this->mockOperation,
                ['id' => $userId]
            )
        );
    }

    /**
     * @return array<string, string|UserInterface|UserUpdate>
     */
    private function createProcessTestData(): array
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
    private function executeProcessWithNewData(array $testData): UserInterface
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
