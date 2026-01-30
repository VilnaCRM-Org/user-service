<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver\Source;

use App\Shared\Application\Resolver\Source\BatchEmailSource;
use App\Shared\Application\Resolver\Source\ChainEmailSource;
use App\Shared\Application\Resolver\Source\NullEmailSource;
use App\Tests\Unit\UnitTestCase;

final class EmailSourceChainTest extends UnitTestCase
{
    public function testNullEmailSourceAlwaysReturnsNull(): void
    {
        $source = new NullEmailSource();

        self::assertNull($source->extract(['email' => $this->faker->email()]));
    }

    public function testChainEmailSourceReturnsValueFromFirstSource(): void
    {
        $primaryEmail = $this->faker->email();
        $fallbackEmail = $this->faker->email();

        $source = new ChainEmailSource(
            $this->createEmailSource($primaryEmail),
            $this->createEmailSource($fallbackEmail)
        );

        self::assertSame($primaryEmail, $source->extract([]));
    }

    public function testChainEmailSourceFallsBackWhenPrimaryReturnsNull(): void
    {
        $fallbackEmail = $this->faker->email();

        $source = new ChainEmailSource(
            $this->createNullSource(),
            $this->createEmailSource($fallbackEmail)
        );

        self::assertSame($fallbackEmail, $source->extract([]));
    }

    private function createEmailSource(string $email): BatchEmailSource
    {
        return new class($email) implements BatchEmailSource {
            public function __construct(private string $email)
            {
            }

            #[\Override]
            public function extract(mixed $entry): ?string
            {
                return $this->email;
            }
        };
    }

    private function createNullSource(): BatchEmailSource
    {
        return new class() implements BatchEmailSource {
            #[\Override]
            public function extract(mixed $entry): ?string
            {
                return null;
            }
        };
    }
}
