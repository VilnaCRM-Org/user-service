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
        $email = $this->faker->safeEmail();
        $query = sprintf('mutation { createUser(input: { email: "%s" }) { user { id } } }', $email);
        $request = $this->createGraphQlRequest($query, $clientIp);

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
        $email = $this->faker->safeEmail();
        $query = sprintf(
            'mutation { passkeySignUpOptions(input: { email: "%s" }) { challengeId } }',
            $email
        );
        $request = $this->createGraphQlRequest($query, $clientIp);

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
        $challengeId = $this->faker->uuid();
        $request = $this->createGraphQlRequest(
            sprintf(
                'mutation { passkeySignUpComplete(input: { challengeId: "%s" }) { accessToken } }',
                $challengeId
            ),
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
        $query = sprintf(
            'mutation { passkeySignInOptions(input: { email: "%s" }) { challengeId } }',
            $email
        );
        $request = $this->createGraphQlRequest($query, $clientIp);

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
        $challengeId = $this->faker->uuid();
        $request = $this->createGraphQlRequest(
            sprintf(
                'mutation { passkeySignInComplete(input: { challengeId: "%s" }) { accessToken } }',
                $challengeId
            ),
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
        $email = $this->faker->safeEmail();
        $query = sprintf(
            'mutation { passkeySignInOptions(input: { email: "%s" }) { challengeId } }',
            $email
        );
        $request = $this->createGraphQlRequest(
            $query,
            $this->faker->ipv4(),
            method: 'GET'
        );

        self::assertSame([], $this->resolver->resolveEndpointLimiters($request));
    }

    public function testResolveEndpointLimitersSkipsGraphQlAuthLimitersForNonGraphQlPath(): void
    {
        $email = $this->faker->safeEmail();
        $query = sprintf(
            'mutation { passkeySignInOptions(input: { email: "%s" }) { challengeId } }',
            $email
        );
        $request = $this->createGraphQlRequest(
            $query,
            $this->faker->ipv4(),
            path: '/api/health'
        );

        self::assertSame([], $this->resolver->resolveEndpointLimiters($request));
    }

    public function testResolveEndpointLimitersSkipsUnrelatedGraphQlMutation(): void
    {
        $projectId = $this->faker->uuid();
        $query = sprintf(
            'mutation { updateProject(input: { id: "%s" }) { project { id } } }',
            $projectId
        );
        $request = $this->createGraphQlRequest($query, $this->faker->ipv4());

        self::assertSame([], $this->resolver->resolveEndpointLimiters($request));
    }

    /**
     * @param array<string, array<string, string>|string> $variables
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
