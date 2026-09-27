<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\RateLimit;

use Symfony\Component\HttpFoundation\Request;

final class ApiRateLimitGraphQlResolverEdgeCasesTest extends RateLimitClientTestCase
{
    public function testNonGraphQlPathReturnsNoTargets(): void
    {
        $request = Request::create('/api/users', 'POST');

        self::assertSame([], $this->createGraphQlResolver()->resolve($request));
    }

    public function testGraphQlGetRequestReturnsNoTargets(): void
    {
        $request = Request::create(self::GRAPHQL_ENDPOINT, 'GET');

        self::assertSame([], $this->createGraphQlResolver()->resolve($request));
    }

    public function testNonSensitiveMutationReturnsNoTargets(): void
    {
        $request = $this->createGraphQlRequest('mutation { createUser(input: {}) { id } }');

        self::assertSame([], $this->createGraphQlResolver()->resolve($request));
    }

    public function testInvalidJsonBodyReturnsNoTargets(): void
    {
        $request = Request::create(
            self::GRAPHQL_ENDPOINT,
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'not-json'
        );

        self::assertSame([], $this->createGraphQlResolver()->resolve($request));
    }

    public function testMissingQueryFieldReturnsNoTargets(): void
    {
        $request = Request::create(
            self::GRAPHQL_ENDPOINT,
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['variables' => ['input' => []]], JSON_THROW_ON_ERROR)
        );

        self::assertSame([], $this->createGraphQlResolver()->resolve($request));
    }

    public function testScalarJsonBodyReturnsNoTargets(): void
    {
        $request = Request::create(
            self::GRAPHQL_ENDPOINT,
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '"signIn"'
        );

        self::assertSame([], $this->createGraphQlResolver()->resolve($request));
    }

    public function testNullJsonBodyReturnsNoTargets(): void
    {
        $request = Request::create(
            self::GRAPHQL_ENDPOINT,
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'null'
        );

        self::assertSame([], $this->createGraphQlResolver()->resolve($request));
    }

    public function testNonArrayVariablesAreIgnored(): void
    {
        $request = Request::create(
            self::GRAPHQL_ENDPOINT,
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(
                ['query' => 'mutation { signIn(input: {}) { id } }', 'variables' => 'not-an-array'],
                JSON_THROW_ON_ERROR
            )
        );

        $byName = array_column($this->createGraphQlResolver()->resolve($request), 'key', 'name');

        self::assertSame(['signin_ip'], array_keys($byName));
    }

    public function testEmailFromVariablesIsLowercasedAndTrimmed(): void
    {
        $request = $this->createGraphQlRequest(
            'mutation($input: signInInput!) { signIn(input: $input) { user { id } } }',
            ['input' => ['email' => "  USER@Example.COM\t"]],
            '198.51.100.6'
        );

        $byName = array_column($this->createGraphQlResolver()->resolve($request), 'key', 'name');

        self::assertSame('email:user@example.com', $byName['signin_email']);
    }

    public function testTopLevelVariablesAreUsedWhenInputKeyMissing(): void
    {
        $request = $this->createGraphQlRequest(
            'mutation($email: String!) { signIn(email: $email) { user { id } } }',
            ['email' => 'top.level@example.com'],
            '198.51.100.7'
        );

        $byName = array_column($this->createGraphQlResolver()->resolve($request), 'key', 'name');

        self::assertSame('email:top.level@example.com', $byName['signin_email']);
    }

    public function testEmptyEmailStringIsTreatedAsAbsent(): void
    {
        $request = $this->createGraphQlRequest(
            'mutation($input: signInInput!) { signIn(input: $input) { user { id } } }',
            ['input' => ['email' => '']],
            '198.51.100.44'
        );

        $byName = array_column($this->createGraphQlResolver()->resolve($request), 'key', 'name');

        self::assertArrayHasKey('signin_ip', $byName);
        self::assertArrayNotHasKey('signin_email', $byName);
    }

    public function testEmailIsResolvedRegardlessOfItsPositionInInput(): void
    {
        $request = $this->createGraphQlRequest(
            'mutation($input: signInInput!) { signIn(input: $input) { user { id } } }',
            ['input' => ['password' => 'secret', 'email' => 'positioned@example.com']],
            '198.51.100.47'
        );

        $byName = array_column($this->createGraphQlResolver()->resolve($request), 'key', 'name');

        self::assertSame('email:positioned@example.com', $byName['signin_email']);
    }

    public function testNonArrayInputKeyIsIgnored(): void
    {
        $request = Request::create(
            self::GRAPHQL_ENDPOINT,
            'POST',
            [],
            [],
            [],
            ['REMOTE_ADDR' => '198.51.100.46', 'CONTENT_TYPE' => 'application/json'],
            json_encode(
                [
                    'query' => 'mutation { signIn(input: {}) { id } }',
                    'variables' => ['input' => 'not-an-array'],
                ],
                JSON_THROW_ON_ERROR
            )
        );

        $byName = array_column($this->createGraphQlResolver()->resolve($request), 'key', 'name');

        self::assertSame(['signin_ip'], array_keys($byName));
    }

    public function testNonStringQueryFieldReturnsNoTargets(): void
    {
        $request = Request::create(
            self::GRAPHQL_ENDPOINT,
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['query' => 12345], JSON_THROW_ON_ERROR)
        );

        self::assertSame([], $this->createGraphQlResolver()->resolve($request));
    }

    public function testNestedInputObjectIsDecodedAssociatively(): void
    {
        $request = Request::create(
            self::GRAPHQL_ENDPOINT,
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(
                [
                    'query' => 'mutation { signIn(input: {}) { id } }',
                    'variables' => ['input' => ['email' => 'nested@example.com']],
                ],
                JSON_THROW_ON_ERROR
            )
        );

        $byName = array_column($this->createGraphQlResolver()->resolve($request), 'key', 'name');

        self::assertSame('email:nested@example.com', $byName['signin_email']);
    }
}
