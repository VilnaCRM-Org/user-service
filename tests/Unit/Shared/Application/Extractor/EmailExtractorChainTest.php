<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Extractor;

use App\Shared\Application\Resolver\Extractor\BatchEmailExtractor;
use App\Shared\Application\Resolver\Extractor\ChainEmailExtractor;
use App\Shared\Application\Resolver\Extractor\NullEmailExtractor;
use App\Tests\Unit\UnitTestCase;

final class EmailExtractorChainTest extends UnitTestCase
{
    public function testNullEmailExtractorAlwaysReturnsNull(): void
    {
        $source = new NullEmailExtractor();

        self::assertNull($source->extract(['email' => $this->faker->email()]));
    }

    public function testChainEmailExtractorReturnsValueFromFirstSource(): void
    {
        $primaryEmail = $this->faker->email();
        $fallbackEmail = $this->faker->email();

        $source = new ChainEmailExtractor(
            $this->createEmailExtractor($primaryEmail),
            $this->createEmailExtractor($fallbackEmail)
        );

        self::assertSame($primaryEmail, $source->extract([]));
    }

    public function testChainEmailExtractorFallsBackWhenPrimaryReturnsNull(): void
    {
        $fallbackEmail = $this->faker->email();

        $source = new ChainEmailExtractor(
            $this->createNullExtractor(),
            $this->createEmailExtractor($fallbackEmail)
        );

        self::assertSame($fallbackEmail, $source->extract([]));
    }

    private function createEmailExtractor(string $email): object
    {
        return new class($email) implements BatchEmailExtractor {
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

    private function createNullExtractor(): object
    {
        return new class() implements BatchEmailExtractor {
            #[\Override]
            public function extract(mixed $entry): ?string
            {
                return null;
            }
        };
    }
}
