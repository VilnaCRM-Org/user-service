<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\RateLimit;

use Symfony\Component\HttpFoundation\Request;

final class ApiRateLimitRequestResolverGraphQlLimitersTest extends GraphQlRateLimitTestCase
{
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

    public function testResolveEndpointLimitersForGraphQlPasskeyRegistrationOptions(): void
    {
        $clientIp = $this->faker->ipv4();
        $query = <<<'GRAPHQL'
mutation {
  passkeyRegistrationOptionsUser(input: {}) {
    user { challengeId }
  }
}
GRAPHQL;

        $this->assertGraphQlLimiters($query, $clientIp, $this->registrationLimiters($clientIp));
    }

    public function testResolveEndpointLimitersForGraphQlPasskeyRegistrationComplete(): void
    {
        $clientIp = $this->faker->ipv4();
        $challengeId = $this->faker->uuid();
        $query = sprintf(
            <<<'GRAPHQL'
mutation {
  passkeyRegistrationCompleteUser(input: { challengeId: "%s" }) {
    user { passkeyId }
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

    public function testGraphQlOperationsPayloadWithInvalidJsonUsesNoAuthLimiters(): void
    {
        $clientIp = $this->faker->ipv4();
        $request = Request::create(
            self::GRAPHQL_PATH,
            'POST',
            ['operations' => '{'],
            [],
            [],
            [
                'CONTENT_TYPE' => 'multipart/form-data; boundary=----rate-limit',
                'REMOTE_ADDR' => $clientIp,
            ]
        );

        self::assertSame([], $this->resolver->resolveEndpointLimiters($request));
    }

    public function testGraphQlOperationsPayloadWithScalarJsonUsesNoAuthLimiters(): void
    {
        $clientIp = $this->faker->ipv4();
        $request = $this->createGraphQlOperationsRequest(
            ['operationName' => 'Ignored'],
            $clientIp,
            'multipart/form-data; boundary=----rate-limit'
        );
        $request->request->set('operations', 'true');

        self::assertSame([], $this->resolver->resolveEndpointLimiters($request));
    }

    public function testGraphQlOperationsPayloadWithoutQueryUsesNoAuthLimiters(): void
    {
        $clientIp = $this->faker->ipv4();
        $request = $this->createGraphQlOperationsRequest(
            ['operationName' => 'Ignored'],
            $clientIp,
            'multipart/form-data; boundary=----rate-limit'
        );

        self::assertSame([], $this->resolver->resolveEndpointLimiters($request));
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

    public function testGraphQlLimitersIgnoreOperationTokensInsideSelectedOperationStrings(): void
    {
        $clientIp = $this->faker->ipv4();
        $decoyEmail = $this->faker->email();
        $email = $this->faker->email();
        $query = $this->createSelectedPasskeySigninQueryWithStringTokens($decoyEmail);

        $request = $this->createGraphQlRequest(
            $query,
            $clientIp,
            ['e' => $email],
            extraPayload: ['operationName' => 'Real']
        );

        self::assertSame(
            $this->signInLimiters($clientIp, $email),
            $this->resolver->resolveEndpointLimiters($request)
        );
    }

    public function testResolveEndpointLimitersSupportsInvalidRawGraphQlRequestFallback(): void
    {
        $clientIp = $this->faker->ipv4();
        $request = Request::create(
            self::GRAPHQL_PATH,
            'POST',
            [],
            [],
            [],
            ['REMOTE_ADDR' => $clientIp],
            'not valid GraphQL passkeySignInOptionsUser'
        );

        self::assertSame(
            $this->signInIpLimiters($clientIp),
            $this->resolver->resolveEndpointLimiters($request)
        );
    }

    public function testInvalidRawGraphQlPasskeySigninCompleteFallbackDoesNotUseEmailLimiter(): void
    {
        $clientIp = $this->faker->ipv4();
        $request = Request::create(
            self::GRAPHQL_PATH,
            'POST',
            [],
            [],
            [],
            ['REMOTE_ADDR' => $clientIp],
            sprintf(
                'not valid GraphQL passkeySignInCompleteUser credential email %s',
                $this->faker->email()
            )
        );

        self::assertSame(
            $this->signInIpLimiters($clientIp),
            $this->resolver->resolveEndpointLimiters($request)
        );
    }

    public function testResolveEndpointLimitersSkipsGraphQlAuthWhenQueryPayloadIsNotString(): void
    {
        $request = Request::create(
            self::GRAPHQL_PATH,
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'REMOTE_ADDR' => $this->faker->ipv4()],
            json_encode(['query' => 42], JSON_THROW_ON_ERROR)
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

    public function testGraphQlPasskeySigninCompleteIgnoresCredentialEmail(): void
    {
        $clientIp = $this->faker->ipv4();
        $challengeId = $this->faker->uuid();
        $email = $this->faker->email();
        $query = sprintf(
            <<<'GRAPHQL'
mutation {
  passkeySignInCompleteUser(input: {
    challengeId: "%s"
    credential: { email: "%s" }
  }) {
    user { accessToken }
  }
}
GRAPHQL,
            $challengeId,
            $email
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
            'passkeyRegistrationOptionsUser',
            'passkeyRegistrationCompleteUser',
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
}
