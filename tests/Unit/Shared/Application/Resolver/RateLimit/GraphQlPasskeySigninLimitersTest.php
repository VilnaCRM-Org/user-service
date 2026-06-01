<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\RateLimit;

final class GraphQlPasskeySigninLimitersTest extends GraphQlRateLimitTestCase
{
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
        $request = $this->createGraphQlRequest(
            $this->passkeySigninOptionsAliasedQuery(),
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
        $query = sprintf($this->passkeySigninOptionsNestedCredentialQuery(), $this->faker->email());

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
        $query = sprintf(
            $this->passkeySigninOptionsTopLevelAndNestedEmailQuery(),
            $email,
            $this->faker->email()
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
        $request = $this->createGraphQlRequest(
            $this->passkeySigninOptionsInputQuery(),
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
        $request = $this->createGraphQlRequest(
            $this->passkeySigninOptionsInputQuery(),
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

    public function testPasskeySigninOptionsInputVariableRejectsNonStringTopLevelEmail(): void
    {
        $clientIp = $this->faker->ipv4();
        $request = $this->createGraphQlRequest(
            $this->passkeySigninOptionsInputQuery(),
            $clientIp,
            [
                'input' => [
                    'email' => 42,
                    'credential' => ['email' => $this->faker->email()],
                ],
            ]
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
        $query = sprintf($this->passkeySigninOptionsClientMutationIdQuery(), $email);

        $request = $this->createGraphQlRequest($query, $clientIp);

        self::assertSame(
            $this->signInLimiters($clientIp, $email),
            $this->resolver->resolveEndpointLimiters($request)
        );
    }

    public function testPasskeySigninOptionsStringInputUsesOnlyIpLimiter(): void
    {
        $clientIp = $this->faker->ipv4();
        $query = <<<'GRAPHQL'
mutation {
  passkeySignInOptionsUser(input: "not-object") { user { challengeId } }
}
GRAPHQL;

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
        $request = $this->createGraphQlRequest(
            $this->passkeySigninOptionsInputQuery(),
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

    private function passkeySigninOptionsAliasedQuery(): string
    {
        return <<<'GRAPHQL'
mutation($e: String!) {
  passkeySignInOptionsUser(input: { email: $e }) {
    user { challengeId }
  }
}
GRAPHQL;
    }

    private function passkeySigninOptionsNestedCredentialQuery(): string
    {
        return <<<'GRAPHQL'
mutation {
  passkeySignInOptionsUser(input: {
    credential: { email: "%s" }
  }) {
    user { challengeId }
  }
}
GRAPHQL;
    }

    private function passkeySigninOptionsTopLevelAndNestedEmailQuery(): string
    {
        return <<<'GRAPHQL'
mutation {
  passkeySignInOptionsUser(input: {
    email: "%s"
    credential: { email: "%s" }
  }) {
    user { challengeId }
  }
}
GRAPHQL;
    }

    private function passkeySigninOptionsClientMutationIdQuery(): string
    {
        return <<<'GRAPHQL'
mutation {
  passkeySignInOptionsUser(
    clientMutationId: "client-id"
    input: { email: "%s" }
  ) {
    user { challengeId }
  }
}
GRAPHQL;
    }
}
