<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RegenerateRecoveryCodesCommand;
use App\User\Application\Command\RegenerateRecoveryCodesCommandResponse;
use App\User\Application\DTO\RegenerateRecoveryCodesDto;
use App\User\Application\Processor\RegenerateRecoveryCodesProcessor;
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
        $recoveryCodes = ['AB12-CD34', 'EF56-GH78', 'IJ90-KL12', 'MN34-OP56', 'QR78-ST90', 'UV12-WX34', 'YZ56-AB78', 'CD90-EF12'];

        $this->mockAuthenticatedUser($email, $sessionId);

        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(/**
             * @return true
             */
            function (RegenerateRecoveryCodesCommand $command) use ($email, $sessionId, $recoveryCodes): bool {
                $this->assertSame($email, $command->userEmail);
                $this->assertSame($sessionId, $command->currentSessionId);

                $command->setResponse(
                    new RegenerateRecoveryCodesCommandResponse($recoveryCodes)
                );

                return true;
            }));

        $processor = new RegenerateRecoveryCodesProcessor(
            $this->commandBus,
            $this->security
        );

        $response = $processor->process(
            new RegenerateRecoveryCodesDto(),
            $this->operation
        );

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $body = json_decode((string) $response->getContent(), true);
        $this->assertSame($recoveryCodes, $body['recovery_codes']);
    }

    public function testProcessUsesEmptySessionIdWhenTokenMissing(): void
    {
        $email = $this->faker->email();

        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn($email);
        $this->security->method('getUser')->willReturn($user);
        $this->security->method('getToken')->willReturn(null);

        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(/**
             * @return true
             */
            function (RegenerateRecoveryCodesCommand $command): bool {
                $this->assertSame('', $command->currentSessionId);

                $command->setResponse(
                    new RegenerateRecoveryCodesCommandResponse([])
                );

                return true;
            }));

        $processor = new RegenerateRecoveryCodesProcessor(
            $this->commandBus,
            $this->security
        );

        $response = $processor->process(
            new RegenerateRecoveryCodesDto(),
            $this->operation
        );

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testProcessUsesEmptySessionIdWhenSidIsNotString(): void
    {
        $email = $this->faker->email();

        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn($email);
        $this->security->method('getUser')->willReturn($user);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getAttribute')->with('sid')->willReturn(123);
        $this->security->method('getToken')->willReturn($token);

        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(/**
             * @return true
             */
            function (RegenerateRecoveryCodesCommand $command): bool {
                $this->assertSame('', $command->currentSessionId);

                $command->setResponse(
                    new RegenerateRecoveryCodesCommandResponse([])
                );

                return true;
            }));

        $processor = new RegenerateRecoveryCodesProcessor(
            $this->commandBus,
            $this->security
        );

        $processor->process(new RegenerateRecoveryCodesDto(), $this->operation);
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
            $this->security
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
}
