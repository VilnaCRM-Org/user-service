<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Resolver;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\SignInCommand;
use App\User\Application\DTO\AuthPayload;
use App\User\Application\DTO\SignInCommandResponse;
use App\User\Application\DTO\SignInDto;
use App\User\Application\Factory\SignInCommandFactoryInterface;
use App\User\Application\Resolver\HttpRequestContextResolverInterface;
use App\User\Application\Resolver\SignInAuthMutationResolver;
use App\User\Application\Validator\MutationInputValidator;
use Symfony\Component\HttpFoundation\Request;

final class SignInAuthMutationResolverTest extends AuthMutationResolverTestCase
{
    private MutationInputValidator $validator;
    private CommandBusInterface $commandBus;
    private SignInCommandFactoryInterface $commandFactory;
    private HttpRequestContextResolverInterface $requestContextResolver;
    private SignInAuthMutationResolver $resolver;
    private SignInCommand $command;
    private Request $request;
    private string $email;
    private string $password;
    private string $ipAddress;
    private string $userAgent;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDependencies();
        $this->setUpScenario();
        $this->resolver = new SignInAuthMutationResolver(
            $this->validator,
            $this->commandBus,
            $this->authPayloadFactory(),
            $this->commandFactory,
            $this->requestContextResolver
        );
    }

    public function testInvokeDispatchesCommandAndBuildsPayload(): void
    {
        $this->expectValidation();
        $this->expectRequestContextResolution();
        $this->expectCommandCreation();

        $result = $this->resolver->__invoke(null, $this->context());

        $this->assertPayload($result);
    }

    private function setUpDependencies(): void
    {
        $this->validator = $this->createMock(MutationInputValidator::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->commandFactory =
            $this->createMock(SignInCommandFactoryInterface::class);
        $this->requestContextResolver =
            $this->createMock(HttpRequestContextResolverInterface::class);
    }

    private function setUpScenario(): void
    {
        $this->email = $this->faker->email();
        $this->password = $this->faker->password();
        $this->ipAddress = $this->faker->ipv4();
        $this->userAgent = $this->faker->userAgent();
        $this->request = Request::create('/api/graphql');
        $this->command = new SignInCommand(
            $this->email,
            $this->password,
            true,
            $this->ipAddress,
            $this->userAgent
        );
        $this->command->setResponse($this->response());
    }

    private function expectValidation(): void
    {
        $this->validator->expects($this->once())
            ->method('validate')
            ->with($this->callback(fn (object $dto): bool => $dto instanceof SignInDto
                && $dto->emailValue() === $this->email
                && $dto->passwordValue() === $this->password
                && $dto->isRememberMe()));
    }

    private function expectRequestContextResolution(): void
    {
        $this->requestContextResolver->expects($this->once())
            ->method('resolveRequest')
            ->with($this->request)
            ->willReturn($this->request);
        $this->requestContextResolver->expects($this->once())
            ->method('resolveIpAddress')
            ->with($this->request)
            ->willReturn($this->ipAddress);
        $this->requestContextResolver->expects($this->once())
            ->method('resolveUserAgent')
            ->with($this->request)
            ->willReturn($this->userAgent);
    }

    private function expectCommandCreation(): void
    {
        $this->commandFactory->expects($this->once())
            ->method('create')
            ->with(
                $this->email,
                $this->password,
                true,
                $this->ipAddress,
                $this->userAgent
            )
            ->willReturn($this->command);
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->command);
    }

    /**
     * @return array<string, array<string, array<string, bool|string>|Request>>
     */
    private function context(): array
    {
        return [
            'args' => [
                'input' => [
                    'email' => $this->email,
                    'password' => $this->password,
                    'rememberMe' => true,
                ],
            ],
            'request' => $this->request,
        ];
    }

    private function response(): SignInCommandResponse
    {
        return new SignInCommandResponse(
            false,
            $this->faker->sha256(),
            $this->faker->sha256(),
            $this->faker->uuid()
        );
    }

    private function assertPayload(AuthPayload $payload): void
    {
        $response = $this->command->getResponse();
        $this->assertSame('auth-sign-in', $payload->getId());
        $this->assertFalse($payload->isTwoFactorEnabled());
        $this->assertSame($response->getAccessToken(), $payload->getAccessToken());
        $this->assertSame($response->getRefreshToken(), $payload->getRefreshToken());
        $this->assertSame(
            $response->getPendingSessionId(),
            $payload->getPendingSessionId()
        );
    }
}
