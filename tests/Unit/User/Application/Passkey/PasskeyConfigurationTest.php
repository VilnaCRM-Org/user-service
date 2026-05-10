<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Passkey;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Passkey\PasskeyConfiguration;
use DateTimeImmutable;
use InvalidArgumentException;

final class PasskeyConfigurationTest extends UnitTestCase
{
    public function testAllowedOriginsAreTrimmedAndEmptyEntriesAreIgnored(): void
    {
        $configuration = new PasskeyConfiguration(
            'localhost',
            'VilnaCRM',
            ' https://app.example.com, ,https://admin.example.com ',
            300,
            120
        );

        self::assertSame([
            'https://app.example.com',
            'https://admin.example.com',
        ], $configuration->getAllowedOrigins());
        self::assertSame('localhost', $configuration->getRpId());
        self::assertSame('VilnaCRM', $configuration->getRpName());
        self::assertSame(300000, $configuration->getTimeoutMilliseconds());
    }

    public function testRelyingPartyValuesAreTrimmed(): void
    {
        $configuration = new PasskeyConfiguration(
            ' localhost ',
            ' VilnaCRM ',
            'https://app.example.com',
            300,
            120
        );

        self::assertSame('localhost', $configuration->getRpId());
        self::assertSame('VilnaCRM', $configuration->getRpName());
    }

    public function testChallengeExpiryUsesConfiguredTtl(): void
    {
        $configuration = new PasskeyConfiguration(
            'localhost',
            'VilnaCRM',
            'https://app.example.com',
            300,
            120
        );
        $createdAt = new DateTimeImmutable('2026-05-10 12:00:00');

        self::assertEquals(
            new DateTimeImmutable('2026-05-10 12:02:00'),
            $configuration->challengeExpiresAt($createdAt)
        );
    }

    public function testEmptyRpIdIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Passkey relying party ID must be configured.');

        new PasskeyConfiguration('', 'VilnaCRM', 'https://app.example.com', 300, 120);
    }

    public function testBlankRpIdIsRejectedAfterTrim(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Passkey relying party ID must be configured.');

        new PasskeyConfiguration('  ', 'VilnaCRM', 'https://app.example.com', 300, 120);
    }

    public function testEmptyRpNameIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Passkey relying party name must be configured.');

        new PasskeyConfiguration('localhost', '', 'https://app.example.com', 300, 120);
    }

    public function testBlankRpNameIsRejectedAfterTrim(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Passkey relying party name must be configured.');

        new PasskeyConfiguration('localhost', '  ', 'https://app.example.com', 300, 120);
    }

    public function testNonPositiveTimeoutIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Passkey timeout must be greater than zero.');

        new PasskeyConfiguration('localhost', 'VilnaCRM', 'https://app.example.com', 0, 120);
    }

    public function testNonPositiveChallengeTtlIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Passkey challenge TTL must be greater than zero.');

        new PasskeyConfiguration('localhost', 'VilnaCRM', 'https://app.example.com', 300, 0);
    }

    public function testEmptyAllowedOriginsAreRejected(): void
    {
        $configuration = new PasskeyConfiguration(
            'localhost',
            'VilnaCRM',
            ' , ',
            300,
            120
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one passkey allowed origin must be configured.');

        $configuration->getAllowedOrigins();
    }
}
