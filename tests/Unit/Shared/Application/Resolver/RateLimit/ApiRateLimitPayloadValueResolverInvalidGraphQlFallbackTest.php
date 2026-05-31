<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\RateLimit;

use App\Shared\Application\Resolver\RateLimit\ApiRateLimitGraphQlQueryInspector;
use App\Shared\Application\Resolver\RateLimit\ApiRateLimitPayloadValueResolver;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

final class ApiRateLimitPayloadValueResolverInvalidGraphQlFallbackTest extends UnitTestCase
{
    public function testResolveInlineGraphQlArgumentEscapesRegexCharactersInKey(): void
    {
        $value = $this->faker->word();
        $query = sprintf(
            'mutation { m(input: { clientXid: "wrong", client.id: "%s" }) { ok } }',
            $value
        );
        $request = $this->createGraphQlRequest(
            json_encode(['query' => $query], JSON_THROW_ON_ERROR)
        );

        self::assertSame($value, $this->createResolver()->resolve($request, ['client.id']));
    }

    public function testResolveFallsBackToRegexVariableForInvalidGraphQl(): void
    {
        $email = $this->faker->email();
        $query = <<<'GRAPHQL'
mutation($e: String!) {
  m(input: { client.id: "invalid", email: $e }) { ok }
}
GRAPHQL;
        $request = $this->createGraphQlRequest(
            json_encode(
                [
                    'query' => $query,
                    'variables' => ['e' => $email],
                ],
                JSON_THROW_ON_ERROR
            )
        );

        self::assertSame($email, $this->createResolver()->resolve($request, ['email']));
    }

    public function testResolveEscapesRegexCharactersInVariableKey(): void
    {
        $value = $this->faker->word();
        $query = <<<'GRAPHQL'
mutation($wrong: String!, $target: String!) {
  m(input: { clientXid: $wrong, client.id: $target }) { ok }
}
GRAPHQL;
        $request = $this->createGraphQlRequest(
            json_encode(
                ['query' => $query, 'variables' => ['wrong' => 'wrong', 'target' => $value]],
                JSON_THROW_ON_ERROR
            )
        );

        self::assertSame($value, $this->createResolver()->resolve($request, ['client.id']));
    }

    public function testResolveTriesLaterKeysWhenInvalidGraphQlVariableKeyMisses(): void
    {
        $email = $this->faker->email();
        $query = <<<'GRAPHQL'
mutation($e: String!) {
  m(input: { email: $e, client.id: "invalid" }) { ok }
}
GRAPHQL;
        $request = $this->createGraphQlRequest(
            json_encode(['query' => $query, 'variables' => ['e' => $email]], JSON_THROW_ON_ERROR)
        );

        self::assertSame($email, $this->createResolver()->resolve($request, ['missing', 'email']));
    }

    public function testResolveRejectsInvalidGraphQlNonStringVariableValue(): void
    {
        $query = <<<'GRAPHQL'
mutation($e: String!) {
  m(input: { email: $e, client.id: "invalid" }) { ok }
}
GRAPHQL;
        $request = $this->createGraphQlRequest(
            json_encode(['query' => $query, 'variables' => ['e' => 42]], JSON_THROW_ON_ERROR)
        );

        self::assertNull($this->createResolver()->resolve($request, ['email']));
    }

    public function testResolveFallsBackToRegexInputVariableForInvalidGraphQl(): void
    {
        $email = $this->faker->email();
        $query = <<<'GRAPHQL'
mutation($input: passkeySignInOptionsUserInput!) {
  m(input: $input, client.id: "invalid") { ok }
}
GRAPHQL;
        $request = $this->createGraphQlRequest(
            json_encode(
                [
                    'query' => $query,
                    'variables' => ['input' => compact('email')],
                ],
                JSON_THROW_ON_ERROR
            )
        );

        self::assertSame($email, $this->createResolver()->resolve($request, ['email']));
    }

    public function testResolveFallsBackToLaterRegexInputVariableForInvalidGraphQl(): void
    {
        $email = $this->faker->email();
        $query = <<<'GRAPHQL'
mutation($first: passkeySignInOptionsUserInput!, $second: passkeySignInOptionsUserInput!) {
  m(input: $first, input: $second, client.id: "invalid") { ok }
}
GRAPHQL;
        $request = $this->createGraphQlRequest(
            json_encode(
                [
                    'query' => $query,
                    'variables' => ['first' => 'not-array', 'second' => compact('email')],
                ],
                JSON_THROW_ON_ERROR
            )
        );

        self::assertSame($email, $this->createResolver()->resolve($request, ['email']));
    }

    public function testResolveReturnsNullForInvalidGraphQlWithoutVariables(): void
    {
        $request = $this->createGraphQlRequest(
            json_encode(
                ['query' => 'mutation { m(client.id: "invalid") { ok } }'],
                JSON_THROW_ON_ERROR
            )
        );

        self::assertNull($this->createResolver()->resolve($request, ['email']));
    }

    public function testResolveReturnsNullWhenInvalidGraphQlHasNoInputVariable(): void
    {
        $request = $this->createGraphQlRequest(
            json_encode(
                [
                    'query' => 'mutation { m(client.id: "invalid") { ok } }',
                    'variables' => [],
                ],
                JSON_THROW_ON_ERROR
            )
        );

        self::assertNull($this->createResolver()->resolve($request, ['email']));
    }

    public function testResolveReturnsNullWhenInvalidGraphQlInputVariableIsNotArray(): void
    {
        $query = <<<'GRAPHQL'
mutation($input: passkeySignInOptionsUserInput!) {
  m(input: $input, client.id: "invalid") { ok }
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

    private function createResolver(): ApiRateLimitPayloadValueResolver
    {
        return new ApiRateLimitPayloadValueResolver(
            $this->createJsonSerializer(),
            new ApiRateLimitGraphQlQueryInspector()
        );
    }

    private function createGraphQlRequest(string $content): Request
    {
        return Request::create(
            '/api/graphql',
            'POST',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $content
        );
    }
}
