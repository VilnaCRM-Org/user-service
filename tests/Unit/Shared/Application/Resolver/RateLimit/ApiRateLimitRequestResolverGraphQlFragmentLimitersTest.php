<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\RateLimit;

use App\Shared\Application\Resolver\RateLimit\ApiRateLimitRequestResolver;
use Symfony\Component\HttpFoundation\Request;

final class ApiRateLimitRequestResolverGraphQlFragmentLimitersTest extends RateLimitClientTestCase
{
    private const GRAPHQL_PATH = '/api/graphql';

    private ApiRateLimitRequestResolver $resolver;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = $this->createRequestResolver();
    }

    public function testPasskeySignupOptionsUsesRootFragmentLimiter(): void
    {
        $clientIp = $this->faker->ipv4();
        $email = $this->faker->safeEmail();
        $query = <<<'GRAPHQL'
mutation Real($input: passkeySignUpOptionsUserInput!) {
  ...PasskeySignUp
}
fragment PasskeySignUp on Mutation {
  passkeySignUpOptionsUser(input: $input) {
    user { challengeId }
  }
}
GRAPHQL;

        $this->assertGraphQlLimiters(
            $query,
            $clientIp,
            $this->registrationLimiters($clientIp),
            ['input' => ['email' => $email]]
        );
    }

    public function testPasskeySigninOptionsUsesRootFragmentLimiter(): void
    {
        $clientIp = $this->faker->ipv4();
        $email = $this->faker->email();
        $query = <<<'GRAPHQL'
mutation Real($input: passkeySignInOptionsUserInput!) {
  ...PasskeySignIn
}
fragment PasskeySignIn on Mutation {
  passkeySignInOptionsUser(input: $input) {
    user { challengeId }
  }
}
GRAPHQL;

        $this->assertGraphQlLimiters(
            $query,
            $clientIp,
            $this->signInLimiters($clientIp, $email),
            ['input' => ['email' => $email]]
        );
    }

    public function testSigninEmailLimiterIgnoresEarlierUnrelatedFieldEmail(): void
    {
        $clientIp = $this->faker->ipv4();
        $decoyEmail = $this->faker->email();
        $email = $this->faker->email();
        $query = <<<'GRAPHQL'
mutation Real($e: String!, $decoy: String!) {
  updateProject(input: { email: $decoy }) { project { id } }
  passkeySignInOptionsUser(input: { email: $e }) {
    user { challengeId }
  }
}
GRAPHQL;

        $this->assertGraphQlLimiters(
            $query,
            $clientIp,
            $this->signInLimiters($clientIp, $email),
            ['e' => $email, 'decoy' => $decoyEmail]
        );
    }

    public function testSigninEmailLimiterIncludesEverySigninFieldEmail(): void
    {
        $clientIp = $this->faker->ipv4();
        $firstEmail = $this->faker->email();
        $secondEmail = $this->faker->email();
        $query = <<<'GRAPHQL'
mutation Real($first: String!, $second: String!) {
  signInUser(input: { email: $first }) { user { id } }
  passkeySignInOptionsUser(input: { email: $second }) {
    user { challengeId }
  }
}
GRAPHQL;

        $this->assertGraphQlLimiters(
            $query,
            $clientIp,
            $this->signInLimitersForAttempts($clientIp, 2, $firstEmail, $secondEmail),
            ['first' => $firstEmail, 'second' => $secondEmail]
        );
    }

    public function testSigninLimitersConsumeEveryRepeatedSameEmailAttempt(): void
    {
        $clientIp = $this->faker->ipv4();
        $email = $this->faker->email();
        $query = <<<'GRAPHQL'
mutation Real($email: String!) {
  first: passkeySignInOptionsUser(input: { email: $email }) { user { challengeId } }
  second: passkeySignInOptionsUser(input: { email: $email }) { user { challengeId } }
}
GRAPHQL;

        $this->assertGraphQlLimiters(
            $query,
            $clientIp,
            $this->signInLimitersForAttempts($clientIp, 2, $email, $email),
            ['email' => $email]
        );
    }

    public function testRawGraphQlSigninRequestUsesInlineEmailLimiter(): void
    {
        $clientIp = $this->faker->ipv4();
        $email = $this->faker->email();
        $query = sprintf(
            <<<'GRAPHQL'
mutation {
  passkeySignInOptionsUser(input: { email: "%s" }) {
    user { challengeId }
  }
}
GRAPHQL,
            $email
        );

        $request = $this->createRawGraphQlRequest($query, $clientIp);

        self::assertSame(
            $this->signInLimiters($clientIp, $email),
            $this->resolver->resolveEndpointLimiters($request)
        );
    }

    /**
     * @param list<array{name: string, key: string}> $expectedLimiters
     * @param array<string, array<string, string>|string> $variables
     */
    private function assertGraphQlLimiters(
        string $query,
        string $clientIp,
        array $expectedLimiters,
        array $variables
    ): void {
        $request = Request::create(
            self::GRAPHQL_PATH,
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'REMOTE_ADDR' => $clientIp],
            json_encode(['query' => $query, 'variables' => $variables], JSON_THROW_ON_ERROR)
        );

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
        return $this->signInLimitersForAttempts($clientIp, 1, $email);
    }

    /**
     * @return list<array{name: string, key: string}>
     */
    private function signInLimitersForAttempts(
        string $clientIp,
        int $attempts,
        string ...$emails
    ): array {
        $limiters = $this->signInIpLimiters($clientIp, $attempts);
        foreach ($emails as $email) {
            $limiters[] = ['name' => 'signin_email', 'key' => 'email:' . strtolower($email)];
        }

        return $limiters;
    }

    private function createRawGraphQlRequest(string $query, string $clientIp): Request
    {
        return Request::create(
            self::GRAPHQL_PATH,
            'POST',
            [],
            [],
            [],
            ['REMOTE_ADDR' => $clientIp],
            $query
        );
    }

    /**
     * @return list<array{name: string, key: string}>
     */
    private function signInIpLimiters(string $clientIp, int $attempts): array
    {
        $limiters = [];
        for ($index = 0; $index < $attempts; ++$index) {
            $limiters[] = ['name' => 'signin_ip', 'key' => 'ip:' . $clientIp];
        }

        return $limiters;
    }
}
