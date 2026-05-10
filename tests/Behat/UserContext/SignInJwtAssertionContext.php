<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use Behat\Behat\Context\Context;
use PHPUnit\Framework\Assert;

final class SignInJwtAssertionContext implements Context
{
    public function __construct(
        private UserOperationsState $state,
        private readonly UserContextUserManagementServices $userManagement,
    ) {
    }

    /**
     * @Then the access token should be a valid JWT signed with RS256
     * @Then the new access token should be a valid JWT
     */
    public function theAccessTokenShouldBeAValidJwtSignedWithRs256(): void
    {
        $accessToken = $this->extractAccessToken();
        $parts = explode('.', $accessToken);
        Assert::assertCount(3, $parts);

        $header = $this->decodeBase64Url($parts[0]);
        Assert::assertIsArray($header);
        Assert::assertSame('RS256', $header['alg'] ?? null);
    }

    /**
     * @Then the JWT should contain claim :claim
     * @Then the new access token JWT should contain claim :claim
     */
    public function theJwtShouldContainClaim(string $claim): void
    {
        $payload = $this->decodeAccessTokenPayload();
        Assert::assertArrayHasKey(
            $claim,
            $payload,
            sprintf('JWT is missing claim "%s".', $claim)
        );
    }

    /**
     * @Then the JWT should contain claim :claim with value :value
     * @Then the new access token JWT should contain claim :claim with value :value
     */
    public function theJwtShouldContainClaimWithValue(
        string $claim,
        string $value
    ): void {
        $payload = $this->decodeAccessTokenPayload();
        Assert::assertArrayHasKey($claim, $payload);
        Assert::assertSame($value, $payload[$claim]);
    }

    /**
     * @Then the JWT :claim should be approximately :minutes minutes after :baseClaim
     * @Then the new access token JWT :claim should be approximately :minutes minutes after :baseClaim
     */
    public function theJwtClaimShouldBeApproximatelyMinutesAfter(
        string $claim,
        int $minutes,
        string $baseClaim
    ): void {
        $payload = $this->decodeAccessTokenPayload();
        Assert::assertArrayHasKey($claim, $payload);
        Assert::assertArrayHasKey($baseClaim, $payload);

        $diff = $payload[$claim] - $payload[$baseClaim];
        $expected = $minutes * 60;
        $tolerance = 5;

        Assert::assertEqualsWithDelta(
            $expected,
            $diff,
            $tolerance,
            sprintf(
                '"%s" - "%s" should be ~%d seconds.',
                $claim,
                $baseClaim,
                $expected
            )
        );
    }

    /**
     * @Then the JWT :claim claim should match the user's ID
     */
    public function theJwtClaimShouldMatchTheUsersId(
        string $claim
    ): void {
        $payload = $this->decodeAccessTokenPayload();
        Assert::assertArrayHasKey($claim, $payload);

        $email = $this->resolveSignInEmail();
        $user = $this->userManagement
            ->userRepository->findByEmail($email);
        Assert::assertNotNull($user);

        Assert::assertSame($user->getId(), $payload[$claim]);
    }

    /**
     * @Then the JWT :claim claim should contain :value
     * @Then the new access token JWT :claim should contain :value
     */
    public function theJwtClaimShouldContain(
        string $claim,
        string $value
    ): void {
        $payload = $this->decodeAccessTokenPayload();
        Assert::assertArrayHasKey($claim, $payload);
        Assert::assertIsArray($payload[$claim]);
        Assert::assertContains($value, $payload[$claim]);
    }

    /**
     * @Then I store the JWT :claim claim as :key
     */
    public function iStoreTheJwtClaimAs(
        string $claim,
        string $key
    ): void {
        $payload = $this->decodeAccessTokenPayload();
        Assert::assertArrayHasKey($claim, $payload);

        $this->state->{$key} = $payload[$claim];
    }

    /**
     * @Then I store the original JWT claims
     */
    public function iStoreTheOriginalJwtClaims(): void
    {
        $this->state->originalJwtClaims = $this->decodeTokenPayload(
            $this->resolveOriginalAccessToken()
        );
    }

