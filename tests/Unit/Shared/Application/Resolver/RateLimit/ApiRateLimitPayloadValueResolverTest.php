<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\RateLimit;

use App\Shared\Application\Resolver\RateLimit\ApiRateLimitGraphQlQueryInspector;
use App\Shared\Application\Resolver\RateLimit\ApiRateLimitPayloadValueResolver;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

final class ApiRateLimitPayloadValueResolverTest extends UnitTestCase
{
    private const GRAPHQL_PATH = '/api/graphql';
    private const PASSKEY_SIGNIN_OPTIONS_MUTATION =
        'mutation { passkeySignInOptionsUser(input: { email: "%s" }) { user { challengeId } } }';

    public function testResolveReturnsNullForEmptyBody(): void
    {
        $resolver = $this->createResolver();
        $request = Request::create('/api/users', 'POST', [], [], [], [], '');

        self::assertNull($resolver->resolve($request, ['email']));
    }

    public function testResolveReturnsValueFromJsonBody(): void
    {
        $value = $this->faker->word();
        $resolver = $this->createResolver();
        $request = Request::create(
            '/api/users',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['my_key' => $value], JSON_THROW_ON_ERROR)
        );

        self::assertSame($value, $resolver->resolve($request, ['my_key']));
    }

    public function testResolveReturnsValueFromFormBodyWhenJsonDecodeFails(): void
    {
        $value = $this->faker->word();
        $resolver = $this->createResolver();
        $request = Request::create(
            '/api/users',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'],
            http_build_query(['my_key' => $value])
        );

        self::assertSame($value, $resolver->resolve($request, ['my_key']));
    }

    public function testResolveReturnsFirstMatchingKey(): void
    {
        $firstValue = $this->faker->word();
        $resolver = $this->createResolver();
        $request = Request::create(
            '/api/users',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(
                ['key_a' => $firstValue, 'key_b' => $this->faker->word()],
                JSON_THROW_ON_ERROR
            )
        );

        self::assertSame($firstValue, $resolver->resolve($request, ['key_a', 'key_b']));
    }

    public function testResolveReturnsNullWhenJsonPayloadIsScalar(): void
    {
        $resolver = $this->createResolver();
        $request = Request::create(
            '/api/users',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($this->faker->word(), JSON_THROW_ON_ERROR)
        );

        self::assertNull($resolver->resolve($request, ['my_key']));
    }

    public function testResolveIgnoresEmptyAndNonStringValues(): void
    {
        $fallback = $this->faker->word();
        $resolver = $this->createResolver();
        $request = Request::create(
            '/api/users',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(
                ['key_a' => '', 'key_b' => 42, 'key_c' => $fallback],
                JSON_THROW_ON_ERROR
            )
        );

        self::assertSame($fallback, $resolver->resolve($request, ['key_a', 'key_b', 'key_c']));
    }

    public function testResolveReturnsValueFromNestedJsonBody(): void
    {
        $email = $this->faker->email();
        $resolver = $this->createResolver();
        $content = json_encode(['variables' => ['input' => compact('email')]], JSON_THROW_ON_ERROR);
        $request = $this->createGraphQlRequest($content);

        self::assertSame($email, $resolver->resolve($request, ['email']));
    }

    public function testResolveReturnsGraphQlInputVariableBeforeUnrelatedJsonKey(): void
    {
        $email = $this->faker->email();
        $query = <<<'GRAPHQL'
mutation($input: passkeySignInOptionsUserInput!) {
  passkeySignInOptionsUser(input: $input) {
    user { challengeId }
  }
}
GRAPHQL;
        $request = $this->createGraphQlRequest(
            json_encode(
                [
                    'email' => $this->faker->email(),
                    'query' => $query,
                    'variables' => ['input' => compact('email')],
                ],
                JSON_THROW_ON_ERROR
            )
        );

        self::assertSame($email, $this->createResolver()->resolve($request, ['email']));
    }

    public function testResolveReturnsValueFromInlineGraphQlArgument(): void
    {
        $email = $this->faker->email();
        $resolver = $this->createResolver();
        $request = $this->createGraphQlRequest(
            json_encode(
                ['query' => sprintf(self::PASSKEY_SIGNIN_OPTIONS_MUTATION, $email)],
                JSON_THROW_ON_ERROR
            )
        );

        self::assertSame($email, $resolver->resolve($request, ['email']));
    }

    public function testResolveReturnsValueFromGraphQlArgumentVariableWithDifferentName(): void
    {
        $email = $this->faker->email();
        $query = <<<'GRAPHQL'
mutation($e: String!) {
  passkeySignInOptionsUser(input: { email: $e }) {
    user { challengeId }
  }
}
GRAPHQL;
        $resolver = $this->createResolver();
        $request = $this->createGraphQlRequest(
            json_encode(
                ['query' => $query, 'variables' => ['e' => $email]],
                JSON_THROW_ON_ERROR
            )
        );

        self::assertSame($email, $resolver->resolve($request, ['email']));
    }

    public function testResolveReturnsGraphQlArgumentVariableBeforeUnrelatedJsonKey(): void
    {
        $decoyEmail = $this->faker->email();
        $email = $this->faker->email();
        $query = <<<'GRAPHQL'
mutation($e: String!) {
  passkeySignInOptionsUser(input: { email: $e }) {
    user { challengeId }
  }
}
GRAPHQL;
        $resolver = $this->createResolver();
        $request = $this->createGraphQlRequest(
            json_encode(
                ['email' => $decoyEmail, 'query' => $query, 'variables' => ['e' => $email]],
                JSON_THROW_ON_ERROR
            )
        );

        self::assertSame($email, $resolver->resolve($request, ['email']));
    }

    public function testResolveUsesSelectedGraphQlOperation(): void
    {
        $decoyEmail = $this->faker->email();
        $email = $this->faker->email();
        $request = $this->createGraphQlRequest(
            json_encode(
                [
                    'operationName' => 'Real',
                    'query' => $this->createSelectedPasskeySigninQuery($decoyEmail),
                    'variables' => ['e' => $email],
                ],
                JSON_THROW_ON_ERROR
            )
        );

        self::assertSame($email, $this->createResolver()->resolve($request, ['email']));
    }

    public function testResolveIgnoresOperationTokensInsideSelectedGraphQlStrings(): void
    {
        $decoyEmail = $this->faker->email();
        $email = $this->faker->email();
        $request = $this->createGraphQlRequest(
            json_encode(
                [
                    'operationName' => 'Real',
                    'query' => $this->createSelectedPasskeySigninQueryWithStringTokens($decoyEmail),
                    'variables' => ['e' => $email],
                ],
                JSON_THROW_ON_ERROR
            )
        );

        self::assertSame($email, $this->createResolver()->resolve($request, ['email']));
    }

    public function testResolveReturnsNullForValidGraphQlWithoutRequestedArgument(): void
    {
        $request = $this->createGraphQlRequest(
            json_encode(
                [
                    'query' => <<<'GRAPHQL'
mutation Real {
  updateProject(input: { id: "project-id" }) { project { id } }
}
GRAPHQL,
                ],
                JSON_THROW_ON_ERROR
            )
        );

        self::assertNull($this->createResolver()->resolve($request, ['email']));
    }

    public function testResolveReturnsNullWhenGraphQlArgumentVariableIsMissing(): void
    {
        $query = <<<'GRAPHQL'
mutation Real($e: String!) {
  passkeySignInOptionsUser(input: { email: $e }) {
    user { challengeId }
  }
}
GRAPHQL;
        $request = $this->createGraphQlRequest(
            json_encode(
                [
                    'query' => $query,
                    'variables' => [],
                ],
                JSON_THROW_ON_ERROR
            )
        );

        self::assertNull($this->createResolver()->resolve($request, ['email']));
    }

    public function testResolveSkipsNonArrayGraphQlInputVariable(): void
    {
        $query = <<<'GRAPHQL'
mutation Real($input: passkeySignInOptionsUserInput!) {
  passkeySignInOptionsUser(input: $input) {
    user { challengeId }
  }
}
GRAPHQL;
        $request = $this->createGraphQlRequest(
            json_encode(
                [
                    'query' => $query,
                    'variables' => ['input' => $this->faker->word()],
                ],
                JSON_THROW_ON_ERROR
            )
        );

        self::assertNull($this->createResolver()->resolve($request, ['email']));
    }

    public function testResolveInlineGraphQlArgumentRequiresExactKeyBoundary(): void
    {
        $ignoredEmail = $this->faker->email();
        $email = $this->faker->email();
        $resolver = $this->createResolver();
        $query = sprintf(
            'mutation { m(input: { notemail: "%s", email: "%s" }) { id } }',
            $ignoredEmail,
            $email
        );
        $content = json_encode(['query' => $query], JSON_THROW_ON_ERROR);
        $request = $this->createGraphQlRequest($content);

        self::assertSame($email, $resolver->resolve($request, ['email']));
    }

    private function createResolver(): ApiRateLimitPayloadValueResolver
    {
        return new ApiRateLimitPayloadValueResolver(
            $this->createJsonSerializer(),
            new ApiRateLimitGraphQlQueryInspector()
        );
    }

    private function createSelectedPasskeySigninQuery(string $decoyEmail): string
    {
        return sprintf(
            <<<'GRAPHQL'
mutation Decoy {
  passkeySignInOptionsUser(input: { email: "%s" }) { user { challengeId } }
}
mutation Real($e: String!) {
  passkeySignInOptionsUser(input: { email: $e }) { user { challengeId } }
}
GRAPHQL,
            $decoyEmail
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

    private function createGraphQlRequest(string $content): Request
    {
        return Request::create(
            self::GRAPHQL_PATH,
            'POST',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $content
        );
    }
}
