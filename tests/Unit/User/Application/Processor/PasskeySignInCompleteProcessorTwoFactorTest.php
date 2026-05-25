<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Domain\Bus\Command\CommandInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\CompletePasskeySignInCommand;
use App\User\Application\DTO\PasskeyAuthenticationResult;
use App\User\Application\DTO\PasskeySignInCompleteDto;
use App\User\Application\Factory\AuthCookieFactoryInterface;
use App\User\Application\Factory\PasskeyResponseFactory;
use App\User\Application\Processor\PasskeySignInCompleteProcessor;
use App\User\Application\Resolver\HttpRequestContextResolverInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class PasskeySignInCompleteProcessorTwoFactorTest extends UnitTestCase
{
    private Operation $operation;
    private CommandBusInterface&MockObject $commandBus;
    private HttpRequestContextResolverInterface&MockObject $requestContextResolver;
    private AuthCookieFactoryInterface&MockObject $authCookieFactory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->operation = $this->createMock(Operation::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->requestContextResolver = $this->createMock(
            HttpRequestContextResolverInterface::class
        );
        $this->authCookieFactory = $this->createMock(AuthCookieFactoryInterface::class);
    }

    public function testSignInCompleteProcessorReturnsPendingTwoFactorWithoutCookie(): void
    {
        $request = new Request();
        $challengeId = $this->faker->uuid();
        $credentialPayload = ['id' => $this->faker->uuid()];
        $pendingSessionId = $this->faker->uuid();

        $this->expectRequestContext($request);
        $this->expectCommandDispatch($this->createTwoFactorResult($pendingSessionId));
        $this->authCookieFactory->expects($this->never())->method('create');

        $response = $this->createProcessor()->process(
            new PasskeySignInCompleteDto($challengeId, $credentialPayload),
            $this->operation,
            [],
            ['request' => $request]
        );
        $payload = json_decode((string) $response->getContent(), true);

        $this->assertPendingTwoFactorResponse($response, $payload, $pendingSessionId);
    }

    private function createTwoFactorResult(string $pendingSessionId): PasskeyAuthenticationResult
    {
        return new PasskeyAuthenticationResult('', '', true, '', $pendingSessionId);
    }

    /**
     * @param array<string, bool|string> $payload
     */
    private function assertPendingTwoFactorResponse(
        Response $response,
        array $payload,
        string $pendingSessionId
    ): void {
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertTrue($payload['2fa_enabled']);
        self::assertSame($pendingSessionId, $payload['pending_session_id']);
        self::assertArrayNotHasKey('access_token', $payload);
        self::assertCount(0, $response->headers->getCookies());
    }

    private function createProcessor(): PasskeySignInCompleteProcessor
    {
        return new PasskeySignInCompleteProcessor(
            $this->commandBus,
            new PasskeyResponseFactory(),
            $this->requestContextResolver,
            $this->authCookieFactory
        );
    }

    private function expectRequestContext(Request $request): void
    {
        $this->requestContextResolver->expects($this->once())
            ->method('resolveRequest')
            ->with($request)
            ->willReturn($request);
        $this->requestContextResolver->expects($this->once())
            ->method('resolveIpAddress')
            ->with($request)
            ->willReturn($this->faker->ipv4());
        $this->requestContextResolver->expects($this->once())
            ->method('resolveUserAgent')
            ->with($request)
            ->willReturn($this->faker->userAgent());
    }

    private function expectCommandDispatch(PasskeyAuthenticationResult $response): void
    {
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with(self::callback(static function (
                CommandInterface $command
            ) use ($response): bool {
                self::assertInstanceOf(CompletePasskeySignInCommand::class, $command);
                $command->setResponse($response);

                return true;
            }));
    }
}
