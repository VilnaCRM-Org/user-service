<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Validator;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Validator\PasskeyAllowedOriginNormalizer;
use InvalidArgumentException;

use function sprintf;

final class PasskeyAllowedOriginNormalizerTest extends UnitTestCase
{
    /**
     * @dataProvider invalidBrowserOriginProvider
     */
    public function testRejectsInvalidBrowserOrigins(string $case): void
    {
        $rpId = $this->faker->domainName();
        $origin = match ($case) {
            'path' => sprintf('https://%s/passkeys', $this->faker->domainName()),
            'query' => sprintf('https://%s?debug=1', $this->faker->domainName()),
            'missing scheme' => $this->faker->domainName(),
            'unsupported scheme' => sprintf('ftp://%s', $this->faker->domainName()),
            'trailing dot host' => sprintf('https://%s.', $rpId),
        };

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Passkey allowed origin must be a browser origin.');

        (new PasskeyAllowedOriginNormalizer())->normalize($origin, 'dev', $rpId);
    }

    /**
     * @psalm-return \Generator<string, list{string}, void, void>
     */
    public static function invalidBrowserOriginProvider(): \Generator
    {
        yield 'path' => ['path'];
        yield 'query' => ['query'];
        yield 'missing scheme' => ['missing scheme'];
        yield 'unsupported scheme' => ['unsupported scheme'];
        yield 'trailing dot host' => ['trailing dot host'];
    }
}
