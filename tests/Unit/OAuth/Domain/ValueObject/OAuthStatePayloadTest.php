<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Domain\ValueObject;

use App\OAuth\Domain\ValueObject\OAuthStatePayload;
use App\Tests\Unit\UnitTestCase;
use DateTimeImmutable;

final class OAuthStatePayloadTest extends UnitTestCase
{
    public function testConstructorSetsAllProperties(): void
    {
        $provider = $this->faker->word();
        $codeVerifier = $this->faker->sha256();
        $flowBindingHash = $this->faker->sha256();
        $redirectUri = $this->faker->url();
        $createdAt = new DateTimeImmutable();

        $payload = new OAuthStatePayload(
            provider: $provider,
            codeVerifier: $codeVerifier,
            flowBindingHash: $flowBindingHash,
            redirectUri: $redirectUri,
            createdAt: $createdAt,
        );

        $this->assertSame($provider, $payload->provider);
        $this->assertSame($codeVerifier, $payload->codeVerifier);
        $this->assertSame($flowBindingHash, $payload->flowBindingHash);
        $this->assertSame($redirectUri, $payload->redirectUri);
        $this->assertSame($createdAt, $payload->createdAt);
    }

    public function testEqualsReturnsTrueForIdenticalPayloads(): void
    {
        $provider = $this->faker->word();
        $codeVerifier = $this->faker->sha256();
        $flowBindingHash = $this->faker->sha256();
        $redirectUri = $this->faker->url();
        $createdAt = new DateTimeImmutable();

        $payload1 = new OAuthStatePayload($provider, $codeVerifier, $flowBindingHash, $redirectUri, $createdAt);
        $payload2 = new OAuthStatePayload($provider, $codeVerifier, $flowBindingHash, $redirectUri, $createdAt);

        $this->assertTrue($payload1->equals($payload2));
    }

    public function testEqualsReturnsFalseForDifferentProvider(): void
    {
        $codeVerifier = $this->faker->sha256();
        $flowBindingHash = $this->faker->sha256();
        $redirectUri = $this->faker->url();
        $createdAt = new DateTimeImmutable();

        $payload1 = new OAuthStatePayload($this->faker->word(), $codeVerifier, $flowBindingHash, $redirectUri, $createdAt);
        $payload2 = new OAuthStatePayload($this->faker->word(), $codeVerifier, $flowBindingHash, $redirectUri, $createdAt);

        $this->assertFalse($payload1->equals($payload2));
    }

    public function testEqualsReturnsFalseForDifferentCodeVerifier(): void
    {
        $provider = $this->faker->word();
        $flowBindingHash = $this->faker->sha256();
        $redirectUri = $this->faker->url();
        $createdAt = new DateTimeImmutable();

        $payload1 = new OAuthStatePayload($provider, $this->faker->sha256(), $flowBindingHash, $redirectUri, $createdAt);
        $payload2 = new OAuthStatePayload($provider, $this->faker->sha256(), $flowBindingHash, $redirectUri, $createdAt);

        $this->assertFalse($payload1->equals($payload2));
    }

    public function testEqualsReturnsFalseForDifferentFlowBindingHash(): void
    {
        $provider = $this->faker->word();
        $codeVerifier = $this->faker->sha256();
        $redirectUri = $this->faker->url();
        $createdAt = new DateTimeImmutable();

        $payload1 = new OAuthStatePayload($provider, $codeVerifier, $this->faker->sha256(), $redirectUri, $createdAt);
        $payload2 = new OAuthStatePayload($provider, $codeVerifier, $this->faker->sha256(), $redirectUri, $createdAt);

        $this->assertFalse($payload1->equals($payload2));
    }

    public function testEqualsReturnsFalseForDifferentRedirectUri(): void
    {
        $provider = $this->faker->word();
        $codeVerifier = $this->faker->sha256();
        $flowBindingHash = $this->faker->sha256();
        $createdAt = new DateTimeImmutable();

        $payload1 = new OAuthStatePayload($provider, $codeVerifier, $flowBindingHash, $this->faker->url(), $createdAt);
        $payload2 = new OAuthStatePayload($provider, $codeVerifier, $flowBindingHash, $this->faker->url(), $createdAt);

        $this->assertFalse($payload1->equals($payload2));
    }

    public function testEqualsReturnsFalseForDifferentCreatedAt(): void
    {
        $provider = $this->faker->word();
        $codeVerifier = $this->faker->sha256();
        $flowBindingHash = $this->faker->sha256();
        $redirectUri = $this->faker->url();

        $payload1 = new OAuthStatePayload($provider, $codeVerifier, $flowBindingHash, $redirectUri, new DateTimeImmutable('2025-01-01'));
        $payload2 = new OAuthStatePayload($provider, $codeVerifier, $flowBindingHash, $redirectUri, new DateTimeImmutable('2025-06-01'));

        $this->assertFalse($payload1->equals($payload2));
    }
}
