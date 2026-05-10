<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Shared\Application\Converter\JsonBodyConverter;
use App\Shared\Application\Provider\Http\JsonRequestContentProvider;
use App\Shared\Application\Validator\Http\JsonRequestValidator;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\SendConfirmationEmailCommand;
use App\User\Application\DTO\RetryDto;
use App\User\Application\Factory\SendConfirmationEmailCommandFactoryInterface;
use App\User\Application\Processor\ResendEmailProcessor;
use App\User\Application\Query\GetUserQueryHandlerInterface;
use App\User\Application\Validator\OwnershipValidatorInterface;
use App\User\Domain\Aggregate\ConfirmationEmailInterface;
use App\User\Domain\Entity\ConfirmationTokenInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Factory\ConfirmationEmailFactoryInterface;
use App\User\Domain\Factory\ConfirmationTokenFactoryInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\TokenRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class ResendEmailProcessorTest extends UnitTestCase
{
    private UserFactoryInterface $userFactory;
    private UuidTransformer $uuidTransformer;
    private GetUserQueryHandlerInterface&MockObject $getUserQueryHandler;
    private JsonRequestValidator $jsonRequestValidator;
    private CommandBusInterface&MockObject $commandBus;
    private TokenRepositoryInterface&MockObject $tokenRepository;
    private ConfirmationTokenFactoryInterface&MockObject $tokenFactory;
    private ConfirmationEmailFactoryInterface&MockObject $confirmationEmailFactory;
    private SendConfirmationEmailCommandFactoryInterface&MockObject $emailCmdFactory;
    private OwnershipValidatorInterface&MockObject $ownershipGuard;
    private RequestStack $requestStack;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new UuidFactory());
        $this->initMocks();
        $this->initJsonValidator();
    }

    public function testProcess(): void
    {
        $userId = $this->faker->uuid();
        $user = $this->createUser($userId);
        $this->expectUserLookup($userId, $user);
        $this->configureEmailSending($user);
        $this->commandBus->expects($this->once())->method('dispatch');

        $response = $this->processWithRequest($userId, '{}');

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testProcessUserNotFound(): void
    {
        $userId = $this->faker->uuid();
        $this->getUserQueryHandler->expects($this->once())
            ->method('handle')
            ->with($userId)
            ->willThrowException(new UserNotFoundException());
        $this->expectException(UserNotFoundException::class);

        $this->processWithRequest($userId, '{}');
    }

    public function testProcessSendsEmailForUser(): void
    {
        $userId = $this->faker->uuid();
        $user = $this->createUser($userId);
        $this->expectUserLookup($userId, $user);
        $this->configureEmailSending($user);
        $this->commandBus->expects($this->once())->method('dispatch');

        $this->processWithRequest($userId, '{}');
    }

    public function testProcessAllowsEmptyRequestBody(): void
    {
        $userId = $this->faker->uuid();
        $user = $this->createUser($userId);
        $this->expectUserLookup($userId, $user);
        $this->configureEmailSending($user);
        $this->commandBus->expects($this->once())->method('dispatch');

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
        $userId = $this->faker->uuid();
        $user = $this->createUser($userId);
        $this->expectUserLookup($userId, $user);
        $this->configureEmailSending($user);
        $this->commandBus->expects($this->once())->method('dispatch');

        $this->getProcessor()->process(
            new RetryDto(),
            $this->createMock(Operation::class),
            ['id' => $userId]
        );
    }

    public function testProcessThrowsAccessDeniedWhenTokenIsNull(): void
    {
        $userId = $this->faker->uuid();
        $this->expectUserLookup($userId, $this->createUser($userId));
        $this->ownershipGuard->expects($this->once())
            ->method('assertOwnership')
            ->willThrowException(new AccessDeniedException());
        $this->expectException(AccessDeniedException::class);

        $this->processWithRequest($userId, '{}');
    }

    public function testProcessThrowsAccessDeniedWhenUserIsNotAuthorizationUserDto(): void
    {
        $userId = $this->faker->uuid();
        $this->expectUserLookup($userId, $this->createUser($userId));
        $this->ownershipGuard->expects($this->once())
            ->method('assertOwnership')
            ->willThrowException(new AccessDeniedException());
        $this->expectException(AccessDeniedException::class);

        $this->processWithRequest($userId, '{}');
    }

    public function testProcessThrowsAccessDeniedWhenUserIdDoesNotMatch(): void
    {
        $resourceUserId = $this->faker->uuid();
        $this->expectUserLookup($resourceUserId, $this->createUser($resourceUserId));
        $this->ownershipGuard->expects($this->once())
            ->method('assertOwnership')
            ->willThrowException(new AccessDeniedException());
        $this->expectException(AccessDeniedException::class);

        $this->processWithRequest($resourceUserId, '{}');
    }

    public function testProcessCreatesNewTokenWhenNoneExists(): void
    {
        $userId = $this->faker->uuid();
        $user = $this->createUser($userId);
        $this->expectUserLookup($userId, $user);

        $this->tokenRepository->method('findByUserId')->with($user->getId())->willReturn(null);
        $newToken = $this->createMock(ConfirmationTokenInterface::class);
        $this->tokenFactory->expects($this->once())
            ->method('create')
            ->with($user->getId())
            ->willReturn($newToken);

        $email = $this->createMock(ConfirmationEmailInterface::class);
        $this->confirmationEmailFactory->method('create')
            ->with($newToken, $user)->willReturn($email);
        $command = new SendConfirmationEmailCommand($email);
        $this->emailCmdFactory->method('create')->with($email)->willReturn($command);
        $this->commandBus->expects($this->once())->method('dispatch');

        $this->processWithRequest($userId, '{}');
    }

    private function getProcessor(): ResendEmailProcessor
    {
        return new ResendEmailProcessor(
            $this->getUserQueryHandler,
            $this->jsonRequestValidator,
            $this->commandBus,
            $this->tokenRepository,
            $this->tokenFactory,
            $this->confirmationEmailFactory,
            $this->emailCmdFactory,
            $this->ownershipGuard,
        );
    }

    private function configureEmailSending(UserInterface $user): void
    {
        $token = $this->createMock(ConfirmationTokenInterface::class);
        $this->tokenRepository->method('findByUserId')->with($user->getId())->willReturn($token);

        $email = $this->createMock(ConfirmationEmailInterface::class);
        $this->confirmationEmailFactory->method('create')
            ->with($token, $user)
            ->willReturn($email);

        $command = new SendConfirmationEmailCommand($email);
        $this->emailCmdFactory->method('create')->with($email)->willReturn($command);
    }

    private function processWithRequest(string $userId, string $body): mixed
    {
        $this->requestStack->push(Request::create('/', 'POST', [], [], [], [], $body));

        try {
            return $this->getProcessor()->process(
                new RetryDto(),
                $this->createMock(Operation::class),
                ['id' => $userId]
            );
        } finally {
            $this->requestStack->pop();
        }
    }

    private function createUser(string $userId): UserInterface
    {
        return $this->userFactory->create(
            $this->faker->email(),
            $this->faker->name(),
            $this->faker->password(),
            $this->uuidTransformer->transformFromString($userId)
        );
    }

    private function initMocks(): void
    {
        $this->getUserQueryHandler = $this->createMock(GetUserQueryHandlerInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->tokenRepository = $this->createMock(TokenRepositoryInterface::class);
        $this->tokenFactory = $this->createMock(ConfirmationTokenFactoryInterface::class);
        $this->confirmationEmailFactory = $this->createMock(
            ConfirmationEmailFactoryInterface::class
        );
        $this->emailCmdFactory = $this->createMock(
            SendConfirmationEmailCommandFactoryInterface::class
        );
        $this->ownershipGuard = $this->createMock(OwnershipValidatorInterface::class);
    }

    private function initJsonValidator(): void
    {
        $this->requestStack = new RequestStack();
        $serializer = new \Symfony\Component\Serializer\Serializer(
            [],
            [new \Symfony\Component\Serializer\Encoder\JsonEncoder()]
        );
        $this->jsonRequestValidator = new JsonRequestValidator(
            new JsonRequestContentProvider($this->requestStack),
            new JsonBodyConverter($serializer)
        );
    }

    private function expectUserLookup(string $userId, UserInterface $user): void
    {
        $this->getUserQueryHandler->expects($this->once())
            ->method('handle')
            ->with($userId)
            ->willReturn($user);
    }
}
