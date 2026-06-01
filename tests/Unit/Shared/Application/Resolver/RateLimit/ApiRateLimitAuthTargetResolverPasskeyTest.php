<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\RateLimit;

use Symfony\Component\HttpFoundation\Request;

final class ApiRateLimitAuthTargetResolverPasskeyTest extends ApiRateLimitAuthTargetResolverTestCase
{
    public function testResolvePasskeySignInOptionsUsesSignInLimiters(): void
    {
        $clientIp = $this->faker->ipv4();
        $email = $this->faker->email();
        $resolver = $this->createAuthTargetResolver();
        $request = $this->createPasskeySignInOptionsRequest($clientIp, ['email' => $email]);

        $result = $resolver->resolve($request);

        self::assertCount(2, $result);
        self::assertSame('signin_ip', $result[0]['name']);
        self::assertSame('ip:' . $clientIp, $result[0]['key']);
        self::assertSame('signin_email', $result[1]['name']);
        self::assertSame('email:' . strtolower(trim($email)), $result[1]['key']);
    }

    public function testResolvePasskeySignInOptionsIgnoresNestedCredentialEmail(): void
    {
        $clientIp = $this->faker->ipv4();
        $resolver = $this->createAuthTargetResolver();
        $request = $this->createPasskeySignInOptionsRequest(
            $clientIp,
            ['credential' => ['email' => $this->faker->email()]]
        );

        self::assertSame([
            ['name' => 'signin_ip', 'key' => 'ip:' . $clientIp],
        ], $resolver->resolve($request));
    }

    public function testResolvePasskeySignInOptionsUsesTopLevelEmailBeforeNestedDecoy(): void
    {
        $clientIp = $this->faker->ipv4();
        $email = $this->faker->email();
        $resolver = $this->createAuthTargetResolver();
        $request = $this->createPasskeySignInOptionsRequest(
            $clientIp,
            [
                'email' => $email,
                'credential' => ['email' => $this->faker->email()],
            ]
        );

        self::assertSame(
            [
                ['name' => 'signin_ip', 'key' => 'ip:' . $clientIp],
                ['name' => 'signin_email', 'key' => 'email:' . strtolower(trim($email))],
            ],
            $resolver->resolve($request)
        );
    }

    public function testResolvePasskeySignInOptionsTrimsAndLowercasesTopLevelEmail(): void
    {
        $clientIp = $this->faker->ipv4();
        $resolver = $this->createAuthTargetResolver();
        $request = $this->createPasskeySignInOptionsRequest(
            $clientIp,
            ['email' => '  User@Example.TEST  ']
        );

        self::assertSame(
            [
                ['name' => 'signin_ip', 'key' => 'ip:' . $clientIp],
                ['name' => 'signin_email', 'key' => 'email:user@example.test'],
            ],
            $resolver->resolve($request)
        );
    }

    public function testResolvePasskeySignInCompleteUsesIpSignInLimiter(): void
    {
        $clientIp = $this->faker->ipv4();
        $resolver = $this->createAuthTargetResolver();
        $request = Request::create(
            '/api/passkeys/signin/complete',
            'POST',
            [],
            [],
            [],
            ['REMOTE_ADDR' => $clientIp]
        );

        $result = $resolver->resolve($request);

        self::assertCount(1, $result);
        self::assertSame('signin_ip', $result[0]['name']);
        self::assertSame('ip:' . $clientIp, $result[0]['key']);
    }

    public function testResolvePasskeySignInCompleteIgnoresCredentialEmail(): void
    {
        $clientIp = $this->faker->ipv4();
        $resolver = $this->createAuthTargetResolver();
        $request = Request::create(
            '/api/passkeys/signin/complete',
            'POST',
            [],
            [],
            [],
            ['REMOTE_ADDR' => $clientIp, 'CONTENT_TYPE' => 'application/json'],
            json_encode([
                'challengeId' => $this->faker->uuid(),
                'credential' => ['email' => $this->faker->email()],
            ], JSON_THROW_ON_ERROR)
        );

        $result = $resolver->resolve($request);

        self::assertSame([
            ['name' => 'signin_ip', 'key' => 'ip:' . $clientIp],
        ], $result);
    }

    /**
     * @param array<string, array<string, string>|string> $payload
     */
    private function createPasskeySignInOptionsRequest(string $clientIp, array $payload): Request
    {
        return Request::create(
            '/api/passkeys/signin/options',
            'POST',
            [],
            [],
            [],
            ['REMOTE_ADDR' => $clientIp, 'CONTENT_TYPE' => 'application/json'],
            json_encode($payload, JSON_THROW_ON_ERROR)
        );
    }
}
