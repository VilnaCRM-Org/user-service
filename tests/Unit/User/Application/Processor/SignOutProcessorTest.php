<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Factory\UuidFactory as SharedUuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\SignOutCommand;
use App\User\Application\DTO\AuthorizationUserDto;
use App\User\Application\DTO\SignOutDto;
use App\User\Application\Processor\SignOutProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class SignOutProcessorTest extends UnitTestCase
{
    private CommandBusInterface&MockObject $commandBus;
    private TokenStorageInterface&MockObject $tokenStorage;
    private SignOutProcessor $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->processor = new SignOutProcessor(
            $this->commandBus,
            $this->tokenStorage
        );
    }

    public function testProcessDispatchesSignOutCommand(): void
    {
        $sessionId = $this->faker->uuid();
        $userId = $this->faker->uuid();
        $dto = new SignOutDto();
        $operation = $this->createMock(Operation::class);

        $token = $this->createMock(TokenInterface::class);
        $uuidTransformer = new UuidTransformer(new SharedUuidFactory());
        $user = new AuthorizationUserDto(
            $this->faker->email(),
            $this->faker->name(),
            $this->faker->password(),
            $uuidTransformer->transformFromString($userId),
            true
        );

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $token->expects($this->once())
            ->method('getAttribute')
            ->with('sid')
            ->willReturn($sessionId);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static function (SignOutCommand $command) use ($sessionId, $userId) {
                return $command->sessionId === $sessionId
                    && $command->userId === $userId;
            }));

        $response = $this->processor->process($dto, $operation);
        $cookies = $response->headers->getCookies();
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertCount(1, $cookies);
        $this->assertSame('__Host-auth_token', $cookies[0]->getName());
    }

    public function testProcessThrowsExceptionWhenNoToken(): void
    {
        $dto = new SignOutDto();
        $operation = $this->createMock(Operation::class);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn(null);

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Authentication required');

        $this->processor->process($dto, $operation);
    }

    public function testProcessThrowsExceptionWhenNoUser(): void
    {
        $dto = new SignOutDto();
        $operation = $this->createMock(Operation::class);
        $token = $this->createMock(TokenInterface::class);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $token->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid token');

        $this->processor->process($dto, $operation);
    }

    public function testClearCookieHasCorrectAttributes(): void
    {
        $processor = new SignOutProcessor(
            $this->commandBus,
            $this->tokenStorage
        );

        $reflection = new \ReflectionMethod(SignOutProcessor::class, 'createClearCookieResponse');
        $response = $reflection->invoke($processor);

        $cookies = $response->headers->getCookies();
        $this->assertCount(1, $cookies);
        $cookie = $cookies[0];

        $this->assertSame('__Host-auth_token', $cookie->getName());
        $this->assertSame('', $cookie->getValue());
        $this->assertSame(1, $cookie->getExpiresTime());
        $this->assertSame('/', $cookie->getPath());
        $this->assertNull($cookie->getDomain());
        $this->assertTrue($cookie->isSecure());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertFalse($cookie->isRaw());
        $this->assertSame(Cookie::SAMESITE_LAX, $cookie->getSameSite());
    }

    public function testProcessThrowsExceptionWhenNoSessionId(): void
    {
        $dto = new SignOutDto();
        $operation = $this->createMock(Operation::class);
        $token = $this->createMock(TokenInterface::class);
        $uuidTransformer = new UuidTransformer(new SharedUuidFactory());
        $user = new AuthorizationUserDto(
            $this->faker->email(),
            $this->faker->name(),
            $this->faker->password(),
            $uuidTransformer->transformFromString($this->faker->uuid()),
            true
        );

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $token->expects($this->once())
            ->method('getAttribute')
            ->with('sid')
            ->willReturn(null);

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Session ID not found in token');

        $this->processor->process($dto, $operation);
    }
}
