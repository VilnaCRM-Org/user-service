<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\DTO;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\PasskeyConfiguration;
use DateTimeImmutable;
use InvalidArgumentException;

final class PasskeyConfigurationTest extends UnitTestCase
{
    public function testAllowedOriginsAreTrimmedAndEmptyEntriesAreIgnored(): void
    {
        $rpId = $this->faker->domainName();
        $rpName = $this->faker->company();
        $firstOrigin = sprintf('https://%s', $this->faker->domainName());
        $secondOrigin = sprintf('https://%s', $this->faker->domainName());
        $timeoutSeconds = $this->faker->numberBetween(60, 600);
        $configuration = new PasskeyConfiguration(
            $rpId,
            $rpName,
            sprintf(' %s, ,%s ', $firstOrigin, $secondOrigin),
            $timeoutSeconds,
            $this->faker->numberBetween(60, 600)
        );

        self::assertSame([
            $firstOrigin,
            $secondOrigin,
        ], $configuration->getAllowedOrigins());
        self::assertSame($rpId, $configuration->getRpId());
        self::assertSame($rpName, $configuration->getRpName());
        self::assertSame($timeoutSeconds * 1000, $configuration->getTimeoutMilliseconds());
    }

    public function testRelyingPartyValuesAreTrimmed(): void
    {
        $rpId = $this->faker->domainName();
        $rpName = $this->faker->company();
        $configuration = new PasskeyConfiguration(
            sprintf(' %s ', $rpId),
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

    public function testEmptyAllowedOriginsAreRejected(): void
    {
        $configuration = new PasskeyConfiguration(
            $this->faker->domainName(),
            $this->faker->company(),
            ' , ',
            $this->faker->numberBetween(60, 600),
            $this->faker->numberBetween(60, 600)
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one passkey allowed origin must be configured.');

        $configuration->getAllowedOrigins();
    }
}
