<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\DTO;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\PasskeyConfiguration;
use DateTimeImmutable;
use InvalidArgumentException;

final class PasskeyConfigurationTest extends UnitTestCase
{
    public function testAllowedOriginsAreTrimmedNormalizedAndEmptyEntriesAreIgnored(): void
    {
        $rpId = $this->faker->domainName();
        $firstOriginHost = $this->faker->domainName();
        $firstOrigin = sprintf('HTTPS://%s:8443', strtoupper($firstOriginHost));
        $secondOriginHost = $this->faker->domainName();
        $secondOrigin = sprintf('https://%s', $secondOriginHost);
        $timeoutSeconds = $this->faker->numberBetween(60, 600);
        $configuration = new PasskeyConfiguration(
            $rpId,
            $this->faker->company(),
            sprintf(' %s, ,%s ', $firstOrigin, $secondOrigin),
            $timeoutSeconds,
            $this->faker->numberBetween(60, 600)
        );

        self::assertSame([
            sprintf('https://%s:8443', strtolower($firstOriginHost)),
            $secondOrigin,
        ], $configuration->getAllowedOrigins());
        self::assertSame($rpId, $configuration->getRpId());
        self::assertSame($timeoutSeconds * 1000, $configuration->getTimeoutMilliseconds());
    }

    public function testRelyingPartyValuesAreTrimmedAndNormalized(): void
    {
        $rpId = $this->faker->domainName();
        $rpName = $this->faker->company();
        $configuration = new PasskeyConfiguration(
            sprintf(' %s ', strtoupper($rpId)),
            sprintf(' %s ', $rpName),
            sprintf('https://%s', $this->faker->domainName()),
            $this->faker->numberBetween(60, 600),
            $this->faker->numberBetween(60, 600)
        );

        self::assertSame($rpId, $configuration->getRpId());
        self::assertSame($rpName, $configuration->getRpName());
    }

    public function testChallengeExpiryUsesConfiguredTtl(): void
    {
        $ttlSeconds = $this->faker->numberBetween(60, 600);
        $configuration = new PasskeyConfiguration(
            $this->faker->domainName(),
            $this->faker->company(),
            sprintf('https://%s', $this->faker->domainName()),
            $this->faker->numberBetween(60, 600),
            $ttlSeconds
        );
        $createdAt = new DateTimeImmutable('@' . $this->faker->unixTime());

        self::assertEquals(
            $createdAt->modify(sprintf('+%d seconds', $ttlSeconds)),
            $configuration->challengeExpiresAt($createdAt)
        );
    }

    public function testEmptyRpIdIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Passkey relying party ID must be configured.');

