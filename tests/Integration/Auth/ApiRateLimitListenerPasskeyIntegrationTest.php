<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth;

use Symfony\Component\HttpFoundation\Request;

final class ApiRateLimitListenerPasskeyIntegrationTest extends ApiRateLimitListenerTestCase
{
    public function testGraphQlPasskeySignupUsesRegistrationLimiter(): void
    {
        $email = $this->faker->safeEmail();
        $initials = strtoupper($this->faker->lexify('??'));
        $this->exhaustLimiter(
            'registration',
            'ip:127.0.0.1',
            $this->resolveLimit('REGISTRATION_RATE_LIMIT_MAX_REQUESTS', 5)
        );
        $content = json_encode([
            'query' => sprintf(<<<'GRAPHQL'
mutation {
  passkeySignUpOptionsUser(input: { email: "%s", initials: "%s" }) {
    user { challengeId }
  }
}
GRAPHQL, $email, $initials),
        ], JSON_THROW_ON_ERROR);

        $response = $this->handleJsonRequest('/api/graphql', Request::METHOD_POST, $content);

        $this->assertRateLimitResponse($response);
    }

    public function testRestPasskeyRegistrationOptionsUsesRegistrationLimiter(): void
    {
        $this->exhaustLimiter(
            'registration',
            'ip:127.0.0.1',
            $this->resolveLimit('REGISTRATION_RATE_LIMIT_MAX_REQUESTS', 5)
        );
        $response = $this->handleJsonRequest(
            '/api/passkeys/register/options',
            Request::METHOD_POST,
            json_encode([], JSON_THROW_ON_ERROR),
            $this->createAuthenticatedHeader()
        );

        $this->assertRateLimitResponse($response);
    }

    public function testGraphQlPasskeyRegistrationUsesRegistrationLimiter(): void
    {
        $this->exhaustLimiter(
            'registration',
            'ip:127.0.0.1',
            $this->resolveLimit('REGISTRATION_RATE_LIMIT_MAX_REQUESTS', 5)
        );
        $response = $this->handleJsonRequest(
            '/api/graphql',
            Request::METHOD_POST,
            $this->createPasskeyRegistrationOptionsGraphQlPayload(),
            $this->createAuthenticatedHeader()
        );

        $this->assertRateLimitResponse($response);
    }

    public function testGraphQlPasskeySigninUsesSignInEmailLimiter(): void
    {
        $email = $this->faker->safeEmail();
        $this->exhaustLimiter(
            'signin_email',
            sprintf('email:%s', $email),
            $this->resolveLimit('SIGNIN_EMAIL_RATE_LIMIT_MAX_REQUESTS', 5)
        );
        $content = json_encode([
            'query' => sprintf(<<<'GRAPHQL'
mutation {
  passkeySignInOptionsUser(input: { email: "%s" }) {
    user { challengeId }
  }
}
GRAPHQL, $email),
        ], JSON_THROW_ON_ERROR);

        $response = $this->handleJsonRequest('/api/graphql', Request::METHOD_POST, $content);

        $this->assertRateLimitResponse($response);
    }

    public function testGraphQlPasskeySigninUsesSignInEmailLimiterWithAliasedVariable(): void
    {
        $email = $this->faker->safeEmail();
        $this->exhaustLimiter(
            'signin_email',
            sprintf('email:%s', $email),
            $this->resolveLimit('SIGNIN_EMAIL_RATE_LIMIT_MAX_REQUESTS', 5)
        );
        $content = $this->createSelectedPasskeySigninPayload($email);

        $response = $this->handleJsonRequest('/api/graphql', Request::METHOD_POST, $content);

        $this->assertRateLimitResponse($response);
    }

    public function testGraphQlPasskeySigninMultipartOperationsUsesSignInEmailLimiter(): void
    {
        $email = $this->faker->safeEmail();
        $this->exhaustLimiter(
            'signin_email',
            sprintf('email:%s', $email),
            $this->resolveLimit('SIGNIN_EMAIL_RATE_LIMIT_MAX_REQUESTS', 5)
        );
        $content = $this->createMultipartPasskeySigninPayload($email);

        $response = $this->handleFormRequest('/api/graphql', Request::METHOD_POST, $content);

        $this->assertRateLimitResponse($response);
    }

