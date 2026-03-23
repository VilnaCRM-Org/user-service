<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\RateLimit;

use App\Shared\Application\Converter\JwtTokenConverterInterface;
use App\Tests\Unit\UnitTestCase;

abstract class RateLimitClientTestCase extends UnitTestCase
{
    protected JwtTokenConverterInterface $jwtConverter;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->jwtConverter = $this->createMock(JwtTokenConverterInterface::class);
    }

    /**
     * @param array<string, bool|float|int|string|null> $overrides
     *
     * @return array<string, array<int, string>|bool|float|int|string|null>
     */
    protected function buildValidPayload(array $overrides = []): array
    {
        $now = time();

        /** @var array<string, array<int, string>|bool|float|int|string|null> $base */
        $base = [
            'iss' => 'vilnacrm-user-service',
            'aud' => 'vilnacrm-api',
            'sub' => $this->faker->uuid(),
            'nbf' => $now - 60,
            'exp' => $now + 3600,
        ];

        return array_merge($base, $overrides);
    }
}
