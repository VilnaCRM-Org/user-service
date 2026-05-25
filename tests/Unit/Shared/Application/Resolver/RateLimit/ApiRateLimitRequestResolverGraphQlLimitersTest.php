<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\RateLimit;

use App\Shared\Application\Resolver\RateLimit\ApiRateLimitRequestResolver;
use Symfony\Component\HttpFoundation\Request;

final class ApiRateLimitRequestResolverGraphQlLimitersTest extends RateLimitClientTestCase
{
    private ApiRateLimitRequestResolver $resolver;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = $this->createRequestResolver();
    }

    public function testResolveEndpointLimitersForGraphQlCreateUser(): void
    {
        $clientIp = $this->faker->ipv4();
        $request = $this->createGraphQlRequest(
            'mutation { createUser(input: { email: "user@example.com" }) { user { id } } }',
            $clientIp,
        );

        $limiters = $this->resolver->resolveEndpointLimiters($request);

        self::assertSame(
            [
                ['name' => 'registration', 'key' => 'ip:' . $clientIp],
            ],
            $limiters
        );
    }

    public function testResolveEndpointLimitersForGraphQlPasskeySignupOptions(): void
    {
        $clientIp = $this->faker->ipv4();
        $request = $this->createGraphQlRequest(
            'mutation { passkeySignUpOptions(input: { email: "user@example.com" }) { challengeId } }',
            $clientIp,
        );

        $limiters = $this->resolver->resolveEndpointLimiters($request);

        self::assertSame(
            [
                ['name' => 'registration', 'key' => 'ip:' . $clientIp],
            ],
            $limiters
        );
    }

    public function testResolveEndpointLimitersForGraphQlPasskeySignupComplete(): void
    {
        $clientIp = $this->faker->ipv4();
        $request = $this->createGraphQlRequest(
            'mutation { passkeySignUpComplete(input: { challengeId: "challenge-id" }) { accessToken } }',
            $clientIp,
        );

        $limiters = $this->resolver->resolveEndpointLimiters($request);

        self::assertSame(
            [
                ['name' => 'registration', 'key' => 'ip:' . $clientIp],
            ],
            $limiters
        );
    }

    public function testResolveEndpointLimitersForGraphQlSignIn(): void
    {
        $clientIp = $this->faker->ipv4();
        $email = $this->faker->email();
        $request = $this->createGraphQlRequest(
            'mutation SignIn($input: SignInInput!) { signIn(input: $input) { accessToken } }',
            $clientIp,
            ['input' => ['email' => $email]]
        );

        $limiters = $this->resolver->resolveEndpointLimiters($request);

        self::assertSame(
            [
                ['name' => 'signin_ip', 'key' => 'ip:' . $clientIp],
                ['name' => 'signin_email', 'key' => 'email:' . strtolower($email)],
            ],
            $limiters
        );
    }

    public function testResolveEndpointLimitersForGraphQlPasskeySigninOptions(): void
    {
        $clientIp = $this->faker->ipv4();
        $email = $this->faker->email();
        $request = $this->createGraphQlRequest(
            'mutation { passkeySignInOptions(input: { email: "' . $email . '" }) { challengeId } }',
            $clientIp,
        );

        $limiters = $this->resolver->resolveEndpointLimiters($request);

        self::assertSame(
            [
                ['name' => 'signin_ip', 'key' => 'ip:' . $clientIp],
                ['name' => 'signin_email', 'key' => 'email:' . strtolower($email)],
            ],
            $limiters
        );
    }

    public function testResolveEndpointLimitersForGraphQlPasskeySigninComplete(): void
    {
        $clientIp = $this->faker->ipv4();
        $request = $this->createGraphQlRequest(
            'mutation { passkeySignInComplete(input: { challengeId: "challenge-id" }) { accessToken } }',
            $clientIp,
        );

        $limiters = $this->resolver->resolveEndpointLimiters($request);

        self::assertSame(
            [
                ['name' => 'signin_ip', 'key' => 'ip:' . $clientIp],
            ],
            $limiters
        );
    }

    public function testResolveEndpointLimitersSkipsGraphQlAuthLimitersForGetRequest(): void
    {
        $request = $this->createGraphQlRequest(
            'mutation { passkeySignInOptions(input: { email: "user@example.com" }) { challengeId } }',
            $this->faker->ipv4(),
            method: 'GET'
        );

        self::assertSame([], $this->resolver->resolveEndpointLimiters($request));
    }

    public function testResolveEndpointLimitersSkipsGraphQlAuthLimitersForNonGraphQlPath(): void
    {
        $request = $this->createGraphQlRequest(
            'mutation { passkeySignInOptions(input: { email: "user@example.com" }) { challengeId } }',
            $this->faker->ipv4(),
            path: '/api/health'
        );

        self::assertSame([], $this->resolver->resolveEndpointLimiters($request));
    }

    public function testResolveEndpointLimitersSkipsUnrelatedGraphQlMutation(): void
    {
        $request = $this->createGraphQlRequest(
            'mutation { updateProject(input: { id: "project-id" }) { project { id } } }',
            $this->faker->ipv4(),
        );

        self::assertSame([], $this->resolver->resolveEndpointLimiters($request));
    }

    /**
     * @param array<string, mixed> $variables
     */
    private function createGraphQlRequest(
        string $query,
        string $clientIp,
        array $variables = [],
        string $method = 'POST',
        string $path = '/api/graphql'
    ): Request {
        return Request::create(
            $path,
            $method,
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'REMOTE_ADDR' => $clientIp],
            json_encode(
                [
                    'query' => $query,
                    'variables' => $variables,
                ],
                JSON_THROW_ON_ERROR
            )
        );
    }
}
