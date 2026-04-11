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
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class OAuthCallbackControllerEdgeCaseTest extends UnitTestCase
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

    public function testInvokeDoesNotSetAuthCookieForTwoFactorWhenAccessTokenExists(): void
    {
        $this->arrangeCommandBus(
            new HandleOAuthCallbackResponse(
                true,
                $this->faker->sha256(),
                $this->faker->sha256(),
                $this->faker->uuid(),
            )
        );

        $this->authCookieFactory->expects($this->never())
            ->method('create');

        $response = $this->invokeController();
        $body = $this->decodeResponse($response);

        $this->assertTrue($body['2fa_enabled']);
        $this->assertArrayNotHasKey('access_token', $body);
    }

    public function testInvokeThrowsOnAbsentCode(): void
    {
        $provider = $this->faker->word();
        $state = $this->faker->sha256();
        $request = Request::create(
            sprintf(
                'https://example.com/api/auth/social/%s/callback?state=%s',
                $provider,
                $state,
            ),
            'GET',
        );
        $request->cookies->set(
            OAuthFlowCookieFactory::COOKIE_NAME,
            $this->faker->sha256(),
        );

        $this->expectException(MissingOAuthParametersException::class);
        $this->expectExceptionMessage(
            'Missing required OAuth parameters: code, state, or flow-binding cookie'
        );

        ($this->controller)($provider, $request);
    }

    public function testInvokeThrowsOnAbsentState(): void
    {
        $provider = $this->faker->word();
        $code = $this->faker->sha256();
        $request = Request::create(
            sprintf(
                'https://example.com/api/auth/social/%s/callback?code=%s',
                $provider,
                $code,
            ),
            'GET',
        );
        $request->cookies->set(
            OAuthFlowCookieFactory::COOKIE_NAME,
            $this->faker->sha256(),
        );

        $this->expectException(MissingOAuthParametersException::class);
        $this->expectExceptionMessage(
            'Missing required OAuth parameters: code, state, or flow-binding cookie'
        );

        ($this->controller)($provider, $request);
    }

    public function testInvokeThrowsOnAbsentFlowBindingCookie(): void
    {
        $provider = $this->faker->word();
        $code = $this->faker->sha256();
        $state = $this->faker->sha256();
        $request = Request::create(
            sprintf(
                'https://example.com/api/auth/social/%s/callback?code=%s&state=%s',
                $provider,
                $code,
                $state,
            ),
            'GET',
        );

        $this->expectException(MissingOAuthParametersException::class);
        $this->expectExceptionMessage(
            'Missing required OAuth parameters: code, state, or flow-binding cookie'
        );

        ($this->controller)($provider, $request);
    }

    public function testInvokeDispatchesCommandWithFallbackIpAddress(): void
    {
        $provider = $this->faker->word();
        $code = $this->faker->sha256();
        $state = $this->faker->sha256();
        $flowBindingToken = $this->faker->sha256();
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
        $request->server->remove('REMOTE_ADDR');

        $responseDto = new HandleOAuthCallbackResponse(
            false,
            $this->faker->sha256(),
            $this->faker->sha256(),
        );
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(
                static fn (HandleOAuthCallbackCommand $command): bool =>
                    $command->ipAddress === '0.0.0.0'
            ))
            ->willReturnCallback(
                static function (HandleOAuthCallbackCommand $command) use ($responseDto): void {
                    $command->setResponse($responseDto);
                }
            );
        $this->arrangeAuthCookie();

        ($this->controller)($provider, $request);
    }

    public function testInvokeDispatchesCommandWithActualClientIpAddress(): void
    {
        $provider = $this->faker->word();
        $code = $this->faker->sha256();
        $state = $this->faker->sha256();
        $flowBindingToken = $this->faker->sha256();
        $clientIp = $this->faker->ipv4();
        $request = Request::create(
            sprintf(
                'https://example.com/api/auth/social/%s/callback?code=%s&state=%s',
                $provider,
                $code,
                $state,
            ),
            'GET',
            [],
            [],
            [],
            ['REMOTE_ADDR' => $clientIp],
        );
        $request->cookies->set(
            OAuthFlowCookieFactory::COOKIE_NAME,
            $flowBindingToken,
        );

        $responseDto = new HandleOAuthCallbackResponse(
            false,
            $this->faker->sha256(),
            $this->faker->sha256(),
        );
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(
                static fn (HandleOAuthCallbackCommand $command): bool =>
                    $command->ipAddress === $clientIp
            ))
            ->willReturnCallback(
                static function (HandleOAuthCallbackCommand $command) use ($responseDto): void {
                    $command->setResponse($responseDto);
                }
            );
        $this->arrangeAuthCookie();

        ($this->controller)($provider, $request);
    }

    public function testInvokeThrowsWhenDirectSignInAccessTokenIsEmpty(): void
    {
        $this->arrangeCommandBus(
            new HandleOAuthCallbackResponse(
                false,
                '',
                $this->faker->sha256(),
            )
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'OAuth callback response missing access/refresh token when 2FA is disabled.'
        );

        $this->invokeController();
    }

    public function testInvokeThrowsWhenDirectSignInRefreshTokenIsNull(): void
    {
        $this->arrangeCommandBus(
            new HandleOAuthCallbackResponse(
                false,
                $this->faker->sha256(),
                null,
            )
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'OAuth callback response missing access/refresh token when 2FA is disabled.'
        );

        $this->invokeController();
    }

    public function testAttachAuthCookieSkipsEmptyAccessToken(): void
    {
        $method = new \ReflectionMethod(
            OAuthCallbackController::class,
            'attachAuthCookie',
        );
        $this->makeAccessible($method);
        $response = new Response();

        $this->authCookieFactory->expects($this->never())
            ->method('create');

        $method->invoke(
            $this->controller,
            new HandleOAuthCallbackResponse(
                false,
                '',
                $this->faker->sha256(),
            ),
            $response,
        );

        $this->assertCount(0, $response->headers->getCookies());
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

    private function invokeController(?string $provider = null): Response
    {
        $provider ??= $this->faker->word();

        $request = Request::create(
            sprintf(
                'https://example.com/api/auth/social/%s/callback?code=%s&state=%s',
                $provider,
                $this->faker->sha256(),
                $this->faker->sha256(),
            ),
            'GET',
        );
        $request->cookies->set(
            OAuthFlowCookieFactory::COOKIE_NAME,
            $this->faker->sha256(),
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
