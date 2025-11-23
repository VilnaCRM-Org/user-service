<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Resolver;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\ConfirmPasswordResetCommand;
use App\User\Application\Command\ConfirmPasswordResetCommandResponse;
use App\User\Application\MutationInput\MutationInputValidator;
use App\User\Application\Resolver\ConfirmPasswordResetMutationResolver;

final class ConfirmPasswordResetMutationResolverTest extends UnitTestCase
{
    private CommandBusInterface $commandBus;
    private MutationInputValidator $validator;
    private ConfirmPasswordResetMutationResolver $resolver;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->validator = $this->createMock(MutationInputValidator::class);

        $this->resolver = new ConfirmPasswordResetMutationResolver(
            $this->commandBus,
            $this->validator
        );
    }

    public function testInvokeSuccessfully(): void
    {
        $token = $this->faker->sha256();
        $newPassword = $this->faker->password();
        $message = 'Password has been reset successfully.';

        $context = $this->createContext($token, $newPassword);

        $this->expectValidationCall();
        $this->expectCommandDispatch($token, $newPassword, $message);

        $result = $this->resolver->__invoke(null, $context);

        $this->assertNull($result);
    }

    public function testInvokeWithMissingArgs(): void
    {
        $context = [
            'args' => [
                'input' => [
                    'token' => '',
                    'newPassword' => '',
                ],
            ],
        ];

        $this->validator->expects($this->once())
            ->method('validate');

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static function (ConfirmPasswordResetCommand $command) {
                $response = new ConfirmPasswordResetCommandResponse('Success');
                $command->setResponse($response);

                return true;
            }));

        $result = $this->resolver->__invoke(null, $context);

        $this->assertNull($result);
    }

    /**
     * @return array<string, array<string, array<string, string>>>
     */
    private function createContext(string $token, string $newPassword): array
    {
        return [
            'args' => [
                'input' => [
                    'token' => $token,
                    'newPassword' => $newPassword,
                ],
            ],
        ];
    }

    private function expectValidationCall(): void
    {
        $this->validator->expects($this->once())
            ->method('validate');
    }

    private function expectCommandDispatch(string $token, string $newPassword, string $message): void
    {
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (ConfirmPasswordResetCommand $command) use ($token, $newPassword, $message) {
                $this->assertSame($token, $command->token);
                $this->assertSame($newPassword, $command->newPassword);

                $response = new ConfirmPasswordResetCommandResponse($message);
                $command->setResponse($response);

                return true;
            }));
    }
}
