<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\RateLimit;

use App\Shared\Application\Converter\JwtTokenConverterInterface;
use App\Shared\Application\Resolver\RateLimit\ApiRateLimitRequestResolver;
use Symfony\Component\HttpFoundation\Request;

final class ApiRateLimitRequestResolverLimitersTest extends RateLimitClientTestCase
{
    private ApiRateLimitRequestResolver $resolver;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = $this->createRequestResolver();
    }

    public function testResolveGlobalLimiterReturnsAnonymousWhenNoAuth(): void
    {
        $clientIp = $this->faker->ipv4();
        $request = Request::create('/api/users', 'GET', [], [], [], ['REMOTE_ADDR' => $clientIp]);

        $result = $this->resolver->resolveGlobalLimiter($request);

        self::assertSame('global_api_anonymous', $result['name']);
        self::assertSame('ip:' . $clientIp, $result['key']);
    }

    public function testResolveGlobalLimiterReturnsAuthenticatedForValidJwt(): void
    {
        $now = time();
        $jwtConverter = $this->createMock(JwtTokenConverterInterface::class);
        $jwtConverter->method('decode')->willReturn([
            'iss' => 'vilnacrm-user-service',
            'aud' => 'vilnacrm-api',
            'sub' => $this->faker->uuid(),
            'nbf' => $now - 10,
            'exp' => $now + 900,
        ]);

        $clientIp = $this->faker->ipv4();
        $resolver = $this->createRequestResolver($jwtConverter);
        $request = Request::create('/api/users', 'GET', [], [], [], ['REMOTE_ADDR' => $clientIp]);
        $request->headers->set('Authorization', 'Bearer ' . $this->faker->sha256());

        $result = $resolver->resolveGlobalLimiter($request);

        self::assertSame('global_api_authenticated', $result['name']);
        self::assertSame('ip:' . $clientIp, $result['key']);
    }

    public function testResolveEndpointLimitersForRegistrationPost(): void
    {
        $clientIp = $this->faker->ipv4();
        $request = Request::create('/api/users', 'POST', [], [], [], ['REMOTE_ADDR' => $clientIp]);

        $limiters = $this->resolver->resolveEndpointLimiters($request);
        $byName = array_column($limiters, 'key', 'name');

        self::assertArrayHasKey('registration', $byName);
        self::assertSame('ip:' . $clientIp, $byName['registration']);
    }

    public function testResolveEndpointLimitersForPasskeySignupOptions(): void
    {
        $clientIp = $this->faker->ipv4();
        $request = Request::create(
            '/api/passkeys/signup/options',
            'POST',
            [],
            [],
            [],
            ['REMOTE_ADDR' => $clientIp]
        );

        $limiters = $this->resolver->resolveEndpointLimiters($request);
        $byName = array_column($limiters, 'key', 'name');

        self::assertArrayHasKey('registration', $byName);
        self::assertSame('ip:' . $clientIp, $byName['registration']);
    }

    public function testResolveEndpointLimitersForPasskeySignupComplete(): void
    {
        $clientIp = $this->faker->ipv4();
        $request = Request::create(
            '/api/passkeys/signup/complete',
            'POST',
            [],
            [],
            [],
            ['REMOTE_ADDR' => $clientIp]
        );

        $limiters = $this->resolver->resolveEndpointLimiters($request);
        $byName = array_column($limiters, 'key', 'name');

        self::assertArrayHasKey('registration', $byName);
        self::assertSame('ip:' . $clientIp, $byName['registration']);
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

    public function testResolveEndpointLimitersForUserCollectionGet(): void
    {
        $clientIp = $this->faker->ipv4();
        $request = Request::create('/api/users', 'GET', [], [], [], ['REMOTE_ADDR' => $clientIp]);

        $limiters = $this->resolver->resolveEndpointLimiters($request);
        $byName = array_column($limiters, 'key', 'name');

        self::assertArrayHasKey('user_collection', $byName);
        self::assertSame('ip:' . $clientIp, $byName['user_collection']);
    }

    public function testResolveEndpointLimitersForOauthTokenPath(): void
    {
        $request = Request::create('/api/token', 'POST', [], [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
        ]);

        $limiters = $this->resolver->resolveEndpointLimiters($request);
        $byName = array_column($limiters, 'key', 'name');

        self::assertArrayHasKey('refresh_token', $byName);
        self::assertSame('ip:127.0.0.1', $byName['refresh_token']);
    }

    public function testResolveEndpointLimitersForOauthAlternatePath(): void
    {
        $request = Request::create(
            '/api/oauth/token',
            'POST',
            [],
            [],
            [],
            ['REMOTE_ADDR' => '127.0.0.1']
        );

        $limiters = $this->resolver->resolveEndpointLimiters($request);
        $names = array_column($limiters, 'name');

        self::assertContains('oauth_token', $names);
    }

    public function testResolveEndpointLimitersOauthTokenUsesClientIdFromBasicAuth(): void
    {
        $clientId = $this->faker->lexify('client???');
        $request = Request::create('/api/token', 'POST', [], [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
        ]);
        $request->headers->set('Authorization', 'Basic ' . base64_encode($clientId . ':secret'));

        $limiters = $this->resolver->resolveEndpointLimiters($request);
        $byName = array_column($limiters, 'key', 'name');

        self::assertSame('ip:127.0.0.1', $byName['refresh_token']);
    }

    public function testResolveEndpointLimitersOauthAlternatePathUsesClientIdFromBasicAuth(): void
    {
        $clientId = $this->faker->lexify('client???');
        $request = Request::create(
            '/api/oauth/token',
            'POST',
            [],
            [],
            [],
            ['REMOTE_ADDR' => '127.0.0.1']
        );
        $request->headers->set('Authorization', 'Basic ' . base64_encode($clientId . ':secret'));

        $limiters = $this->resolver->resolveEndpointLimiters($request);
        $byName = array_column($limiters, 'key', 'name');

        self::assertSame('client:' . $clientId, $byName['oauth_token']);
    }

    public function testResolveEndpointLimitersForEmailConfirmation(): void
    {
        $clientIp = $this->faker->ipv4();
        $request = Request::create(
            '/api/users/confirm',
            'PATCH',
            [],
            [],
            [],
            ['REMOTE_ADDR' => $clientIp]
        );

        $limiters = $this->resolver->resolveEndpointLimiters($request);
        $byName = array_column($limiters, 'key', 'name');

        self::assertArrayHasKey('email_confirmation', $byName);
        self::assertSame('ip:' . $clientIp, $byName['email_confirmation']);
    }

    public function testResolveEndpointLimitersForUserUpdatePatch(): void
    {
        $userId = $this->faker->uuid();
        $request = Request::create('/api/users/' . $userId, 'PATCH');

        $limiters = $this->resolver->resolveEndpointLimiters($request);
        $byName = array_column($limiters, 'key', 'name');

        self::assertArrayHasKey('user_update', $byName);
        self::assertSame('user:' . $userId, $byName['user_update']);
    }

    public function testResolveEndpointLimitersForUserUpdatePut(): void
    {
        $userId = $this->faker->uuid();
        $request = Request::create('/api/users/' . $userId, 'PUT');

        $limiters = $this->resolver->resolveEndpointLimiters($request);
        $byName = array_column($limiters, 'key', 'name');

        self::assertArrayHasKey('user_update', $byName);
        self::assertSame('user:' . $userId, $byName['user_update']);
    }

    public function testResolveEndpointLimitersForUserDelete(): void
    {
        $userId = $this->faker->uuid();
        $request = Request::create('/api/users/' . $userId, 'DELETE');

        $limiters = $this->resolver->resolveEndpointLimiters($request);
        $byName = array_column($limiters, 'key', 'name');

        self::assertArrayHasKey('user_delete', $byName);
        self::assertSame('user:' . $userId, $byName['user_delete']);
    }

    public function testResolveEndpointLimitersSkipsUserMutationForBatchPath(): void
    {
        $request = Request::create('/api/users/batch', 'PATCH');

        $limiters = $this->resolver->resolveEndpointLimiters($request);
        $names = array_column($limiters, 'name');

        self::assertNotContains('user_update', $names);
    }

    public function testResolveEndpointLimitersSkipsUserMutationForConfirmPath(): void
    {
        $request = Request::create('/api/users/confirm', 'PATCH');

        $limiters = $this->resolver->resolveEndpointLimiters($request);
        $names = array_column($limiters, 'name');

        self::assertNotContains('user_update', $names);
    }

    public function testResolveEndpointLimitersSkipsUserMutationForGetRequest(): void
    {
        $userId = $this->faker->uuid();
        $request = Request::create('/api/users/' . $userId, 'GET');

        $limiters = $this->resolver->resolveEndpointLimiters($request);
        $names = array_column($limiters, 'name');

        self::assertNotContains('user_update', $names);
        self::assertNotContains('user_delete', $names);
    }

    public function testResolveEndpointLimitersForResendConfirmationEmail(): void
    {
        $userId = $this->faker->uuid();
        $clientIp = $this->faker->ipv4();
        $request = Request::create(
            '/api/users/' . $userId . '/resend-confirmation-email',
            'POST',
            [],
            [],
            [],
            ['REMOTE_ADDR' => $clientIp]
        );

        $limiters = $this->resolver->resolveEndpointLimiters($request);
        $byName = array_column($limiters, 'key', 'name');

        self::assertArrayHasKey('resend_confirmation', $byName);
        self::assertArrayHasKey('resend_confirmation_target', $byName);
        self::assertSame('ip:' . $clientIp, $byName['resend_confirmation']);
        self::assertSame('user:' . $userId, $byName['resend_confirmation_target']);
    }

    public function testResolveEndpointLimitersSkipsResendConfirmationForGetMethod(): void
    {
        $userId = $this->faker->uuid();
        $request = Request::create('/api/users/' . $userId . '/resend-confirmation-email', 'GET');

        $limiters = $this->resolver->resolveEndpointLimiters($request);
        $names = array_column($limiters, 'name');

        self::assertNotContains('resend_confirmation', $names);
    }

    public function testResolveEndpointLimitersForSignIn(): void
    {
        $clientIp = $this->faker->ipv4();
        $request = Request::create('/api/signin', 'POST', [], [], [], ['REMOTE_ADDR' => $clientIp]);

        $limiters = $this->resolver->resolveEndpointLimiters($request);
        $byName = array_column($limiters, 'key', 'name');

        self::assertArrayHasKey('signin_ip', $byName);
        self::assertSame('ip:' . $clientIp, $byName['signin_ip']);
    }

    public function testResolveEndpointLimitersForSignInWithEmailInBody(): void
    {
        $email = $this->faker->email();
        $request = Request::create(
            '/api/signin',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email], JSON_THROW_ON_ERROR)
        );

        $limiters = $this->resolver->resolveEndpointLimiters($request);
        $byName = array_column($limiters, 'key', 'name');

        self::assertArrayHasKey('signin_email', $byName);
        self::assertSame('email:' . strtolower(trim($email)), $byName['signin_email']);
    }

    public function testResolveEndpointLimitersForSignInTwoFactor(): void
    {
        $request = Request::create('/api/signin/2fa', 'POST');

        $limiters = $this->resolver->resolveEndpointLimiters($request);
        $names = array_column($limiters, 'name');

        self::assertContains('twofa_verification_ip', $names);
    }

    public function testResolveEndpointLimitersReturnsEmptyForUnrecognizedApiPath(): void
    {
        $request = Request::create('/api/health', 'GET');

        $limiters = $this->resolver->resolveEndpointLimiters($request);

        self::assertSame([], $limiters);
    }

    public function testResolveEndpointLimitersForRegistrationWithFormatExtension(): void
    {
        $request = Request::create('/api/users.json', 'POST');

        $limiters = $this->resolver->resolveEndpointLimiters($request);
        $names = array_column($limiters, 'name');

        self::assertContains('registration', $names);
    }

    public function testResolveEndpointLimitersForCollectionWithFormatExtension(): void
    {
        $request = Request::create('/api/users.jsonld', 'GET');

        $limiters = $this->resolver->resolveEndpointLimiters($request);
        $names = array_column($limiters, 'name');

        self::assertContains('user_collection', $names);
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
