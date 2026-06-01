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

    public function testPasskeySigninOptionsIgnoresNestedCredentialEmail(): void
    {
        $clientIp = $this->faker->ipv4();
        $email = $this->faker->email();
        $query = sprintf(
            <<<'GRAPHQL'
mutation {
  passkeySignInOptionsUser(input: {
    credential: { email: "%s" }
  }) {
    user { challengeId }
  }
}
GRAPHQL,
            $email
        );

        $request = $this->createGraphQlRequest($query, $clientIp);

        self::assertSame(
            $this->signInIpLimiters($clientIp),
            $this->resolver->resolveEndpointLimiters($request)
        );
    }

    public function testPasskeySigninOptionsUsesTopLevelEmailBeforeNestedDecoy(): void
    {
        $clientIp = $this->faker->ipv4();
        $email = $this->faker->email();
        $decoyEmail = $this->faker->email();
        $query = sprintf(
            <<<'GRAPHQL'
mutation {
  passkeySignInOptionsUser(input: {
    email: "%s"
    credential: { email: "%s" }
  }) {
    user { challengeId }
  }
}
GRAPHQL,
            $email,
            $decoyEmail
        );

        $request = $this->createGraphQlRequest($query, $clientIp);

        self::assertSame(
            $this->signInLimiters($clientIp, $email),
            $this->resolver->resolveEndpointLimiters($request)
        );
    }

    public function testPasskeySigninOptionsInputVariableIgnoresNestedCredentialEmail(): void
    {
        $clientIp = $this->faker->ipv4();
        $query = <<<'GRAPHQL'
mutation($input: passkeySignInOptionsUserInput!) {
  passkeySignInOptionsUser(input: $input) {
    user { challengeId }
  }
}
GRAPHQL;

        $request = $this->createGraphQlRequest(
            $query,
            $clientIp,
            ['input' => ['credential' => ['email' => $this->faker->email()]]]
        );

        self::assertSame(
            $this->signInIpLimiters($clientIp),
            $this->resolver->resolveEndpointLimiters($request)
        );
    }

    public function testPasskeySigninOptionsInputVariableUsesTopLevelEmailBeforeNestedDecoy(): void
    {
        $clientIp = $this->faker->ipv4();
        $email = $this->faker->email();
        $query = <<<'GRAPHQL'
mutation($input: passkeySignInOptionsUserInput!) {
  passkeySignInOptionsUser(input: $input) {
    user { challengeId }
  }
}
GRAPHQL;

        $request = $this->createGraphQlRequest(
            $query,
            $clientIp,
            [
                'input' => [
                    'email' => $email,
                    'credential' => ['email' => $this->faker->email()],
                ],
            ]
        );

        self::assertSame(
            $this->signInLimiters($clientIp, $email),
            $this->resolver->resolveEndpointLimiters($request)
        );
    }

    public function testPasskeySigninOptionsUsesMultipartOperationsPayloadLimiters(): void
    {
        $clientIp = $this->faker->ipv4();
        $email = $this->faker->email();
        $query = <<<'GRAPHQL'
mutation($input: passkeySignInOptionsUserInput!) {
  passkeySignInOptionsUser(input: $input) {
    user { challengeId }
  }
}
GRAPHQL;

        $request = $this->createGraphQlOperationsRequest(
            [
                'query' => $query,
                'variables' => ['input' => ['email' => $email]],
            ],
            $clientIp,
            'multipart/form-data; boundary=----rate-limit'
        );

        self::assertSame(
            $this->signInLimiters($clientIp, $email),
            $this->resolver->resolveEndpointLimiters($request)
        );
    }

    public function testPasskeySigninOptionsFormOperationsOperationNameUsesQueryStringQuery(): void
    {
        $clientIp = $this->faker->ipv4();
        $email = $this->faker->email();
        $query = <<<'GRAPHQL'
mutation Passkey($input: passkeySignInOptionsUserInput!) {
  passkeySignInOptionsUser(input: $input) {
    user { challengeId }
  }
}
GRAPHQL;

        $request = $this->createGraphQlOperationsRequest(
            ['operationName' => 'Passkey'],
            $clientIp,
            'application/x-www-form-urlencoded'
        );
        $request->query->set('query', $query);
        $request->query->set('variables', json_encode(['input' => ['email' => $email]], JSON_THROW_ON_ERROR));

        self::assertSame(
            $this->signInLimiters($clientIp, $email),
            $this->resolver->resolveEndpointLimiters($request)
        );
    }

    public function testPasskeySigninCompleteFormOperationsOperationNameUsesQueryStringQuery(): void
    {
        $clientIp = $this->faker->ipv4();
        $challengeId = $this->faker->uuid();
        $query = sprintf(
            <<<'GRAPHQL'
mutation Passkey {
  passkeySignInCompleteUser(input: { challengeId: "%s" }) {
    user { accessToken }
  }
}
GRAPHQL,
            $challengeId
        );

        $request = $this->createGraphQlOperationsRequest(
            ['operationName' => 'Passkey'],
            $clientIp,
            'application/x-www-form-urlencoded'
        );
        $request->query->set('query', $query);

        self::assertSame(
            $this->signInIpLimiters($clientIp),
            $this->resolver->resolveEndpointLimiters($request)
        );
    }

    public function testMalformedFormOperationsFallsBackToQueryStringLimiters(): void
    {
        $clientIp = $this->faker->ipv4();
        $email = $this->faker->email();
        $query = sprintf(self::PASSKEY_SIGNIN_OPTIONS_MUTATION, $email);
        $request = Request::create(
            self::GRAPHQL_PATH,
            'POST',
            ['operations' => '{'],
            [],
            [],
            ['CONTENT_TYPE' => 'application/x-www-form-urlencoded', 'REMOTE_ADDR' => $clientIp]
        );
        $request->query->set('query', $query);

        self::assertSame(
            $this->signInLimiters($clientIp, $email),
            $this->resolver->resolveEndpointLimiters($request)
        );
    }

    public function testFormOperationsVariablesOverlayQueryStringQuery(): void
    {
        $clientIp = $this->faker->ipv4();
        $email = $this->faker->email();
        $query = <<<'GRAPHQL'
mutation Passkey($input: passkeySignInOptionsUserInput!) {
  passkeySignInOptionsUser(input: $input) {
    user { challengeId }
  }
}
GRAPHQL;

        $request = $this->createGraphQlOperationsRequest(
            [
                'operationName' => 'Passkey',
                'variables' => ['input' => ['email' => $email]],
            ],
            $clientIp,
            'application/x-www-form-urlencoded'
        );
        $request->query->set('query', $query);

        self::assertSame(
            $this->signInLimiters($clientIp, $email),
            $this->resolver->resolveEndpointLimiters($request)
        );
    }

    public function testInvalidJsonPayloadFallsBackToQueryStringLimiters(): void
    {
        $clientIp = $this->faker->ipv4();
        $email = $this->faker->email();
        $query = sprintf(self::PASSKEY_SIGNIN_OPTIONS_MUTATION, $email);
        $request = Request::create(
            self::GRAPHQL_PATH,
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'REMOTE_ADDR' => $clientIp],
            '{'
        );
        $request->query->set('query', $query);

        self::assertSame(
            $this->signInLimiters($clientIp, $email),
            $this->resolver->resolveEndpointLimiters($request)
        );
    }

    public function testInvalidQueryStringVariablesUsesOnlyIpLimiter(): void
    {
        $clientIp = $this->faker->ipv4();
        $query = <<<'GRAPHQL'
mutation($input: passkeySignInOptionsUserInput!) {
  passkeySignInOptionsUser(input: $input) {
    user { challengeId }
  }
}
GRAPHQL;
        $request = Request::create(
            self::GRAPHQL_PATH,
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'text/plain', 'REMOTE_ADDR' => $clientIp]
        );
        $request->query->set('query', $query);
        $request->query->set('variables', '{');

        self::assertSame(
            $this->signInIpLimiters($clientIp),
            $this->resolver->resolveEndpointLimiters($request)
        );
    }

    public function testRawGraphQlContentTypeUsesRequestBodyLimiters(): void
    {
        $clientIp = $this->faker->ipv4();
        $challengeId = $this->faker->uuid();
        $request = Request::create(
            self::GRAPHQL_PATH,
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/graphql', 'REMOTE_ADDR' => $clientIp],
            sprintf(
                'mutation { passkeySignInCompleteUser(input: { challengeId: "%s" }) { user { accessToken } } }',
                $challengeId
            )
        );
        $request->setFormat('graphql', ['application/graphql']);

        self::assertSame(
            $this->signInIpLimiters($clientIp),
            $this->resolver->resolveEndpointLimiters($request)
        );
    }

    public function testRawGraphQlEmptyBodyUsesQueryStringLimiters(): void
    {
        $clientIp = $this->faker->ipv4();
        $email = $this->faker->email();
        $request = Request::create(
            self::GRAPHQL_PATH,
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/graphql', 'REMOTE_ADDR' => $clientIp]
        );
        $request->query->set('query', sprintf(self::PASSKEY_SIGNIN_OPTIONS_MUTATION, $email));

        self::assertSame(
            $this->signInLimiters($clientIp, $email),
            $this->resolver->resolveEndpointLimiters($request)
        );
    }

    public function testPasskeySigninOptionsMultipartOperationsIgnoresNestedCredentialEmail(): void
    {
        $clientIp = $this->faker->ipv4();
        $query = <<<'GRAPHQL'
mutation($input: passkeySignInOptionsUserInput!) {
  passkeySignInOptionsUser(input: $input) {
    user { challengeId }
  }
}
GRAPHQL;

        $request = $this->createGraphQlOperationsRequest(
            [
                'query' => $query,
                'variables' => ['input' => ['credential' => ['email' => $this->faker->email()]]],
            ],
            $clientIp,
            'application/x-www-form-urlencoded'
        );

        self::assertSame(
            $this->signInIpLimiters($clientIp),
            $this->resolver->resolveEndpointLimiters($request)
        );
    }

    public function testPasskeySigninOptionsDirectEmailArgumentUsesEmailLimiter(): void
    {
        $clientIp = $this->faker->ipv4();
        $email = $this->faker->email();
        $query = sprintf(
            'mutation { passkeySignInOptionsUser(email: "%s") { user { challengeId } } }',
            $email
        );

        $request = $this->createGraphQlRequest($query, $clientIp);

        self::assertSame(
            $this->signInLimiters($clientIp, $email),
            $this->resolver->resolveEndpointLimiters($request)
        );
    }

    public function testPasskeySigninOptionsContinuesPastNonInputArgument(): void
    {
        $clientIp = $this->faker->ipv4();
        $email = $this->faker->email();
        $query = sprintf(
            <<<'GRAPHQL'
mutation {
  passkeySignInOptionsUser(
    clientMutationId: "client-id"
    input: { email: "%s" }
  ) {
    user { challengeId }
  }
}
GRAPHQL,
            $email
        );

        $request = $this->createGraphQlRequest($query, $clientIp);

        self::assertSame(
            $this->signInLimiters($clientIp, $email),
            $this->resolver->resolveEndpointLimiters($request)
        );
    }

    public function testPasskeySigninOptionsStringInputUsesOnlyIpLimiter(): void
    {
        $clientIp = $this->faker->ipv4();
        $query = 'mutation { passkeySignInOptionsUser(input: "not-object") { user { challengeId } } }';

        $request = $this->createGraphQlRequest($query, $clientIp);

        self::assertSame(
            $this->signInIpLimiters($clientIp),
            $this->resolver->resolveEndpointLimiters($request)
        );
    }

    public function testPasskeySigninOptionsWithoutEmailArgumentUsesOnlyIpLimiter(): void
    {
        $clientIp = $this->faker->ipv4();
        $query = 'mutation { passkeySignInOptionsUser(other: "value") { user { challengeId } } }';

        $request = $this->createGraphQlRequest($query, $clientIp);

        self::assertSame(
            $this->signInIpLimiters($clientIp),
            $this->resolver->resolveEndpointLimiters($request)
        );
    }

    public function testPasskeySigninOptionsInputVariableRejectsNonArrayInput(): void
    {
        $clientIp = $this->faker->ipv4();
        $query = <<<'GRAPHQL'
mutation($input: passkeySignInOptionsUserInput!) {
  passkeySignInOptionsUser(input: $input) {
    user { challengeId }
  }
}
GRAPHQL;

        $request = $this->createGraphQlRequest(
            $query,
            $clientIp,
            ['input' => 'not-array']
        );

        self::assertSame(
            $this->signInIpLimiters($clientIp),
            $this->resolver->resolveEndpointLimiters($request)
        );
    }

    public function testPasskeySigninOptionsInputVariableDefaultIgnoresNestedCredentialEmail(): void
    {
        $clientIp = $this->faker->ipv4();
        $query = sprintf(
            <<<'GRAPHQL'
mutation($input: passkeySignInOptionsUserInput = {
  credential: { email: "%s" }
}) {
  passkeySignInOptionsUser(input: $input) {
    user { challengeId }
  }
}
GRAPHQL,
            $this->faker->email()
        );

        $request = $this->createGraphQlRequest($query, $clientIp);

        self::assertSame(
            $this->signInIpLimiters($clientIp),
            $this->resolver->resolveEndpointLimiters($request)
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
            ['CONTENT_TYPE' => 'multipart/form-data; boundary=----rate-limit', 'REMOTE_ADDR' => $clientIp]
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
            sprintf('not valid GraphQL passkeySignInCompleteUser credential email %s', $this->faker->email())
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

    /**
     * @param array<string, mixed> $variables
     * @param array<string, mixed> $extraPayload
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
     * @param array<string, mixed> $operations
     */
    private function createGraphQlOperationsRequest(
        array $operations,
        string $clientIp,
        string $contentType
    ): Request {
        return Request::create(
            self::GRAPHQL_PATH,
            'POST',
            ['operations' => json_encode($operations, JSON_THROW_ON_ERROR)],
            [],
            [],
            ['CONTENT_TYPE' => $contentType, 'REMOTE_ADDR' => $clientIp]
        );
    }

    private function createSelectedPasskeySigninQueryWithStringTokens(string $decoyEmail): string
    {
        return sprintf(
            <<<'GRAPHQL'
mutation Decoy {
  passkeySignInOptionsUser(input: { email: "%s" }) { user { challengeId } }
}
mutation Real($e: String!) {
  passkeySignInOptionsUser(input: {
    clientMutationId: "mutation Stop email: \"%s\""
    email: $e
  }) { user { challengeId } }
}
GRAPHQL,
            $this->faker->email(),
            $decoyEmail
        );
    }

    /**
     * @param list<array{name: string, key: string}> $expectedLimiters
     * @param array{
     *     method?: string,
     *     path?: string,
     *     variables?: array<string, mixed>
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