    /**
     * @Then I store the original JWT :claim claim
     */
    public function iStoreTheOriginalJwtClaim(string $claim): void
    {
        $payload = $this->decodeTokenPayload(
            $this->resolveOriginalAccessToken()
        );
        Assert::assertArrayHasKey($claim, $payload);

        $originalClaims = $this->state->originalJwtClaims;
        if (!is_array($originalClaims)) {
            $originalClaims = [];
        }

        $originalClaims[$claim] = $payload[$claim];
        $this->state->originalJwtClaims = $originalClaims;
    }

    /**
     * @Then the JWT :claim claim should differ from :key
     */
    public function theJwtClaimShouldDifferFrom(
        string $claim,
        string $key
    ): void {
        $payload = $this->decodeAccessTokenPayload();
        Assert::assertArrayHasKey($claim, $payload);

        $storedValue = $this->state->{$key};
        Assert::assertNotNull($storedValue);
        Assert::assertNotSame(
            $storedValue,
            $payload[$claim],
            sprintf(
                'JWT claim "%s" should differ from stored "%s".',
                $claim,
                $key
            )
        );
    }

    /**
     * @Then the new access token JWT :claim should differ from the original
     */
    public function theNewAccessTokenJwtClaimShouldDifferFromTheOriginal(
        string $claim
    ): void {
        $currentPayload = $this->decodeAccessTokenPayload();

        Assert::assertArrayHasKey($claim, $currentPayload);
        Assert::assertNotSame(
            $this->resolveOriginalJwtClaim($claim),
            $currentPayload[$claim]
        );
    }

    /**
     * @Then the new access token JWT :claim should match the original
     */
    public function theNewAccessTokenJwtClaimShouldMatchTheOriginal(
        string $claim
    ): void {
        $currentPayload = $this->decodeAccessTokenPayload();

        Assert::assertArrayHasKey($claim, $currentPayload);
        Assert::assertSame(
            $this->resolveOriginalJwtClaim($claim),
            $currentPayload[$claim]
        );
    }

    private function extractAccessToken(): string
    {
        $data = json_decode(
            (string) $this->state->response?->getContent(),
            true
        );
        Assert::assertIsArray($data);
        Assert::assertArrayHasKey('access_token', $data);

        return $data['access_token'];
    }

    /**
     * @return array<string, array<string>|bool|int|string|null>
     */
    private function decodeAccessTokenPayload(): array
    {
        return $this->decodeTokenPayload($this->extractAccessToken());
    }

    /**
     * @return array<string, array<string>|bool|int|string|null>|null
     */
    private function decodeBase64Url(string $encoded): ?array
    {
        $remainder = strlen($encoded) % 4;
        if ($remainder !== 0) {
            $encoded .= str_repeat('=', 4 - $remainder);
        }

        $raw = base64_decode(
            strtr($encoded, '-_', '+/'),
            true
        );
        if (!is_string($raw) || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @return array<string, array<string>|bool|int|string|null>
     */
    private function decodeTokenPayload(string $accessToken): array
    {
        $parts = explode('.', $accessToken);
        Assert::assertCount(3, $parts);

        $payload = $this->decodeBase64Url($parts[1]);
        Assert::assertIsArray($payload);

        return $payload;
    }

    private function resolveOriginalAccessToken(): string
    {
        $accessToken = $this->state->accessToken;
        if (is_string($accessToken) && $accessToken !== '') {
            return $accessToken;
        }

        return $this->extractAccessToken();
    }

    private function resolveOriginalJwtClaim(
        string $claim
    ): array|bool|int|string|null {
        $originalClaims = $this->state->originalJwtClaims;
        Assert::assertIsArray($originalClaims);
        Assert::assertArrayHasKey($claim, $originalClaims);

        return $originalClaims[$claim];
    }

    private function resolveSignInEmail(): string
    {
        $requestBody = $this->state->requestBody;
        if (
            $requestBody instanceof
            \App\Tests\Behat\UserContext\Input\SignInInput
        ) {
            return $requestBody->email;
        }

        throw new \RuntimeException(
            'Cannot determine sign-in email from request.'
        );
    }
}
