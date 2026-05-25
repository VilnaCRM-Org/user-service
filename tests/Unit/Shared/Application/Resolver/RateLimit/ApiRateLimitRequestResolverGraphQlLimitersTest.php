<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\RateLimit;

use App\Shared\Application\Resolver\RateLimit\ApiRateLimitRequestResolver;
use Symfony\Component\HttpFoundation\Request;

final class ApiRateLimitRequestResolverGraphQlLimitersTest extends RateLimitClientTestCase
{
    private const GRAPHQL_PATH = '/api/graphql';
    private const PASSKEY_SIGNIN_OPTIONS_MUTATION =
        'mutation { passkeySignInOptions(input: { email: "%s" }) { challengeId } }';

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

        $this->assertGraphQlLimiters($query, $clientIp, $this->registrationLimiters($clientIp));
    }

    public function testResolveEndpointLimitersForGraphQlPasskeySignupOptions(): void
    {
        $clientIp = $this->faker->ipv4();
        $email = $this->faker->safeEmail();
        $query = sprintf(
            'mutation { passkeySignUpOptions(input: { email: "%s" }) { challengeId } }',
            $email
        );

        $this->assertGraphQlLimiters($query, $clientIp, $this->registrationLimiters($clientIp));
    }

    public function testResolveEndpointLimitersForGraphQlPasskeySignupComplete(): void
    {
        $clientIp = $this->faker->ipv4();
        $challengeId = $this->faker->uuid();
        $query = sprintf(
            'mutation { passkeySignUpComplete(input: { challengeId: "%s" }) { accessToken } }',
            $challengeId
        );

        $this->assertGraphQlLimiters($query, $clientIp, $this->registrationLimiters($clientIp));
    }

    public function testResolveEndpointLimitersForGraphQlSignIn(): void
    {
        $clientIp = $this->faker->ipv4();
        $email = $this->faker->email();
        $query = 'mutation SignIn($input: SignInInput!) { signIn(input: $input) { accessToken } }';

        $this->assertGraphQlLimiters(
            $query,
            $clientIp,
            $this->signInLimiters($clientIp, $email),
            ['variables' => ['input' => ['email' => $email]]]
        );
    }

    public function testResolveEndpointLimitersForGraphQlPasskeySigninOptions(): void
    {
        $clientIp = $this->faker->ipv4();
        $email = $this->faker->email();

        $this->assertGraphQlLimiters(
            sprintf(
                self::PASSKEY_SIGNIN_OPTIONS_MUTATION,
                $email
            ),
            $clientIp,
            $this->signInLimiters($clientIp, $email)
        );
    }

    public function testResolveEndpointLimitersForGraphQlPasskeySigninComplete(): void
    {
        $clientIp = $this->faker->ipv4();
        $challengeId = $this->faker->uuid();
        $query = sprintf(
            'mutation { passkeySignInComplete(input: { challengeId: "%s" }) { accessToken } }',
            $challengeId
        );

        $this->assertGraphQlLimiters($query, $clientIp, $this->signInIpLimiters($clientIp));
    }

    public function testResolveEndpointLimitersSkipsGraphQlAuthLimitersForGetRequest(): void
    {
        $email = $this->faker->safeEmail();
        $query = sprintf(
            self::PASSKEY_SIGNIN_OPTIONS_MUTATION,
            $email
        );

        $this->assertGraphQlLimiters($query, $this->faker->ipv4(), [], ['method' => 'GET']);
    }

    public function testResolveEndpointLimitersSkipsGraphQlAuthLimitersForNonGraphQlPath(): void
    {
        $email = $this->faker->safeEmail();
        $query = sprintf(
            self::PASSKEY_SIGNIN_OPTIONS_MUTATION,
            $email
        );

        $this->assertGraphQlLimiters($query, $this->faker->ipv4(), [], ['path' => '/api/health']);
    }

    public function testResolveEndpointLimitersSkipsUnrelatedGraphQlMutation(): void
    {
        $projectId = $this->faker->uuid();
        $query = sprintf(
            'mutation { updateProject(input: { id: "%s" }) { project { id } } }',
            $projectId
        );

        $this->assertGraphQlLimiters($query, $this->faker->ipv4(), []);
    }

    /**
     * @param array<string, array<string, string>|string> $variables
     */
    private function createGraphQlRequest(
        string $query,
        string $clientIp,
        array $variables = [],
        string $method = 'POST',
        string $path = self::GRAPHQL_PATH
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

    /**
     * @param list<array{name: string, key: string}> $expectedLimiters
     * @param array{
     *     method?: string,
     *     path?: string,
     *     variables?: array<string, array<string, string>|string>
     * } $options
     */
    private function assertGraphQlLimiters(
        string $query,
        string $clientIp,
        array $expectedLimiters,
        array $options = []
    ): void {
        $variables = $options['variables'] ?? [];
        $method = $options['method'] ?? 'POST';
        $path = $options['path'] ?? self::GRAPHQL_PATH;
        $request = $this->createGraphQlRequest($query, $clientIp, $variables, $method, $path);

        self::assertSame($expectedLimiters, $this->resolver->resolveEndpointLimiters($request));
    }

    /**
     * @return list<array{name: string, key: string}>
     */
    private function registrationLimiters(string $clientIp): array
    {
        return [
            ['name' => 'registration', 'key' => 'ip:' . $clientIp],
        ];
    }

    /**
     * @return list<array{name: string, key: string}>
     */
    private function signInLimiters(string $clientIp, string $email): array
    {
        return [
            ...$this->signInIpLimiters($clientIp),
            ['name' => 'signin_email', 'key' => 'email:' . strtolower($email)],
        ];
    }

    /**
     * @return list<array{name: string, key: string}>
     */
    private function signInIpLimiters(string $clientIp): array
    {
        return [
            ['name' => 'signin_ip', 'key' => 'ip:' . $clientIp],
        ];
    }
}
