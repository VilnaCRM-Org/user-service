<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Application\Factory;

use App\OAuth\Application\Factory\OAuthFlowCookieFactory;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Cookie;

final class OAuthFlowCookieFactoryTest extends UnitTestCase
{
    public function testCreateReturnsCookieWithCorrectName(): void
    {
        $factory = new OAuthFlowCookieFactory();
        $cookie = $factory->create($this->faker->sha256());

        $this->assertSame(
            OAuthFlowCookieFactory::COOKIE_NAME,
            $cookie->getName()
        );
    }

    public function testCreateReturnsCookieWithFlowBindingToken(): void
    {
        $factory = new OAuthFlowCookieFactory();
        $token = $this->faker->sha256();

        $cookie = $factory->create($token);

        $this->assertSame($token, $cookie->getValue());
    }

    public function testCreateReturnsCookieWithHttpOnly(): void
    {
        $factory = new OAuthFlowCookieFactory();
        $cookie = $factory->create($this->faker->sha256());

        $this->assertTrue($cookie->isHttpOnly());
    }

    public function testCreateReturnsCookieWithSecure(): void
    {
        $factory = new OAuthFlowCookieFactory();
        $cookie = $factory->create($this->faker->sha256());

        $this->assertTrue($cookie->isSecure());
    }

    public function testCreateReturnsCookieWithSameSiteLax(): void
    {
        $factory = new OAuthFlowCookieFactory();
        $cookie = $factory->create($this->faker->sha256());

        $this->assertSame(
            Cookie::SAMESITE_LAX,
            $cookie->getSameSite()
        );
    }

    public function testCreateReturnsCookieWithCorrectPath(): void
    {
        $factory = new OAuthFlowCookieFactory();
        $cookie = $factory->create($this->faker->sha256());

        $this->assertSame('/api/auth/social', $cookie->getPath());
    }

    public function testCreateReturnsCookieWithTtlBasedExpiry(): void
    {
        $ttl = 300;
        $factory = new OAuthFlowCookieFactory($ttl);

        $before = time() + $ttl;
        $cookie = $factory->create($this->faker->sha256());
        $after = time() + $ttl;

        $this->assertGreaterThanOrEqual($before, $cookie->getExpiresTime());
        $this->assertLessThanOrEqual($after, $cookie->getExpiresTime());
    }

    public function testCreateUsesDefaultTtl(): void
    {
        $factory = new OAuthFlowCookieFactory();

        $before = time() + 600;
        $cookie = $factory->create($this->faker->sha256());
        $after = time() + 600;

        $this->assertGreaterThanOrEqual($before, $cookie->getExpiresTime());
        $this->assertLessThanOrEqual($after, $cookie->getExpiresTime());
    }
}
