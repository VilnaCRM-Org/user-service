<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use Behat\Behat\Context\Context;
use PHPUnit\Framework\Assert;

/**
 * S5.11 (FR-06): the KMS JWT key is published as a JWK set, and every issued
 * token names a published key in its "kid" header.
 */
final class JwksContext implements Context
{
    public function __construct(private UserOperationsState $state)
    {
    }

    /**
     * @Then I store the access token kid
     */
    public function iStoreTheAccessTokenKid(): void
    {
        $data = $this->responseJson();
        Assert::assertIsString($data['access_token'] ?? null);

        $header = $this->decodeSegment(explode('.', $data['access_token'])[0]);
        Assert::assertSame('RS256', $header['alg'] ?? null);
        Assert::assertIsString($header['kid'] ?? null);

        $this->state->accessTokenKid = $header['kid'];
    }

    /**
     * @Then the JWKS should publish RS256 signing keys
     */
    public function theJwksShouldPublishRs256SigningKeys(): void
    {
        $keys = $this->jwksKeys();

        Assert::assertNotEmpty($keys);
        foreach ($keys as $key) {
            Assert::assertSame('RSA', $key['kty'] ?? null);
            Assert::assertSame('sig', $key['use'] ?? null);
            Assert::assertSame('RS256', $key['alg'] ?? null);
            Assert::assertIsString($key['kid'] ?? null);
            Assert::assertIsString($key['n'] ?? null);
            Assert::assertSame('AQAB', $key['e'] ?? null);
        }
    }

    /**
     * @Then the JWKS should publish the stored access token kid
     */
    public function theJwksShouldPublishTheStoredAccessTokenKid(): void
    {
        $kids = array_column($this->jwksKeys(), 'kid');

        Assert::assertContains($this->state->accessTokenKid, $kids);
    }

    /**
     * @return list<array<string, string>>
     */
    private function jwksKeys(): array
    {
        $keys = $this->responseJson()['keys'] ?? null;
        Assert::assertIsArray($keys);

        return $keys;
    }

    /**
     * @return array<string, array<array-key, array<string, string>>|bool|int|string|null>
     */
    private function responseJson(): array
    {
        $data = json_decode((string) $this->state->response?->getContent(), true);
        Assert::assertIsArray($data);

        return $data;
    }

    /**
     * @return array<string, bool|int|string|null>
     */
    private function decodeSegment(string $segment): array
    {
        $json = base64_decode(strtr($segment, '-_', '+/'), true);
        $decoded = json_decode((string) $json, true);
        Assert::assertIsArray($decoded);

        return $decoded;
    }
}
