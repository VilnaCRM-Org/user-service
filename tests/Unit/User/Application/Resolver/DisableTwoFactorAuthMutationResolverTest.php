<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Resolver;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\DisableTwoFactorCommand;
use App\User\Application\DTO\DisableTwoFactorDto;
use App\User\Application\Factory\DisableTwoFactorCommandFactoryInterface;
use App\User\Application\Resolver\DisableTwoFactorAuthMutationResolver;
use App\User\Application\Validator\MutationInputValidator;

final class DisableTwoFactorAuthMutationResolverTest extends AuthMutationResolverTestCase
{
    private MutationInputValidator $validator;
    private CommandBusInterface $commandBus;
    private DisableTwoFactorCommandFactoryInterface $commandFactory;
    private DisableTwoFactorAuthMutationResolver $resolver;
    private DisableTwoFactorCommand $command;
    private string $email;
    private string $twoFactorCode;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDependencies();
        $this->setUpScenario();
        $this->resolver = new DisableTwoFactorAuthMutationResolver(
            $this->validator,
            $this->commandBus,
            $this->authPayloadFactory(),
            $this->currentUserIdentityResolver(
                $this->email,
                '',
                $this->faker->uuid()
            ),
            $this->commandFactory
        );
    }

    public function testInvokeDispatchesCommandAndReturnsSuccessPayload(): void
    {
        $this->expectResolver();

        $payload = $this->resolver->__invoke(null, $this->context());

        $this->assertSame('auth-success', $payload->getId());
        $this->assertTrue($payload->isSuccess());
    }

    private function setUpDependencies(): void
    {
        $this->validator = $this->createMock(MutationInputValidator::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->commandFactory = $this->createMock(
            DisableTwoFactorCommandFactoryInterface::class
        );
    }

    private function setUpScenario(): void
    {
        $this->email = $this->faker->email();
        $this->twoFactorCode = (string) $this->faker->numberBetween(100000, 999999);
        $this->command = new DisableTwoFactorCommand(
            $this->email,
            $this->twoFactorCode
        );
    }

    private function expectResolver(): void
    {
        $twoFactorCode = $this->twoFactorCode;
        $this->validator->expects($this->once())
            ->method('validate')
            ->with(
                $this->callback(
                    static fn (object $dto): bool => $dto instanceof DisableTwoFactorDto
                        && $dto->twoFactorCodeValue() === $twoFactorCode
                )
            );
        $this->commandFactory->expects($this->once())
            ->method('create')
            ->with($this->email, $this->twoFactorCode)
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
