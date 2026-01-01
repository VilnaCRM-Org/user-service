<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Shared\Application\Decoder\JsonBodyDecoder;
use App\Shared\Application\Http\JsonRequestContentProvider;
use App\Shared\Application\Http\JsonRequestValidator;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\RetryDto;
use App\User\Application\Factory\SendConfirmationEmailCommandFactory;
use App\User\Application\Factory\SendConfirmationEmailCommandFactoryInterface;
use App\User\Application\Processor\ResendEmailProcessor;
use App\User\Application\Query\GetUserQueryHandler;
use App\User\Domain\Entity\ConfirmationTokenInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Factory\ConfirmationEmailFactory;
use App\User\Domain\Factory\ConfirmationEmailFactoryInterface;
use App\User\Domain\Factory\ConfirmationTokenFactory;
use App\User\Domain\Factory\ConfirmationTokenFactoryInterface;
use App\User\Domain\Factory\Event\ConfirmationEmailSendEventFactory;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\TokenRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class ResendEmailProcessorTest extends UnitTestCase
{
    private UserFactoryInterface $userFactory;
    private UuidTransformer $uuidTransformer;
    private ConfirmationTokenFactoryInterface $confirmationTokenFactory;
    private ConfirmationTokenFactoryInterface $tokenFactory;
    private ConfirmationEmailFactoryInterface $confirmationFactory;
    private SendConfirmationEmailCommandFactoryInterface $emailCommandFactory;
    private CommandBusInterface $commandBus;
    private TokenRepositoryInterface $tokenRepository;
    private ConfirmationEmailFactoryInterface $mockConfirmationEmailFactory;
    private SendConfirmationEmailCommandFactoryInterface $mockEmailCmdFactory;
    private GetUserQueryHandler $getUserQueryHandler;
    private RequestStack $requestStack;
    private JsonRequestValidator $jsonRequestValidator;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->initFactories();
        $this->initMocks();
        $this->initRequestHandlers();
    }

    public function testProcess(): void
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
        $token = $this->confirmationTokenFactory->create($userId);

        $this->testProcessSetExpectations($user, $token);

        $this->requestStack->push(Request::create('/', 'POST', [], [], [], [], '{}'));

        $response = $this->getProcessor()->process(
            new RetryDto(),
            $this->createMock(Operation::class),
            ['id' => $userId]
        );

        $this->requestStack->pop();

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testProcessUserNotFound(): void
    {
        $userId = $this->faker->uuid();
        $retryDto = new RetryDto();

        $this->getUserQueryHandler->expects($this->once())
            ->method('handle')
            ->with($userId)
            ->willThrowException(new UserNotFoundException());

        $this->expectException(UserNotFoundException::class);

        $this->requestStack->push(Request::create('/', 'POST', [], [], [], [], '{}'));
        try {
            $this->getProcessor()->process(
                $retryDto,
                $this->createMock(Operation::class),
                ['id' => $userId]
            );
        } finally {
            $this->requestStack->pop();
        }
    }

    public function testProcessCreatesTokenWhenMissing(): void
    {
        $testData = $this->createUserAndToken();
        $user = $testData['user'];
        $token = $testData['token'];
        $userId = $testData['userId'];

        $this->setupTokenCreationExpectations($user, $userId, $token);
        $this->setupEmailDispatchExpectations($token, $user);
        $this->processWithRequest($userId, '{}');
    }

    public function testProcessAllowsEmptyRequestBody(): void
    {
        $testData = $this->createUserAndToken();
        $user = $testData['user'];
        $token = $testData['token'];
        $userId = $testData['userId'];

        $this->testProcessSetExpectations($user, $token);
        $this->processWithRequest($userId, '');
    }

    public function testProcessWithInvalidJsonThrowsBadRequest(): void
    {
        $this->requestStack->push(Request::create('/', 'POST', [], [], [], [], '{invalid'));

        $this->expectException(BadRequestHttpException::class);

        try {
            $this->getProcessor()->process(
                new RetryDto(),
                $this->createMock(Operation::class),
                ['id' => $this->faker->uuid()]
            );
        } finally {
            $this->requestStack->pop();
        }
    }

    public function testProcessWithArrayJsonThrowsBadRequest(): void
    {
        $this->requestStack->push(Request::create('/', 'POST', [], [], [], [], '[1]'));

        $this->expectException(BadRequestHttpException::class);

        try {
            $this->getProcessor()->process(
                new RetryDto(),
                $this->createMock(Operation::class),
                ['id' => $this->faker->uuid()]
            );
        } finally {
            $this->requestStack->pop();
        }
    }

    public function testProcessWithScalarJsonThrowsBadRequest(): void
    {
        $this->requestStack->push(Request::create('/', 'POST', [], [], [], [], '"value"'));

        $this->expectException(BadRequestHttpException::class);

        try {
            $this->getProcessor()->process(
                new RetryDto(),
                $this->createMock(Operation::class),
                ['id' => $this->faker->uuid()]
            );
        } finally {
            $this->requestStack->pop();
        }
    }

    public function testProcessWithoutCurrentRequestSkipsValidation(): void
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
        $token = $this->confirmationTokenFactory->create($userId);

        $this->testProcessSetExpectations($user, $token);

        $this->getProcessor()->process(
            new RetryDto(),
            $this->createMock(Operation::class),
            ['id' => $userId]
        );
    }

    private function getProcessor(): ResendEmailProcessor
    {
        return new ResendEmailProcessor(
            $this->commandBus,
            $this->getUserQueryHandler,
            $this->tokenRepository,
            $this->tokenFactory,
            $this->mockConfirmationEmailFactory,
            $this->mockEmailCmdFactory,
            $this->jsonRequestValidator
        );
    }

    private function testProcessSetExpectations(
        UserInterface $user,
        ConfirmationTokenInterface $token
    ): void {
        $confirmationEmail = $this->confirmationFactory->create($token, $user);
        $command = $this->emailCommandFactory->create($confirmationEmail);

        $this->setupUserAndTokenExpectations($user, $token);
        $this->setupEmailFactoryExpectations($confirmationEmail, $command);
    }

    private function setupUserAndTokenExpectations(
        UserInterface $user,
        ConfirmationTokenInterface $token
    ): void {
        $this->getUserQueryHandler->expects($this->once())
            ->method('handle')
            ->with($user->getID())
            ->willReturn($user);

        $this->tokenRepository->expects($this->once())
            ->method('findByUserId')
            ->with($this->equalTo($user->getID()))
            ->willReturn($token);

        $this->tokenFactory->expects($this->never())->method('create');
    }

    private function setupEmailFactoryExpectations(
        \App\User\Domain\Aggregate\ConfirmationEmail $confirmationEmail,
        \App\User\Application\Command\SendConfirmationEmailCommand $command
    ): void {
        $this->mockConfirmationEmailFactory->expects($this->once())
            ->method('create')
            ->willReturn($confirmationEmail);

        $this->mockEmailCmdFactory->expects($this->once())
            ->method('create')
            ->with($confirmationEmail)
            ->willReturn($command);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($command);
    }

    private function initFactories(): void
    {
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new UuidFactory());
        $this->confirmationTokenFactory = new ConfirmationTokenFactory(
            $this->faker->numberBetween(1, 10)
        );
        $this->confirmationFactory = new ConfirmationEmailFactory(
            new ConfirmationEmailSendEventFactory()
        );
        $this->emailCommandFactory = new SendConfirmationEmailCommandFactory();
    }

    private function initMocks(): void
    {
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->tokenRepository = $this->createMock(TokenRepositoryInterface::class);
        $this->mockConfirmationEmailFactory = $this->createMock(
            ConfirmationEmailFactoryInterface::class
        );
        $this->mockEmailCmdFactory = $this->createMock(
            SendConfirmationEmailCommandFactoryInterface::class
        );
        $this->getUserQueryHandler = $this->createMock(GetUserQueryHandler::class);
        $this->tokenFactory = $this->createMock(ConfirmationTokenFactoryInterface::class);
    }

    private function initRequestHandlers(): void
    {
        $this->requestStack = new RequestStack();
        $this->jsonRequestValidator = new JsonRequestValidator(
            new JsonRequestContentProvider($this->requestStack),
            new JsonBodyDecoder()
        );
    }

    /**
     * @return array<string, string|UserInterface|ConfirmationTokenInterface>
     */
    private function createUserAndToken(): array
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
        $token = $this->confirmationTokenFactory->create($userId);

        return [
            'user' => $user,
            'token' => $token,
            'userId' => $userId,
        ];
    }

    private function setupTokenCreationExpectations(
        UserInterface $user,
        string $userId,
        ConfirmationTokenInterface $token
    ): void {
        $this->getUserQueryHandler->expects($this->once())
            ->method('handle')
            ->with($userId)
            ->willReturn($user);

        $this->tokenRepository->expects($this->once())
            ->method('findByUserId')
            ->with($userId)
            ->willReturn(null);

        $this->tokenFactory->expects($this->once())
            ->method('create')
            ->with($user->getId())
            ->willReturn($token);
    }

    private function setupEmailDispatchExpectations(
        ConfirmationTokenInterface $token,
        UserInterface $user
    ): void {
        $confirmationEmail = $this->confirmationFactory->create($token, $user);
        $command = $this->emailCommandFactory->create($confirmationEmail);

        $this->mockConfirmationEmailFactory->expects($this->once())
            ->method('create')
            ->with($token, $user)
            ->willReturn($confirmationEmail);

        $this->mockEmailCmdFactory->expects($this->once())
            ->method('create')
            ->with($confirmationEmail)
            ->willReturn($command);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($command);
    }

    private function processWithRequest(string $userId, string $body): void
    {
        $this->requestStack->push(Request::create('/', 'POST', [], [], [], [], $body));

        try {
            $this->getProcessor()->process(
                new RetryDto(),
                $this->createMock(Operation::class),
                ['id' => $userId]
            );
        } finally {
            $this->requestStack->pop();
        }
    }
}