    public function testGraphQlPasskeySigninFormOperationsUsesQueryStringSignInEmailLimiter(): void
    {
        $email = $this->faker->safeEmail();
        $this->exhaustLimiter(
            'signin_email',
            sprintf('email:%s', $email),
            $this->resolveLimit('SIGNIN_EMAIL_RATE_LIMIT_MAX_REQUESTS', 5)
        );
        $uri = '/api/graphql?' . http_build_query([
            'query' => $this->passkeySigninOptionsInputQuery(),
            'variables' => json_encode(['input' => ['email' => $email]], JSON_THROW_ON_ERROR),
        ]);

        $response = $this->handleFormRequest(
            $uri,
            Request::METHOD_POST,
            ['operations' => json_encode(['operationName' => 'Passkey'], JSON_THROW_ON_ERROR)]
        );

        $this->assertRateLimitResponse($response);
    }

    public function testRestPasskeySigninOptionsIgnoresNestedCredentialEmailLimiter(): void
    {
        $email = $this->faker->safeEmail();
        $this->exhaustLimiter(
            'signin_email',
            sprintf('email:%s', strtolower(trim($email))),
            $this->resolveLimit('SIGNIN_EMAIL_RATE_LIMIT_MAX_REQUESTS', 5)
        );
        $content = json_encode([
            'credential' => ['email' => $email],
        ], JSON_THROW_ON_ERROR);

        $response = $this->handleJsonRequest(
            '/api/passkeys/signin/options',
            Request::METHOD_POST,
            $content
        );

        $this->assertNotSame(429, $response->getStatusCode());
    }

    public function testGraphQlPasskeySigninOptionsIgnoresNestedCredentialEmailLimiter(): void
    {
        $email = $this->faker->safeEmail();
        $this->exhaustLimiter(
            'signin_email',
            sprintf('email:%s', strtolower(trim($email))),
            $this->resolveLimit('SIGNIN_EMAIL_RATE_LIMIT_MAX_REQUESTS', 5)
        );
        $content = json_encode([
            'query' => sprintf(<<<'GRAPHQL'
mutation {
  passkeySignInOptionsUser(input: {
    credential: { email: "%s" }
  }) {
    user { challengeId }
  }
}
GRAPHQL, $email),
        ], JSON_THROW_ON_ERROR);

        $response = $this->handleJsonRequest('/api/graphql', Request::METHOD_POST, $content);

        $this->assertNotSame(429, $response->getStatusCode());
    }

    public function testRestPasskeySigninCompleteIgnoresCredentialEmailLimiter(): void
    {
        $email = $this->faker->safeEmail();
        $this->exhaustLimiter(
            'signin_email',
            sprintf('email:%s', strtolower(trim($email))),
            $this->resolveLimit('SIGNIN_EMAIL_RATE_LIMIT_MAX_REQUESTS', 5)
        );
        $content = json_encode([
            'challengeId' => $this->faker->uuid(),
            'credential' => ['email' => $email],
        ], JSON_THROW_ON_ERROR);

        $response = $this->handleJsonRequest(
            '/api/passkeys/signin/complete',
            Request::METHOD_POST,
            $content
        );

        $this->assertNotSame(429, $response->getStatusCode());
    }

    public function testGraphQlPasskeySigninCompleteIgnoresCredentialEmailLimiter(): void
    {
        $email = $this->faker->safeEmail();
        $this->exhaustLimiter(
            'signin_email',
            sprintf('email:%s', strtolower(trim($email))),
            $this->resolveLimit('SIGNIN_EMAIL_RATE_LIMIT_MAX_REQUESTS', 5)
        );
        $content = $this->createPasskeySigninCompleteGraphQlPayload($email);

        $response = $this->handleJsonRequest('/api/graphql', Request::METHOD_POST, $content);

        $this->assertNotSame(429, $response->getStatusCode());
    }

    private function passkeySigninOptionsInputQuery(): string
    {
        return <<<'GRAPHQL'
mutation Passkey($input: passkeySignInOptionsUserInput!) {
  passkeySignInOptionsUser(input: $input) { user { challengeId } }
}
GRAPHQL;
    }
}
