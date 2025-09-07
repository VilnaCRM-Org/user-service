<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Resolver;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RequestPasswordResetCommand;
use App\User\Application\Command\RequestPasswordResetCommandResponse;
use App\User\Application\MutationInput\MutationInputValidator;
use App\User\Application\Resolver\RequestPasswordResetMutationResolver;

final class RequestPasswordResetMutationResolverTest extends UnitTestCase
{
    private CommandBusInterface $commandBus;
    private MutationInputValidator $validator;
    private RequestPasswordResetMutationResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->validator = $this->createMock(MutationInputValidator::class);

        $this->resolver = new RequestPasswordResetMutationResolver(
            $this->commandBus,
            $this->validator
        );
    }

    public function testInvokeSuccessfully(): void
    {
        $email = $this->faker->safeEmail();
        $message = 'Password reset email sent successfully.';
        $context = $this->createContext($email);

        $this->validator->expects($this->once())
            ->method('validate');

        $this->expectCommandDispatch($email, $message);

        $result = $this->resolver->__invoke(null, $context);

        $this->assertInstanceOf(\App\User\Application\DTO\PasswordResetPayload::class, $result);
        $this->assertTrue($result->ok);
    }

    public function testInvokeWithMissingEmail(): void
    {
        $context = [
            'args' => [
                'input' => [
                    'email' => '',
                ],
            ],
        ];

        $this->validator->expects($this->once())
            ->method('validate');

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static function (RequestPasswordResetCommand $command) {
                // Mock the response
                $response = new RequestPasswordResetCommandResponse('Success');
                $command->setResponse($response);

                return true;
            }));

        $result = $this->resolver->__invoke(null, $context);

        $this->assertIsObject($result);
    }

    /**
     * @return array<string, array<string, array<string, string>>>
     */
    private function createContext(string $email): array
    {
        return [
            'args' => [
                'input' => [
                    'email' => $email,
                ],
            ],
        ];
    }

    private function expectCommandDispatch(string $email, string $message): void
    {
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (RequestPasswordResetCommand $command) use ($email, $message) {
                $this->assertSame($email, $command->email);

                // Mock the response
                $response = new RequestPasswordResetCommandResponse($message);
                $command->setResponse($response);

                return true;
            }));
    }
}
