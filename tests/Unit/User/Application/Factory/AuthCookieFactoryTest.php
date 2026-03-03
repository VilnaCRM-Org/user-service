<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Factory;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Factory\AuthCookieFactory;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Cookie;

final class AuthCookieFactoryTest extends UnitTestCase
{
    private AuthCookieFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new AuthCookieFactory();
    }

    public function testCreateReturnsCookieWithCorrectName(): void
    {
        $now = new DateTimeImmutable();
        $cookie = $this->factory->create($this->faker->sha256(), false, 900, 2592000, $now);

        $this->assertSame(AuthCookieFactory::COOKIE_NAME, $cookie->getName());
    }

    public function testCreateReturnsCookieWithCorrectToken(): void
    {
        $token = $this->faker->sha256();
        $now = new DateTimeImmutable();
        $cookie = $this->factory->create($token, false, 900, 2592000, $now);

        $this->assertSame($token, $cookie->getValue());
    }

    public function testCreateReturnsCookieWithSecureAttributes(): void
    {
        $now = new DateTimeImmutable();
        $cookie = $this->factory->create($this->faker->sha256(), false, 900, 2592000, $now);

        $this->assertSame('/', $cookie->getPath());
        $this->assertTrue($cookie->isSecure());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame(Cookie::SAMESITE_LAX, $cookie->getSameSite());
    }

    public function testCreateUsesStandardMaxAgeWhenRememberMeIsFalse(): void
    {
        $now = new DateTimeImmutable();
        $cookie = $this->factory->create($this->faker->sha256(), false, 900, 2592000, $now);

        $this->assertGreaterThanOrEqual(899, $cookie->getMaxAge());
        $this->assertLessThanOrEqual(900, $cookie->getMaxAge());
    }

    public function testCreateUsesRememberMeMaxAgeWhenRememberMeIsTrue(): void
    {
        $now = new DateTimeImmutable();
        $cookie = $this->factory->create($this->faker->sha256(), true, 900, 2592000, $now);

        $this->assertGreaterThanOrEqual(2591999, $cookie->getMaxAge());
        $this->assertLessThanOrEqual(2592000, $cookie->getMaxAge());
    }
}
