<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\RateLimit;

use App\Shared\Application\Resolver\RateLimit\ApiRateLimitRequestResolver;
use Symfony\Component\HttpFoundation\Request;

/**
 * @phpstan-type GraphQlLeaf bool|float|int|string|null
 * @phpstan-type GraphQlNestedValue array<array-key, GraphQlLeaf|array<array-key, GraphQlLeaf>>
 * @phpstan-type GraphQlPayloadValue GraphQlLeaf|GraphQlNestedValue
 *
 * @psalm-type GraphQlLeaf = bool|float|int|string|null
 * @psalm-type GraphQlNestedValue = array<array-key, GraphQlLeaf|array<array-key, GraphQlLeaf>>
 * @psalm-type GraphQlPayloadValue = GraphQlLeaf|GraphQlNestedValue
 */
abstract class GraphQlRateLimitTestCase extends RateLimitClientTestCase
{
    protected const GRAPHQL_PATH = '/api/graphql';
    protected const FORM_CONTENT_TYPE = 'application/x-www-form-urlencoded';
    protected const MULTIPART_CONTENT_TYPE = 'multipart/form-data; boundary=----rate-limit';
    protected const PASSKEY_SIGNIN_OPTIONS_MUTATION = <<<'GRAPHQL'
mutation {
  passkeySignInOptionsUser(input: { email: "%s" }) {
    user { challengeId }
  }
}
GRAPHQL;
    protected const PROJECT_UPDATE_MUTATION = <<<'GRAPHQL'
mutation {
  updateProject(input: { id: "project-id" }) { project { id } }
}
GRAPHQL;

    protected ApiRateLimitRequestResolver $resolver;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = $this->createRequestResolver();
    }

    /**
     * @param array<string, GraphQlPayloadValue> $variables
     * @param array<string, GraphQlPayloadValue> $extraPayload
     */
    protected function createGraphQlRequest(
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
     * @param array<string, GraphQlPayloadValue> $operations
     */
    protected function createGraphQlOperationsRequest(
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

    protected function passkeySigninOptionsInputQuery(): string
    {
        return <<<'GRAPHQL'
mutation Passkey($input: passkeySignInOptionsUserInput!) {
  passkeySignInOptionsUser(input: $input) {
    user { challengeId }
  }
}
GRAPHQL;
    }

    protected function passkeySigninCompleteQuery(): string
    {
        return sprintf(
            <<<'GRAPHQL'
mutation Passkey {
  passkeySignInCompleteUser(input: { challengeId: "%s" }) {
    user { accessToken }
  }
}
GRAPHQL,
            $this->faker->uuid()
        );
    }

    protected function decoyAndRealOperationQuery(): string
    {
        return sprintf(
            <<<'GRAPHQL'
mutation Decoy {
  passkeySignInOptionsUser(input: { email: "%s" }) { user { challengeId } }
}
mutation Real {
  updateProject(input: { id: "project-id" }) { project { id } }
}
GRAPHQL,
            $this->faker->email()
        );
    }

    /**
     * @param array<string, GraphQlPayloadValue> $operations
     */
    protected function createNamedFormOperationsRequest(
        string $clientIp,
        array $operations = []
    ): Request {
        return $this->createGraphQlOperationsRequest(
            ['operationName' => 'Passkey'] + $operations,
            $clientIp,
            self::FORM_CONTENT_TYPE
        );
    }

    protected function createMalformedOperationsRequest(
        string $clientIp,
        string $contentType
    ): Request {
        return Request::create(
            self::GRAPHQL_PATH,
            'POST',
            ['operations' => '{'],
            [],
            [],
            ['CONTENT_TYPE' => $contentType, 'REMOTE_ADDR' => $clientIp]
        );
    }

    /**
     * @param array<string, GraphQlPayloadValue> $operations
     */
    protected function createOperationsParameterRequest(
        array $operations,
        string $clientIp,
        string $contentType
    ): Request {
        return Request::create(
            self::GRAPHQL_PATH,
            'POST',
            ['operations' => $this->encodeVariables($operations)],
            [],
            [],
            ['CONTENT_TYPE' => $contentType, 'REMOTE_ADDR' => $clientIp]
        );
    }

    protected function createInvalidJsonRequest(string $clientIp): Request
    {
        return Request::create(
            self::GRAPHQL_PATH,
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'REMOTE_ADDR' => $clientIp],
            '{'
        );
    }

    protected function createJsonStringVariablesRequest(string $clientIp, string $email): Request
    {
        $variables = ['unused' => ['nested' => true], 'input' => ['email' => $email]];

        return Request::create(
            self::GRAPHQL_PATH,
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'REMOTE_ADDR' => $clientIp],
            $this->encodeVariables([
                'query' => $this->passkeySigninOptionsInputQuery(),
                'variables' => $this->encodeVariables($variables),
            ])
        );
    }

    protected function createPasskeyCompleteRawGraphQlRequest(string $clientIp): Request
    {
        return $this->createRawGraphQlRequest($this->passkeySigninCompleteQuery(), $clientIp);
    }

    protected function createProjectUpdateWithQueryStringPasskeyRequest(
        string $clientIp,
        string $email,
        string $contentType = 'application/graphql'
    ): Request {
        $request = $this->createRawGraphQlRequest(
            self::PROJECT_UPDATE_MUTATION,
            $clientIp,
            $contentType
        );
        $request->query->set('query', sprintf(self::PASSKEY_SIGNIN_OPTIONS_MUTATION, $email));

        return $request;
    }

    protected function createRawGraphQlRequest(
        string $body,
        string $clientIp,
        string $contentType = 'application/graphql'
    ): Request {
        return Request::create(
            self::GRAPHQL_PATH,
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => $contentType, 'REMOTE_ADDR' => $clientIp],
            $body
        );
    }

    /**
     * @param array<string, GraphQlPayloadValue> $payload
     */
    protected function encodeVariables(array $payload): string
    {
        return json_encode($payload, JSON_THROW_ON_ERROR);
    }

    protected function createSelectedPasskeySigninQueryWithStringTokens(string $decoyEmail): string
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
     *     variables?: array<string, GraphQlPayloadValue>
     * } $options
     */
    protected function assertGraphQlLimiters(
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
    protected function registrationLimiters(string $clientIp): array
    {
        return [
            ['name' => 'registration', 'key' => 'ip:' . $clientIp],
        ];
    }

    /**
     * @return list<array{name: string, key: string}>
     */
    protected function signInLimiters(string $clientIp, string $email): array
    {
        return [
            ...$this->signInIpLimiters($clientIp),
            ['name' => 'signin_email', 'key' => 'email:' . strtolower($email)],
        ];
    }

    /**
     * @return list<array{name: string, key: string}>
     */
    protected function signInIpLimiters(string $clientIp): array
    {
        return [
            ['name' => 'signin_ip', 'key' => 'ip:' . $clientIp],
        ];
    }
}
