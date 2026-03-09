<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Service;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Factory\AuthCookieFactory;
use App\User\Application\Factory\AuthCookieFactoryInterface;
use App\User\Application\Service\AuthCookieService;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

final class AuthCookieServiceTest extends UnitTestCase
{
    private AuthCookieFactoryInterface&MockObject $cookieFactory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->cookieFactory = $this->createMock(AuthCookieFactoryInterface::class);
    }

    public function testAttachDoesNothingWhenAccessTokenIsEmpty(): void
    {
        $this->cookieFactory->expects($this->never())->method('create');

        $response = new Response();
        $this->createService()->attach($response, '', false);

        $this->assertCount(0, $response->headers->getCookies());
    }

    public function testAttachSetsCookieOnResponseForNonEmptyToken(): void
    {
        $token = $this->faker->sha256();
        $this->cookieFactory->expects($this->once())
            ->method('create')
            ->with($token, false, 900, 2592000, $this->isInstanceOf(DateTimeImmutable::class))
            ->willReturn(Cookie::create(AuthCookieFactory::COOKIE_NAME, $token));

        $response = new Response();
        $this->createService()->attach($response, $token, false);

        $this->assertCount(1, $response->headers->getCookies());
    }

    public function testAttachPassesRememberMeTrueToFactory(): void
    {
        $token = $this->faker->sha256();
        $this->cookieFactory->expects($this->once())
            ->method('create')
            ->with($token, true, $this->anything(), $this->anything(), $this->anything())
            ->willReturn(Cookie::create(AuthCookieFactory::COOKIE_NAME, $token));

        $this->createService()->attach(new Response(), $token, true);
    }

    public function testAttachUsesDefaultCookieMaxAges(): void
    {
        $token = $this->faker->sha256();
        $this->cookieFactory->expects($this->once())
            ->method('create')
            ->with($token, false, 900, 2592000, $this->isInstanceOf(DateTimeImmutable::class))
            ->willReturn(Cookie::create(AuthCookieFactory::COOKIE_NAME, $token));

        (new AuthCookieService($this->cookieFactory))->attach(new Response(), $token, false);
    }

    private function createService(): AuthCookieService
    {
        return new AuthCookieService($this->cookieFactory, 900, 2592000);
    }
}
