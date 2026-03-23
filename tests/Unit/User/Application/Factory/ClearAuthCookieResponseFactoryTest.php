<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Factory;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Factory\ClearAuthCookieResponseFactory;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

final class ClearAuthCookieResponseFactoryTest extends UnitTestCase
{
    private ClearAuthCookieResponseFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new ClearAuthCookieResponseFactory();
    }

    public function testCreateReturnsNoContentResponse(): void
    {
        $response = $this->factory->create();

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertSame('', $response->getContent());
    }

    public function testCreateSetsClearCookieWithCorrectAttributes(): void
    {
        $response = $this->factory->create();
        $cookies = $response->headers->getCookies();

        $this->assertCount(1, $cookies);
        $cookie = $cookies[0];
        $this->assertSame('__Host-auth_token', $cookie->getName());
        $this->assertSame('', $cookie->getValue());
        $this->assertSame(1, $cookie->getExpiresTime());
        $this->assertSame('/', $cookie->getPath());
        $this->assertNull($cookie->getDomain());
        $this->assertTrue($cookie->isSecure());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertFalse($cookie->isRaw());
        $this->assertSame(Cookie::SAMESITE_LAX, $cookie->getSameSite());
    }
}