        new PasskeyConfiguration(
            '',
            $this->faker->company(),
            sprintf('https://%s', $this->faker->domainName()),
            $this->faker->numberBetween(60, 600),
            $this->faker->numberBetween(60, 600)
        );
    }

    public function testBlankRpIdIsRejectedAfterTrim(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Passkey relying party ID must be configured.');

        new PasskeyConfiguration(
            '  ',
            $this->faker->company(),
            sprintf('https://%s', $this->faker->domainName()),
            $this->faker->numberBetween(60, 600),
            $this->faker->numberBetween(60, 600)
        );
    }

    public function testEmptyRpNameIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Passkey relying party name must be configured.');

        new PasskeyConfiguration(
            $this->faker->domainName(),
            '',
            sprintf('https://%s', $this->faker->domainName()),
            $this->faker->numberBetween(60, 600),
            $this->faker->numberBetween(60, 600)
        );
    }

    public function testBlankRpNameIsRejectedAfterTrim(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Passkey relying party name must be configured.');

        new PasskeyConfiguration(
            $this->faker->domainName(),
            '  ',
            sprintf('https://%s', $this->faker->domainName()),
            $this->faker->numberBetween(60, 600),
            $this->faker->numberBetween(60, 600)
        );
    }

    public function testNonPositiveTimeoutIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Passkey timeout must be greater than zero.');

        new PasskeyConfiguration(
            $this->faker->domainName(),
            $this->faker->company(),
            sprintf('https://%s', $this->faker->domainName()),
            0,
            $this->faker->numberBetween(60, 600)
        );
    }

    public function testTooLargeTimeoutIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Passkey timeout must not exceed 600 seconds.');

        new PasskeyConfiguration(
            $this->faker->domainName(),
            $this->faker->company(),
            sprintf('https://%s', $this->faker->domainName()),
            601,
            $this->faker->numberBetween(60, 600)
        );
    }

    public function testNonPositiveChallengeTtlIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Passkey challenge TTL must be greater than zero.');

        new PasskeyConfiguration(
            $this->faker->domainName(),
            $this->faker->company(),
            sprintf('https://%s', $this->faker->domainName()),
            $this->faker->numberBetween(60, 600),
            0
        );
    }

    public function testTooLargeChallengeTtlIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Passkey challenge TTL must not exceed 600 seconds.');

        new PasskeyConfiguration(
            $this->faker->domainName(),
            $this->faker->company(),
            sprintf('https://%s', $this->faker->domainName()),
            $this->faker->numberBetween(60, 600),
            601
        );
    }

    public function testEmptyAllowedOriginsAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one passkey allowed origin must be configured.');

        new PasskeyConfiguration(
            $this->faker->domainName(),
            $this->faker->company(),
            ' , ',
            $this->faker->numberBetween(60, 600),
            $this->faker->numberBetween(60, 600)
        );
    }

    public function testWildcardAllowedOriginIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Passkey allowed origins cannot contain wildcards.');

        new PasskeyConfiguration(
            $this->faker->domainName(),
            $this->faker->company(),
            'https://*.example.com',
            $this->faker->numberBetween(60, 600),
            $this->faker->numberBetween(60, 600)
        );
    }

    public function testHttpAllowedOriginIsRejectedForNonLocalhost(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'HTTP passkey allowed origins are only allowed for localhost.'
        );

        new PasskeyConfiguration(
            $this->faker->domainName(),
            $this->faker->company(),
            sprintf('http://%s', $this->faker->domainName()),
            $this->faker->numberBetween(60, 600),
            $this->faker->numberBetween(60, 600)
        );
    }

    public function testLocalhostHttpOriginIsAllowedOutsideProduction(): void
    {
        $configuration = new PasskeyConfiguration(
            'localhost',
            $this->faker->company(),
            'http://localhost:8080',
            $this->faker->numberBetween(60, 600),
            $this->faker->numberBetween(60, 600)
        );

        self::assertSame(['http://localhost:8080'], $configuration->getAllowedOrigins());
    }

    public function testIpv6LocalhostHttpOriginIsAllowedOutsideProduction(): void
    {
        $configuration = new PasskeyConfiguration(
            'localhost',
            $this->faker->company(),
            'http://[::1]:8080',
            $this->faker->numberBetween(60, 600),
            $this->faker->numberBetween(60, 600)
        );

        self::assertSame(['http://[::1]:8080'], $configuration->getAllowedOrigins());
    }

    public function testIpHttpsOriginIsAllowedOutsideProduction(): void
    {
        $origin = sprintf(
            'https://192.0.2.%d',
            $this->faker->numberBetween(1, 254)
        );
        $configuration = new PasskeyConfiguration(
            'localhost',
            $this->faker->company(),
            $origin,
            $this->faker->numberBetween(60, 600),
            $this->faker->numberBetween(60, 600)
        );

        self::assertSame([$origin], $configuration->getAllowedOrigins());
    }

    public function testIpRpIdIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Passkey relying party ID must be a host name.');

        new PasskeyConfiguration(
            '127.0.0.1',
            $this->faker->company(),
            'https://127.0.0.1',
            $this->faker->numberBetween(60, 600),
            $this->faker->numberBetween(60, 600)
        );
    }

    public function testRpIdWithSchemeIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Passkey relying party ID must be a host name.');

        new PasskeyConfiguration(
            'https://example.com',
            $this->faker->company(),
            'https://example.com',
            $this->faker->numberBetween(60, 600),
            $this->faker->numberBetween(60, 600)
        );
    }

    public function testRpIdWithPortIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Passkey relying party ID must be a host name.');

        new PasskeyConfiguration(
            'example.com:8443',
            $this->faker->company(),
            'https://example.com:8443',
            $this->faker->numberBetween(60, 600),
            $this->faker->numberBetween(60, 600)
        );
    }

    public function testMalformedRpIdIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Passkey relying party ID must be a host name.');

        new PasskeyConfiguration(
            'example invalid',
            $this->faker->company(),
            'https://example.com',
            $this->faker->numberBetween(60, 600),
            $this->faker->numberBetween(60, 600)
        );
    }
}
