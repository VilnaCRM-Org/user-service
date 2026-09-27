<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Resolver;

use App\Shared\Application\Bus\Guard\CommandResponseTypeGuard;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\Command\CompleteTwoFactorCommand;
use App\User\Application\DTO\AuthPayload;
use App\User\Application\DTO\CompleteTwoFactorCommandResponse;
use App\User\Application\DTO\CompleteTwoFactorDto;
use App\User\Application\Factory\CompleteTwoFactorCommandFactoryInterface;
use App\User\Application\Resolver\CompleteTwoFactorAuthMutationResolver;
use App\User\Application\Resolver\HttpRequestContextResolverInterface;
use App\User\Application\Service\CompleteTwoFactorCommandDispatcher;
use App\User\Application\Validator\MutationInputValidator;
use Symfony\Component\HttpFoundation\Request;

final class CompleteTwoFactorAuthMutationResolverTest extends AuthMutationResolverTestCase
{
    private MutationInputValidator $validator;
    private CommandBusInterface $commandBus;
    private CompleteTwoFactorCommandFactoryInterface $commandFactory;
    private HttpRequestContextResolverInterface $requestContextResolver;
    private CompleteTwoFactorAuthMutationResolver $resolver;
    private CompleteTwoFactorCommand $command;
    private CompleteTwoFactorCommandResponse $commandResponse;
    private Request $request;
    private string $pendingSessionId;
    private string $twoFactorCode;
    private string $ipAddress;
    private string $userAgent;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDependencies();
        $this->setUpScenario();
        $this->resolver = new CompleteTwoFactorAuthMutationResolver(
            $this->validator,
            $this->authPayloadFactory(),
            new CompleteTwoFactorCommandDispatcher(
                $this->commandBus,
                new CommandResponseTypeGuard(),
                $this->commandFactory,
                $this->requestContextResolver
            )
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
        $this->commandFactory = $this->createMock(
            CompleteTwoFactorCommandFactoryInterface::class
        );
        $this->requestContextResolver = $this->createMock(
            HttpRequestContextResolverInterface::class
        );
    }

    private function setUpScenario(): void
    {
        $this->pendingSessionId = $this->faker->uuid();
        $this->twoFactorCode = (string) $this->faker->numberBetween(100000, 999999);
        $this->ipAddress = $this->faker->ipv4();
        $this->userAgent = $this->faker->userAgent();
        $this->request = Request::create('/api/graphql');
        $this->command = new CompleteTwoFactorCommand(
            $this->pendingSessionId,
            $this->twoFactorCode,
            $this->ipAddress,
            $this->userAgent
        );
        $this->commandResponse = $this->response();
    }

    private function expectValidation(): void
    {
        $this->validator->expects($this->once())
            ->method('validate')
            ->with($this->callback(fn (object $dto): bool => $dto instanceof CompleteTwoFactorDto
                && $dto->pendingSessionIdValue() === $this->pendingSessionId
                && $dto->twoFactorCodeValue() === $this->twoFactorCode));
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
                $this->pendingSessionId,
                $this->twoFactorCode,
                $this->ipAddress,
                $this->userAgent
            )
            ->willReturn($this->command);
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->command)
            ->willReturn($this->commandResponse);
    }

    /**
     * @return array<string, array<string, array<string, string>|Request>>
     */
    private function context(): array
    {
        return [
            'args' => [
                'input' => [
                    'pendingSessionId' => $this->pendingSessionId,
                    'twoFactorCode' => $this->twoFactorCode,
                ],
            ],
            'request' => $this->request,
        ];
    }

    private function response(): CompleteTwoFactorCommandResponse
    {
        return new CompleteTwoFactorCommandResponse(
            $this->faker->sha256(),
            $this->faker->sha256(),
            2,
            'Use recovery codes soon.'
        );
    }

    private function assertPayload(AuthPayload $payload): void
    {
        $this->assertSame('auth-complete-two-factor', $payload->getId());
        $this->assertTrue($payload->isTwoFactorEnabled());
        $this->assertSame($this->commandResponse->getAccessToken(), $payload->getAccessToken());
        $this->assertSame($this->commandResponse->getRefreshToken(), $payload->getRefreshToken());
        $this->assertSame(2, $payload->getRecoveryCodesRemaining());
        $this->assertSame('Use recovery codes soon.', $payload->getWarning());
    }
}
