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
        $this->assertSame(
            $flowBindingHash,
            $payload->flowBindingHash
        );
        $this->assertSame($redirectUri, $payload->redirectUri);
        $this->assertSame($createdAt, $payload->createdAt);
    }

    public function testEqualsReturnsTrueForIdenticalPayloads(): void
    {
        $payload1 = $this->createPayload();
        $payload2 = new OAuthStatePayload(
            $payload1->provider,
            $payload1->codeVerifier,
            $payload1->flowBindingHash,
            $payload1->redirectUri,
            $payload1->createdAt,
        );

        $this->assertTrue($payload1->equals($payload2));
    }

    public function testEqualsReturnsFalseForDifferentProvider(): void
    {
        $base = $this->createPayload();

        $other = new OAuthStatePayload(
            $this->faker->word(),
            $base->codeVerifier,
            $base->flowBindingHash,
            $base->redirectUri,
            $base->createdAt,
        );

        $this->assertFalse($base->equals($other));
    }

    public function testEqualsReturnsFalseForDifferentCodeVerifier(): void
    {
        $base = $this->createPayload();

        $other = new OAuthStatePayload(
            $base->provider,
            $this->faker->sha256(),
            $base->flowBindingHash,
            $base->redirectUri,
            $base->createdAt,
        );

        $this->assertFalse($base->equals($other));
    }

    public function testEqualsReturnsFalseForDifferentFlowBindingHash(): void
    {
        $base = $this->createPayload();

        $other = new OAuthStatePayload(
            $base->provider,
            $base->codeVerifier,
            $this->faker->sha256(),
            $base->redirectUri,
            $base->createdAt,
        );

        $this->assertFalse($base->equals($other));
    }

    public function testEqualsReturnsFalseForDifferentRedirectUri(): void
    {
        $base = $this->createPayload();

        $other = new OAuthStatePayload(
            $base->provider,
            $base->codeVerifier,
            $base->flowBindingHash,
            $this->faker->url(),
            $base->createdAt,
        );

        $this->assertFalse($base->equals($other));
    }

    public function testEqualsReturnsFalseForDifferentCreatedAt(): void
    {
        $base = $this->createPayload();

        $other = new OAuthStatePayload(
            $base->provider,
            $base->codeVerifier,
            $base->flowBindingHash,
            $base->redirectUri,
            new DateTimeImmutable('2020-01-01'),
        );

        $this->assertFalse($base->equals($other));
    }

    private function createPayload(): OAuthStatePayload
    {
        return new OAuthStatePayload(
            provider: $this->faker->word(),
            codeVerifier: $this->faker->sha256(),
            flowBindingHash: $this->faker->sha256(),
            redirectUri: $this->faker->url(),
            createdAt: new DateTimeImmutable(),
        );
    }
}
