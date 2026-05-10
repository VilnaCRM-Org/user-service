<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RegenerateRecoveryCodesCommand;
use App\User\Application\DTO\RegenerateRecoveryCodesCommandResponse;
use App\User\Application\DTO\RegenerateRecoveryCodesDto;
use App\User\Application\Factory\RegenerateRecoveryCodesCommandFactory;
use App\User\Application\Processor\RegenerateRecoveryCodesProcessor;
use App\User\Application\Resolver\CurrentUserIdentityResolver;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class RegenerateRecoveryCodesProcessorTest extends UnitTestCase
{
    private CommandBusInterface&MockObject $commandBus;
    private Security&MockObject $security;
    private Operation $operation;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->security = $this->createMock(Security::class);
        $this->operation = $this->createMock(Operation::class);
    }

    public function testProcessReturnsRecoveryCodes(): void
    {
        $email = $this->faker->email();
        $sessionId = $this->faker->uuid();
        $recoveryCodes = $this->defaultRecoveryCodes();
        $this->mockAuthenticatedUser($email, $sessionId);
        $this->expectDispatchWithAssertions($email, $sessionId, $recoveryCodes);
        $response = $this->processRecoveryCodes();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $body = json_decode((string) $response->getContent(), true);
        $this->assertSame($recoveryCodes, $body['recovery_codes']);
    }

    public function testProcessUsesEmptySessionIdWhenTokenMissing(): void
    {
        $this->mockUserWithNullToken($this->faker->email());
        $this->expectDispatchWithEmptySessionResponse();
        $response = $this->processRecoveryCodes();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testProcessUsesEmptySessionIdWhenSidIsNotString(): void
    {
        $this->mockUserWithIntegerSid($this->faker->email());
        $this->expectDispatchWithEmptySessionResponse();
        $this->processRecoveryCodes();
    }

    public function testProcessThrows401WhenUserIsMissing(): void
    {
        $this->security->method('getUser')->willReturn(null);
        $this->security->method('getToken')->willReturn(null);

        $this->commandBus
            ->expects($this->never())
            ->method('dispatch');

        $processor = new RegenerateRecoveryCodesProcessor(
            $this->commandBus,
            new CurrentUserIdentityResolver($this->security),
            new RegenerateRecoveryCodesCommandFactory(),
        );

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Authentication required.');

        $processor->process(new RegenerateRecoveryCodesDto(), $this->operation);
    }

    private function mockAuthenticatedUser(
        string $email,
        string $sessionId
    ): void {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn($email);
        $this->security->method('getUser')->willReturn($user);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getAttribute')
            ->with('sid')
            ->willReturn($sessionId);

        $this->security->method('getToken')->willReturn($token);
    }

    /**
     * @return array<string>
     */
    private function defaultRecoveryCodes(): array
    {
        return [
            'AB12-CD34', 'EF56-GH78',
            'IJ90-KL12', 'MN34-OP56',
            'QR78-ST90', 'UV12-WX34',
            'YZ56-AB78', 'CD90-EF12',
        ];
    }

    /**
     * @param array<string> $codes
     */
    private function expectDispatchWithAssertions(
        string $email,
        string $sessionId,
        array $codes
    ): void {
        $this->commandBus->expects($this->once())->method('dispatch')
            ->with($this->callback(
                function (RegenerateRecoveryCodesCommand $cmd) use (
                    $email,
                    $sessionId,
                    $codes
                ): bool {
                    $this->assertSame($email, $cmd->userEmail);
                    $this->assertSame($sessionId, $cmd->currentSessionId);
                    $cmd->setResponse(new RegenerateRecoveryCodesCommandResponse($codes));
                    return true;
                }
            ));
    }

    private function expectDispatchWithEmptySessionResponse(): void
    {
        $this->commandBus->expects($this->once())->method('dispatch')
            ->with($this->callback(
                function (RegenerateRecoveryCodesCommand $cmd): bool {
                    $this->assertSame('', $cmd->currentSessionId);
                    $cmd->setResponse(new RegenerateRecoveryCodesCommandResponse([]));
                    return true;
                }
            ));
    }

    private function processRecoveryCodes(): mixed
    {
        $processor = new RegenerateRecoveryCodesProcessor(
            $this->commandBus,
            new CurrentUserIdentityResolver($this->security),
            new RegenerateRecoveryCodesCommandFactory()
        );
        return $processor->process(new RegenerateRecoveryCodesDto(), $this->operation);
    }

    private function mockUserWithNullToken(string $email): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn($email);
        $this->security->method('getUser')->willReturn($user);
        $this->security->method('getToken')->willReturn(null);
    }

    private function mockUserWithIntegerSid(string $email): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn($email);
        $this->security->method('getUser')->willReturn($user);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getAttribute')->with('sid')->willReturn(123);
        $this->security->method('getToken')->willReturn($token);
    }
}
