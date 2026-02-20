<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Shared\Application\Decoder\JsonBodyDecoder;
use App\Shared\Application\Provider\Http\JsonRequestContentProvider;
use App\Shared\Application\Validator\Http\JsonRequestValidator;
use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\AuthorizationUserDto;
use App\User\Application\DTO\RetryDto;
use App\User\Application\Processor\ResendEmailProcessor;
use App\User\Application\Query\GetUserQueryHandler;
use App\User\Application\Service\ConfirmationEmailSenderInterface;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class ResendEmailProcessorTest extends UnitTestCase
{
    private UserFactoryInterface $userFactory;
    private UuidTransformer $uuidTransformer;
    private GetUserQueryHandler&MockObject $getUserQueryHandler;
    private ConfirmationEmailSenderInterface&MockObject $confirmationEmailSender;
    private JsonRequestValidator $jsonRequestValidator;
    private TokenStorageInterface&MockObject $tokenStorage;
    private RequestStack $requestStack;
    private ?string $authenticatedUserId = null;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new UuidFactory());
        $this->getUserQueryHandler = $this->createMock(GetUserQueryHandler::class);
        $this->confirmationEmailSender = $this->createMock(ConfirmationEmailSenderInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->tokenStorage->method('getToken')
            ->willReturnCallback(fn (): (\Symfony\Component\Security\Core\Authentication\Token\TokenInterface&\PHPUnit\Framework\MockObject\MockObject)|null => $this->createAuthenticatedToken());

        $this->requestStack = new RequestStack();
        $serializer = new \Symfony\Component\Serializer\Serializer(
            [],
            [new \Symfony\Component\Serializer\Encoder\JsonEncoder()]
        );
        $this->jsonRequestValidator = new JsonRequestValidator(
            new JsonRequestContentProvider($this->requestStack),
            new JsonBodyDecoder($serializer)
        );
    }

    public function testProcess(): void
    {
        $userId = $this->faker->uuid();
        $user = $this->createUser($userId);

        $this->getUserQueryHandler->expects($this->once())
            ->method('handle')
            ->with($userId)
            ->willReturn($user);

        $this->confirmationEmailSender->expects($this->once())
            ->method('send')
            ->with($user);

        $this->authenticatedUserId = $userId;
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

        $this->getUserQueryHandler->expects($this->once())
            ->method('handle')
            ->with($userId)
            ->willThrowException(new UserNotFoundException());

        $this->expectException(UserNotFoundException::class);

        $this->requestStack->push(Request::create('/', 'POST', [], [], [], [], '{}'));
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

    public function testProcessSendsEmailForUser(): void
    {
        $userId = $this->faker->uuid();
        $user = $this->createUser($userId);

        $this->getUserQueryHandler->expects($this->once())
            ->method('handle')
            ->with($userId)
            ->willReturn($user);

        $this->confirmationEmailSender->expects($this->once())
            ->method('send')
            ->with($user);

        $this->authenticatedUserId = $userId;
        $this->processWithRequest($userId, '{}');
    }

    public function testProcessAllowsEmptyRequestBody(): void
    {
        $userId = $this->faker->uuid();
        $user = $this->createUser($userId);

        $this->getUserQueryHandler->expects($this->once())
            ->method('handle')
            ->with($userId)
            ->willReturn($user);

        $this->confirmationEmailSender->expects($this->once())
            ->method('send');

        $this->authenticatedUserId = $userId;
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

        $this->getUserQueryHandler->expects($this->once())
            ->method('handle')
            ->with($userId)
            ->willReturn($user);

        $this->confirmationEmailSender->expects($this->once())
            ->method('send')
            ->with($user);

        $this->authenticatedUserId = $userId;

        $this->getProcessor()->process(
            new RetryDto(),
            $this->createMock(Operation::class),
            ['id' => $userId]
        );
    }

    private function getProcessor(): ResendEmailProcessor
    {
        return new ResendEmailProcessor(
            $this->getUserQueryHandler,
            $this->confirmationEmailSender,
            $this->jsonRequestValidator,
            $this->tokenStorage
        );
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

    private function createUser(string $userId): UserInterface
    {
        return $this->userFactory->create(
            $this->faker->email(),
            $this->faker->name(),
            $this->faker->password(),
            $this->uuidTransformer->transformFromString($userId)
        );
    }

    private function createAuthenticatedToken(): (\PHPUnit\Framework\MockObject\MockObject&TokenInterface)|null
    {
        if ($this->authenticatedUserId === null) {
            return null;
        }

        $authorizationUser = new AuthorizationUserDto(
            $this->faker->email(),
            $this->faker->name(),
            $this->faker->password(),
            $this->uuidTransformer->transformFromString($this->authenticatedUserId),
            true
        );

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($authorizationUser);

        return $token;
    }
}
