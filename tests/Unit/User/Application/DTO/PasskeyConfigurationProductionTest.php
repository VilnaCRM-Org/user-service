<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\DTO;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\PasskeyConfiguration;
use InvalidArgumentException;

use function sprintf;
use function sys_get_temp_dir;

final class PasskeyConfigurationProductionTest extends UnitTestCase
{
    public function testProductionAllowsHttpsOriginForRpIdSubdomain(): void
    {
        $rpId = $this->faker->domainName();
        $origin = sprintf('https://app.%s', $rpId);
        $configuration = new PasskeyConfiguration(
            $rpId,
            $this->faker->company(),
            $origin,
            $this->faker->numberBetween(60, 600),
            $this->faker->numberBetween(60, 600),
            'prod'
        );

        self::assertSame([$origin], $configuration->getAllowedOrigins());
    }

    public function testProductionRejectsHttpOrigin(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Production passkey allowed origins must use HTTPS.');

        new PasskeyConfiguration(
            'example.com',
            $this->faker->company(),
            'http://example.com',
            $this->faker->numberBetween(60, 600),
            $this->faker->numberBetween(60, 600),
            'prod'
        );
    }

    public function testProductionRejectsOriginOutsideRpId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Production passkey allowed origin host must match the relying party ID or subdomain.'
        );

        new PasskeyConfiguration(
            'example.com',
            $this->faker->company(),
            'https://example.net',
            $this->faker->numberBetween(60, 600),
            $this->faker->numberBetween(60, 600),
            'prod'
        );
    }

    public function testProductionRejectsLocalhostRpId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Production passkey relying party ID must be a public host name.'
        );

        new PasskeyConfiguration(
            'localhost',
            $this->faker->company(),
            'https://localhost',
            $this->faker->numberBetween(60, 600),
            $this->faker->numberBetween(60, 600),
            'prod'
        );
    }

    public function testProductionRejectsSingleLabelRpId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Production passkey relying party ID must be a public host name.'
        );

        new PasskeyConfiguration(
            'intranet',
            $this->faker->company(),
            'https://intranet',
            $this->faker->numberBetween(60, 600),
            $this->faker->numberBetween(60, 600),
            'prod'
        );
    }

    public function testProductionRejectsKnownPublicSuffixRpId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Production passkey relying party ID must be a public host name.'
        );

        new PasskeyConfiguration(
            'co.uk',
            $this->faker->company(),
            'https://example.co.uk',
            $this->faker->numberBetween(60, 600),
            $this->faker->numberBetween(60, 600),
            'prod'
        );
    }

    public function testProductionRejectsPublicSuffixRpIdWithTrailingDot(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Passkey relying party ID must be a host name.');

        new PasskeyConfiguration(
            'co.uk.',
            $this->faker->company(),
            'https://co.uk.',
            $this->faker->numberBetween(60, 600),
            $this->faker->numberBetween(60, 600),
            'prod'
        );
    }

    public function testProductionRejectsPublicSuffixRpIdFromPublicSuffixList(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Production passkey relying party ID must be a public host name.'
        );

        new PasskeyConfiguration(
            'ac.ae',
            $this->faker->company(),
            'https://example.ac.ae',
            $this->faker->numberBetween(60, 600),
            $this->faker->numberBetween(60, 600),
            'prod'
        );
    }

    public function testProductionRejectsIdnPublicSuffixRpIdInPunycodeForm(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Production passkey relying party ID must be a public host name.'
        );

        new PasskeyConfiguration(
            'xn--55qx5d.cn',
            $this->faker->company(),
            'https://example.xn--55qx5d.cn',
            $this->faker->numberBetween(60, 600),
            $this->faker->numberBetween(60, 600),
            'prod'
        );
    }

    public function testProductionRejectsHongKongIdnPublicSuffixRpIdInPunycodeForm(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Production passkey relying party ID must be a public host name.'
        );

        new PasskeyConfiguration(
            'xn--55qx5d.hk',
            $this->faker->company(),
            'https://example.xn--55qx5d.hk',
            $this->faker->numberBetween(60, 600),
            $this->faker->numberBetween(60, 600),
            'prod'
        );
    }

    public function testProductionRejectsWildcardPublicSuffixRpIdFromPublicSuffixList(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Production passkey relying party ID must be a public host name.'
        );

        new PasskeyConfiguration(
            'example.ck',
            $this->faker->company(),
            'https://app.example.ck',
            $this->faker->numberBetween(60, 600),
            $this->faker->numberBetween(60, 600),
            'prod'
        );
    }

    public function testProductionAllowsPublicSuffixExceptionRpId(): void
    {
        $configuration = new PasskeyConfiguration(
            'www.ck',
            $this->faker->company(),
            'https://login.www.ck',
            $this->faker->numberBetween(60, 600),
            $this->faker->numberBetween(60, 600),
            'prod'
        );

        self::assertSame('www.ck', $configuration->getRpId());
        self::assertSame(['https://login.www.ck'], $configuration->getAllowedOrigins());
    }

    public function testProductionRejectsRpIdWhenPublicSuffixListCannotBeRead(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Production passkey relying party ID must be a public host name.'
        );

        new PasskeyConfiguration(
            'example.com',
            $this->faker->company(),
            'https://example.com',
            $this->faker->numberBetween(60, 600),
            $this->faker->numberBetween(60, 600),
            'prod',
            sprintf('%s/%s.dat', sys_get_temp_dir(), $this->faker->uuid())
        );
    }

    public function testProductionAllowsRegistrableDomainBelowKnownPublicSuffix(): void
    {
        $configuration = new PasskeyConfiguration(
            'example.co.uk',
            $this->faker->company(),
            'https://app.example.co.uk',
            $this->faker->numberBetween(60, 600),
            $this->faker->numberBetween(60, 600),
            'prod'
        );

        self::assertSame('example.co.uk', $configuration->getRpId());
        self::assertSame(['https://app.example.co.uk'], $configuration->getAllowedOrigins());
    }

    public function testProductionAllowsRegistrableDomainBelowIdnPublicSuffix(): void
    {
        $configuration = new PasskeyConfiguration(
            'example.xn--55qx5d.cn',
            $this->faker->company(),
            'https://login.example.xn--55qx5d.cn',
            $this->faker->numberBetween(60, 600),
            $this->faker->numberBetween(60, 600),
            'prod'
        );

        self::assertSame('example.xn--55qx5d.cn', $configuration->getRpId());
        self::assertSame(
            ['https://login.example.xn--55qx5d.cn'],
            $configuration->getAllowedOrigins()
        );
    }

    /**
     * @dataProvider malformedProductionOriginProvider
     */
    public function testProductionRejectsMalformedSubdomainOrigin(string $origin): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Passkey allowed origin must be a browser origin.');

        new PasskeyConfiguration(
            'example.com',
            $this->faker->company(),
            $origin,
            $this->faker->numberBetween(60, 600),
            $this->faker->numberBetween(60, 600),
            'prod'
        );
    }

    /**
     * @return iterable<string, array{origin: string}>
     */
    public static function malformedProductionOriginProvider(): iterable
    {
        yield 'space in label' => ['origin' => 'https://evil .example.com'];
        yield 'empty label' => ['origin' => 'https://evil..example.com'];
        yield 'leading hyphen' => ['origin' => 'https://-evil.example.com'];
        yield 'trailing hyphen' => ['origin' => 'https://evil-.example.com'];
    }
}
