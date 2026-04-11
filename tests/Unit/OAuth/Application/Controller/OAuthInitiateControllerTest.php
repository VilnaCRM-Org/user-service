<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Application\Controller;

use App\OAuth\Application\Command\InitiateOAuthCommand;
use App\OAuth\Application\Controller\OAuthInitiateController;
use App\OAuth\Application\DTO\InitiateOAuthResponse;
use App\OAuth\Application\Factory\OAuthFlowCookieFactory;
use App\OAuth\Domain\Exception\UnsupportedProviderException;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class OAuthInitiateControllerTest extends UnitTestCase
{
    private CommandBusInterface&MockObject $commandBus;
    private OAuthFlowCookieFactory $flowCookieFactory;
    private OAuthInitiateController $controller;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->flowCookieFactory = new OAuthFlowCookieFactory();

        $this->controller = new OAuthInitiateController(
            $this->commandBus,
            $this->flowCookieFactory,
        );
    }

    public function testInvokeReturns302Redirect(): void
    {
        $authUrl = $this->faker->url();
        $this->arrangeCommandBus($authUrl);

        $response = $this->invokeController();

        $this->assertSame(
            Response::HTTP_FOUND,
            $response->getStatusCode()
        );
        $this->assertStringContainsString(
            'no-store',
            (string) $response->headers->get('Cache-Control')
        );
    }

    public function testInvokeRedirectsToAuthorizationUrl(): void
    {
        $authUrl = $this->faker->url();
        $this->arrangeCommandBus($authUrl);

        $response = $this->invokeController();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($authUrl, $response->getTargetUrl());
    }

    public function testInvokeSetsFlowBindingCookie(): void
    {
        $this->arrangeCommandBus($this->faker->url());

        $response = $this->invokeController();

        $cookies = $response->headers->getCookies();
        $flowCookie = $this->findCookieByName(
            $cookies,
            OAuthFlowCookieFactory::COOKIE_NAME
        );

        $this->assertNotNull($flowCookie);
    }

    public function testInvokeSetsFlowBindingCookieWithSecureAttributes(): void
    {
        $this->arrangeCommandBus($this->faker->url());

        $response = $this->invokeController();

        $cookies = $response->headers->getCookies();
        $flowCookie = $this->findCookieByName(
            $cookies,
            OAuthFlowCookieFactory::COOKIE_NAME
        );

        $this->assertNotNull($flowCookie);
        $this->assertTrue($flowCookie->isHttpOnly());
        $this->assertTrue($flowCookie->isSecure());
        $this->assertSame(Cookie::SAMESITE_LAX, $flowCookie->getSameSite());
    }

    public function testInvokeDispatchesCommandWithProvider(): void
    {
        $provider = $this->faker->word();

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(
                static fn (InitiateOAuthCommand $cmd): bool => $cmd->provider === $provider
            ));

        $this->arrangeCommandBus($this->faker->url());

        $this->invokeController($provider);
    }

    public function testInvokeDispatchesCommandWithRedirectUri(): void
    {
        $provider = $this->faker->word();

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(
                static fn (InitiateOAuthCommand $cmd): bool => str_contains(
                    $cmd->redirectUri,
                    '/api/auth/social/' . $provider . '/callback'
                )
            ));

        $this->arrangeCommandBus($this->faker->url());

        $this->invokeController($provider);
    }

    public function testInvokeDispatchesCommandWithAbsoluteRedirectUri(): void
    {
        $provider = $this->faker->word();
        $expectedRedirectUri = sprintf(
            'https://example.com/api/auth/social/%s/callback',
            $provider,
        );
        $authUrl = $this->faker->url();

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(
                static fn (InitiateOAuthCommand $command): bool =>
                    $command->redirectUri === $expectedRedirectUri
            ))
            ->willReturnCallback(
                static function (InitiateOAuthCommand $command) use ($authUrl): void {
                    $command->setResponse(
                        new InitiateOAuthResponse(
                            $authUrl,
                            'state',
                            'flow-binding-token',
                        )
                    );
                }
            );

        $this->invokeController($provider);
    }

    public function testInvokePropagatesUnsupportedProviderException(): void
    {
        $provider = $this->faker->word();

        $this->commandBus->method('dispatch')
            ->willThrowException(new UnsupportedProviderException($provider));

        $this->expectException(UnsupportedProviderException::class);

        $this->invokeController($provider);
    }

    private function arrangeCommandBus(string $authUrl): void
    {
        $this->commandBus->method('dispatch')
            ->willReturnCallback(function (InitiateOAuthCommand $command) use ($authUrl): void {
                $command->setResponse(new InitiateOAuthResponse(
                    $authUrl,
                    $this->faker->sha256(),
                    $this->faker->sha256(),
                ));
            });
    }

    private function invokeController(?string $provider = null): Response
    {
        $provider ??= $this->faker->word();

        $request = Request::create(
            sprintf('https://example.com/api/auth/social/%s', $provider),
            'GET',
        );

        return ($this->controller)($provider, $request);
    }

    /**
     * @param array<Cookie> $cookies
     */
    private function findCookieByName(
        array $cookies,
        string $name,
    ): ?Cookie {
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === $name) {
                return $cookie;
            }
        }
        return null;
    }
}
