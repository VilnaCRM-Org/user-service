<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Resolver;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RequestPasswordResetCommand;
use App\User\Application\Command\RequestPasswordResetCommandResponse;
use App\User\Application\Resolver\RequestPasswordResetMutationResolver;
use App\User\Application\Validator\MutationInputValidator;

final class RequestPasswordResetMutationResolverTest extends UnitTestCase
{
    private CommandBusInterface $commandBus;
    private MutationInputValidator $validator;
    private RequestPasswordResetMutationResolver $resolver;

    #[\Override]
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
        $context = $this->createContext($email);

        $this->validator->expects($this->once())
            ->method('validate');

        $this->expectCommandDispatch($email);

        $result = $this->resolver->__invoke(null, $context);

        $this->assertNull($result);
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
                $response = new RequestPasswordResetCommandResponse();
                $command->setResponse($response);

                return true;
            }));

        $result = $this->resolver->__invoke(null, $context);

        $this->assertNull($result);
    }

    /**
     * @return string[][][]
     *
     * @psalm-return array{args: array{input: array{email: string}}}
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

    private function expectCommandDispatch(string $email): void
    {
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (RequestPasswordResetCommand $command) use ($email) {
                $this->assertSame($email, $command->email);

                $response = new RequestPasswordResetCommandResponse();
                $command->setResponse($response);

                return true;
            }));
    }
}
