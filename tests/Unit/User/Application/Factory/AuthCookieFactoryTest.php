<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Factory;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Factory\AuthCookieFactory;
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
        $cookie = $this->factory->create($this->faker->sha256(), false);

        $this->assertSame(AuthCookieFactory::COOKIE_NAME, $cookie->getName());
    }

    public function testCreateReturnsCookieWithCorrectToken(): void
    {
        $token = $this->faker->sha256();
        $cookie = $this->factory->create($token, false);

        $this->assertSame($token, $cookie->getValue());
    }

    public function testCreateReturnsCookieWithSecureAttributes(): void
    {
        $cookie = $this->factory->create($this->faker->sha256(), false);

        $this->assertSame('/', $cookie->getPath());
        $this->assertTrue($cookie->isSecure());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame(Cookie::SAMESITE_LAX, $cookie->getSameSite());
    }

    public function testCreateUsesStandardMaxAgeWhenRememberMeIsFalse(): void
    {
        $cookie = $this->factory->create($this->faker->sha256(), false);

        $this->assertGreaterThanOrEqual(899, $cookie->getMaxAge());
        $this->assertLessThanOrEqual(900, $cookie->getMaxAge());
    }

    public function testCreateUsesRememberMeMaxAgeWhenRememberMeIsTrue(): void
    {
        $cookie = $this->factory->create($this->faker->sha256(), true);

        $this->assertGreaterThanOrEqual(2591999, $cookie->getMaxAge());
        $this->assertLessThanOrEqual(2592000, $cookie->getMaxAge());
    }

    public function testDefaultStandardCookieMaxAgeIsExpectedValue(): void
    {
        $this->assertSame(900, $this->readPrivateInt('standardCookieMaxAge'));
    }

    public function testDefaultRememberMeCookieMaxAgeIsExpectedValue(): void
    {
        $this->assertSame(2592000, $this->readPrivateInt('rememberMeCookieMaxAge'));
    }

    private function readPrivateInt(string $property): int
    {
        $reflectionProperty = new \ReflectionProperty(AuthCookieFactory::class, $property);
        $value = $reflectionProperty->getValue($this->factory);

        $this->assertIsInt($value);

        return $value;
    }
}
