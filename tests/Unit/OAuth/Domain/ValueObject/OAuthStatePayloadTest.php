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
}
