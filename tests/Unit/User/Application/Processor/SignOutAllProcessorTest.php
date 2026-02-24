<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\SignOutAllCommand;
use App\User\Application\DTO\AuthorizationUserDto;
use App\User\Application\DTO\SignOutAllDto;
use App\User\Application\Processor\SignOutAllProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;

final class SignOutAllProcessorTest extends UnitTestCase
{
    private CommandBusInterface&MockObject $commandBus;
    private TokenStorageInterface&MockObject $tokenStorage;
    private SignOutAllProcessor $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->processor = new SignOutAllProcessor(
            $this->commandBus,
            $this->tokenStorage
        );
    }

    public function testProcessDispatchesSignOutAllCommand(): void
    {
        $userId = $this->faker->uuid();
        $userEmail = $this->faker->email();
        $dto = new SignOutAllDto();
        $operation = $this->createMock(Operation::class);
        $token = $this->createAuthToken($userId, $userEmail);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static function (SignOutAllCommand $command) use ($userId) {
                return $command->userId === $userId;
            }));
        $response = $this->processor->process($dto, $operation);
        $cookies = $response->headers->getCookies();
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertCount(1, $cookies);
        $this->assertSame('__Host-auth_token', $cookies[0]->getName());
    }

    public function testClearCookieHasCorrectAttributes(): void
    {
        $reflection = new \ReflectionMethod(
            SignOutAllProcessor::class,
            'createClearCookieResponse'
        );
        $response = $reflection->invoke($this->processor);
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

    public function testProcessThrowsExceptionWhenNoToken(): void
    {
        $dto = new SignOutAllDto();
        $operation = $this->createMock(Operation::class);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn(null);

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Authentication required');

        $this->processor->process($dto, $operation);
    }

    public function testProcessThrowsExceptionWhenTokenUserIsInvalid(): void
    {
        $dto = new SignOutAllDto();
        $operation = $this->createMock(Operation::class);
        $token = $this->createMock(TokenInterface::class);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $token->expects($this->once())
            ->method('getUser')
            ->willReturn(
                $this->createMock(UserInterface::class)
            );

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid token');

        $this->processor->process($dto, $operation);
    }

    private function createAuthToken(string $userId, string $userEmail): UsernamePasswordToken
    {
        $uuidTransformer = new UuidTransformer(new UuidFactory());
        $authorizationUser = new AuthorizationUserDto(
            $userEmail,
            $this->faker->name(),
            $this->faker->password(),
            $uuidTransformer->transformFromString($userId),
            true
        );

        return new UsernamePasswordToken($authorizationUser, 'api', []);
    }
}
