<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\RateLimit;

use App\Shared\Application\Resolver\RateLimit\ApiRateLimitRequestResolver;
use Symfony\Component\HttpFoundation\Request;

final class ApiRateLimitRequestResolverGraphQlLimitersTest extends RateLimitClientTestCase
{
    private const GRAPHQL_PATH = '/api/graphql';
    private const PASSKEY_SIGNIN_OPTIONS_MUTATION = <<<'GRAPHQL'
mutation {
  passkeySignInOptionsUser(input: { email: "%s" }) {
    user { challengeId }
  }
}
GRAPHQL;

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
            <<<'GRAPHQL'
mutation {
  passkeySignUpOptionsUser(input: { email: "%s" }) {
    user { challengeId }
  }
}
GRAPHQL,
            $email
        );

        $this->assertGraphQlLimiters($query, $clientIp, $this->registrationLimiters($clientIp));
    }

    public function testResolveEndpointLimitersForGraphQlPasskeySignupComplete(): void
    {
        $clientIp = $this->faker->ipv4();
        $challengeId = $this->faker->uuid();
        $query = sprintf(
            <<<'GRAPHQL'
mutation {
  passkeySignUpCompleteUser(input: { challengeId: "%s" }) {
    user { accessToken }
  }
}
GRAPHQL,
            $challengeId
        );

        $this->assertGraphQlLimiters($query, $clientIp, $this->registrationLimiters($clientIp));
    }

    public function testResolveEndpointLimitersForGraphQlSignIn(): void
    {
        $clientIp = $this->faker->ipv4();
        $email = $this->faker->email();
        $query = <<<'GRAPHQL'
mutation SignIn($input: signInUserInput!) {
  signInUser(input: $input) {
    user { accessToken }
  }
}
GRAPHQL;

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

    public function testPasskeySigninOptionsUsesAliasedEmailVariableLimiter(): void
    {
        $clientIp = $this->faker->ipv4();
        $email = $this->faker->email();
        $query = <<<'GRAPHQL'
mutation($e: String!) {
  passkeySignInOptionsUser(input: { email: $e }) {
    user { challengeId }
  }
}
GRAPHQL;

        $this->assertGraphQlLimiters(
            $query,
            $clientIp,
            $this->signInLimiters($clientIp, $email),
            ['variables' => ['e' => $email]]
        );
    }

    public function testPasskeySigninOptionsIgnoresUnrelatedJsonEmail(): void
    {
        $clientIp = $this->faker->ipv4();
        $decoyEmail = $this->faker->email();
        $email = $this->faker->email();
        $query = <<<'GRAPHQL'
mutation($e: String!) {
  passkeySignInOptionsUser(input: { email: $e }) {
    user { challengeId }
  }
}
GRAPHQL;
        $request = $this->createGraphQlRequest(
            $query,
            $clientIp,
            ['e' => $email],
            extraPayload: ['email' => $decoyEmail]
        );

        self::assertSame(
            $this->signInLimiters($clientIp, $email),
            $this->resolver->resolveEndpointLimiters($request)
        );
    }

    public function testGraphQlLimitersUseSelectedOperationOnly(): void
    {
        $clientIp = $this->faker->ipv4();
        $decoyEmail = $this->faker->email();
        $query = sprintf(
            <<<'GRAPHQL'
mutation Decoy {
  passkeySignInOptionsUser(input: { email: "%s" }) { user { challengeId } }
}
mutation Real {
  updateProject(input: { id: "project-id" }) { project { id } }
}
GRAPHQL,
            $decoyEmail
        );

        $request = $this->createGraphQlRequest(
            $query,
            $clientIp,
            extraPayload: ['operationName' => 'Real']
        );

        self::assertSame([], $this->resolver->resolveEndpointLimiters($request));
    }

    public function testResolveEndpointLimitersForGraphQlPasskeySigninComplete(): void
    {
        $clientIp = $this->faker->ipv4();
        $challengeId = $this->faker->uuid();
        $query = sprintf(
            <<<'GRAPHQL'
mutation {
  passkeySignInCompleteUser(input: { challengeId: "%s" }) {
    user { accessToken }
  }
}
GRAPHQL,
            $challengeId
        );

        $this->assertGraphQlLimiters($query, $clientIp, $this->signInIpLimiters($clientIp));
    }

    public function testGeneratedGraphQlSpecExposesAuthFieldNamesCoveredByRateLimiter(): void
    {
        $spec = file_get_contents(dirname(__DIR__, 6) . '/.github/graphql-spec/spec');
        if ($spec === false) {
            self::fail('Generated GraphQL specification is not readable.');
        }

        foreach ([
            'signInUser',
            'passkeySignUpOptionsUser',
            'passkeySignUpCompleteUser',
            'passkeySignInOptionsUser',
            'passkeySignInCompleteUser',
        ] as $fieldName) {
            self::assertStringContainsString(sprintf('%s(input:', $fieldName), $spec);
        }
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
     * @param array<string, array<string, string>|string> $extraPayload
     */
    private function createGraphQlRequest(
        string $query,
        string $clientIp,
        array $variables = [],
        string $method = 'POST',
        string $path = self::GRAPHQL_PATH,
        array $extraPayload = []
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
                ] + $extraPayload,
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
