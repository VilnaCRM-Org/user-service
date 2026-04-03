<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Application\Controller;

use App\OAuth\Application\Command\HandleOAuthCallbackCommand;
use App\OAuth\Application\Controller\OAuthCallbackController;
use App\OAuth\Application\DTO\HandleOAuthCallbackResponse;
use App\OAuth\Application\Factory\OAuthFlowCookieFactory;
use App\OAuth\Domain\Exception\MissingOAuthParametersException;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Factory\AuthCookieFactoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class OAuthCallbackControllerTest extends UnitTestCase
{
    private CommandBusInterface&MockObject $commandBus;
    private AuthCookieFactoryInterface&MockObject $authCookieFactory;
    private OAuthCallbackController $controller;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->authCookieFactory = $this->createMock(
            AuthCookieFactoryInterface::class
        );

        $this->controller = new OAuthCallbackController(
            $this->commandBus,
            $this->authCookieFactory,
        );
    }

    public function testInvokeReturnsJsonResponseOnSuccess(): void
    {
        $this->arrangeDirectSignIn();

        $response = $this->invokeController();

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testInvokeReturnsAccessTokenForDirectSignIn(): void
    {
        $accessToken = $this->faker->sha256();
        $refreshToken = $this->faker->sha256();

        $this->arrangeCommandBus(
            new HandleOAuthCallbackResponse(false, $accessToken, $refreshToken)
        );
        $this->arrangeAuthCookie();

        $response = $this->invokeController();
        $body = $this->decodeResponse($response);

        $this->assertFalse($body['2fa_enabled']);
        $this->assertSame($accessToken, $body['access_token']);
        $this->assertSame($refreshToken, $body['refresh_token']);
    }

    public function testInvokeReturnsPendingSessionForTwoFactor(): void
    {
        $pendingSessionId = $this->faker->uuid();

        $this->arrangeCommandBus(
            new HandleOAuthCallbackResponse(
                true,
                null,
                null,
                $pendingSessionId
            )
        );

        $response = $this->invokeController();
        $body = $this->decodeResponse($response);

        $this->assertTrue($body['2fa_enabled']);
        $this->assertSame($pendingSessionId, $body['pending_session_id']);
        $this->assertArrayNotHasKey('access_token', $body);
    }

    public function testInvokeSetsAuthCookieOnDirectSignIn(): void
    {
        $accessToken = $this->faker->sha256();

        $this->arrangeCommandBus(
            new HandleOAuthCallbackResponse(
                false,
                $accessToken,
                $this->faker->sha256()
            )
        );

        $cookie = Cookie::create('auth', $accessToken);
        $this->authCookieFactory->expects($this->once())
            ->method('create')
            ->with($accessToken, false)
            ->willReturn($cookie);

        $response = $this->invokeController();
        $cookies = $response->headers->getCookies();

        $this->assertContains($cookie, $cookies);
    }

    public function testInvokeDoesNotSetAuthCookieForTwoFactor(): void
    {
        $this->arrangeCommandBus(
            new HandleOAuthCallbackResponse(
                true,
                null,
                null,
                $this->faker->uuid()
            )
        );

        $this->authCookieFactory->expects($this->never())
            ->method('create');

        $this->invokeController();
    }

    public function testInvokeSetsPragmaNoCacheHeader(): void
    {
        $this->arrangeDirectSignIn();

        $response = $this->invokeController();

        $this->assertSame('no-cache', $response->headers->get('Pragma'));
        $this->assertStringContainsString(
            'no-store',
            (string) $response->headers->get('Cache-Control')
        );
    }

    public function testInvokeThrowsOnMissingCode(): void
    {
        $this->expectException(MissingOAuthParametersException::class);

        $this->invokeController(code: '');
    }

    public function testInvokeThrowsOnMissingState(): void
    {
        $this->expectException(MissingOAuthParametersException::class);

        $this->invokeController(state: '');
    }

    public function testInvokeThrowsOnMissingCookie(): void
    {
        $this->expectException(MissingOAuthParametersException::class);

        $this->invokeController(flowBindingToken: '');
    }

    public function testInvokeDispatchesCommandWithCorrectProvider(): void
    {
        $provider = $this->faker->word();

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(
                static fn (HandleOAuthCallbackCommand $cmd): bool => $cmd->provider === $provider
            ));

        $this->arrangeDirectSignIn();

        $this->invokeController(provider: $provider);
    }

    public function testInvokeDispatchesCommandWithCode(): void
    {
        $code = $this->faker->sha256();

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(
                static fn (HandleOAuthCallbackCommand $cmd): bool => $cmd->code === $code
            ));

        $this->arrangeDirectSignIn();

        $this->invokeController(code: $code);
    }

    public function testInvokeDispatchesCommandWithState(): void
    {
        $state = $this->faker->sha256();

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(
                static fn (HandleOAuthCallbackCommand $cmd): bool => $cmd->state === $state
            ));

        $this->arrangeDirectSignIn();

        $this->invokeController(state: $state);
    }

    public function testInvokeDispatchesCommandWithFlowBinding(): void
    {
        $token = $this->faker->sha256();
        $check = static fn (HandleOAuthCallbackCommand $cmd): bool => $cmd->flowBindingToken === $token;

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback($check));

        $this->arrangeDirectSignIn();

        $this->invokeController(flowBindingToken: $token);
    }

    private function arrangeDirectSignIn(): void
    {
        $this->arrangeCommandBus(
            new HandleOAuthCallbackResponse(
                false,
                $this->faker->sha256(),
                $this->faker->sha256(),
            )
        );
        $this->arrangeAuthCookie();
    }

    private function arrangeCommandBus(
        HandleOAuthCallbackResponse $responseDto,
    ): void {
        $this->commandBus->method('dispatch')
            ->willReturnCallback(
                static function (HandleOAuthCallbackCommand $command) use ($responseDto): void {
                    $command->setResponse($responseDto);
                }
            );
    }

    private function arrangeAuthCookie(): void
    {
        $this->authCookieFactory->method('create')
            ->willReturn(Cookie::create('auth', $this->faker->sha256()));
    }

    private function invokeController(
        ?string $provider = null,
        ?string $code = null,
        ?string $state = null,
        ?string $flowBindingToken = null,
    ): Response {
        $provider ??= $this->faker->word();
        $code ??= $this->faker->sha256();
        $state ??= $this->faker->sha256();
        $flowBindingToken ??= $this->faker->sha256();

        $request = Request::create(
            sprintf(
                'https://example.com/api/auth/social/%s/callback?code=%s&state=%s',
                $provider,
                $code,
                $state,
            ),
            'GET',
        );

        $request->cookies->set(
            OAuthFlowCookieFactory::COOKIE_NAME,
            $flowBindingToken,
        );

        return ($this->controller)($provider, $request);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeResponse(Response $response): array
    {
        return json_decode(
            (string) $response->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }
}
