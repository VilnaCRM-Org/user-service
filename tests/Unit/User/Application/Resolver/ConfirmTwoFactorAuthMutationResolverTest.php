<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Resolver;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\ConfirmTwoFactorCommand;
use App\User\Application\DTO\ConfirmTwoFactorCommandResponse;
use App\User\Application\DTO\ConfirmTwoFactorDto;
use App\User\Application\Factory\ConfirmTwoFactorCommandFactoryInterface;
use App\User\Application\Resolver\ConfirmTwoFactorAuthMutationResolver;
use App\User\Application\Validator\MutationInputValidator;

final class ConfirmTwoFactorAuthMutationResolverTest extends AuthMutationResolverTestCase
{
    private MutationInputValidator $validator;
    private CommandBusInterface $commandBus;
    private ConfirmTwoFactorCommandFactoryInterface $commandFactory;
    private ConfirmTwoFactorAuthMutationResolver $resolver;
    private ConfirmTwoFactorCommand $command;
    private string $email;
    private string $sessionId;
    private string $twoFactorCode;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDependencies();
        $this->setUpScenario();
        $this->resolver = new ConfirmTwoFactorAuthMutationResolver(
            $this->validator,
            $this->commandBus,
            $this->authPayloadFactory(),
            $this->currentUserIdentityResolver(
                $this->email,
                $this->sessionId,
                $this->faker->uuid()
            ),
            $this->commandFactory
        );
    }

    public function testInvokeDispatchesCommandAndBuildsPayload(): void
    {
        $this->expectResolver();

        $result = $this->resolver->__invoke(null, $this->context());

        $this->assertSame('auth-confirm-two-factor', $result->getId());
        $this->assertSame(
            $this->command->getResponse()->getRecoveryCodes(),
            $result->getRecoveryCodes()
        );
    }

    private function setUpDependencies(): void
    {
        $this->validator = $this->createMock(MutationInputValidator::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->commandFactory = $this->createMock(
            ConfirmTwoFactorCommandFactoryInterface::class
        );
    }

    private function setUpScenario(): void
    {
        $this->email = $this->faker->email();
        $this->sessionId = $this->faker->uuid();
        $this->twoFactorCode = (string) $this->faker->numberBetween(100000, 999999);
        $this->command = new ConfirmTwoFactorCommand(
            $this->email,
            $this->twoFactorCode,
            $this->sessionId
        );
        $this->command->setResponse(
            new ConfirmTwoFactorCommandResponse(
                [$this->faker->sha1(), $this->faker->sha1()]
            )
        );
    }

    private function expectResolver(): void
    {
        $twoFactorCode = $this->twoFactorCode;
        $this->validator->expects($this->once())
            ->method('validate')
            ->with(
                $this->callback(
                    static fn (object $dto): bool => $dto instanceof ConfirmTwoFactorDto
                        && $dto->twoFactorCodeValue() === $twoFactorCode
                )
            );
        $this->commandFactory->expects($this->once())
            ->method('create')
            ->with($this->email, $this->twoFactorCode, $this->sessionId)
            ->willReturn($this->command);
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->command);
    }

    /**
     * @return array<string, array<string, array<string, string>>>
     */
    private function context(): array
    {
        return [
            'args' => [
                'input' => [
                    'twoFactorCode' => $this->twoFactorCode,
                ],
            ],
        ];
    }
}
