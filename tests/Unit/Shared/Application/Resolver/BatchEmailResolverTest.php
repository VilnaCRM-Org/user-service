<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Resolver;

use App\Shared\Application\Resolver\BatchEmailResolver;
use App\Shared\Application\Validator\Source\BatchEmailSource;
use App\Tests\Unit\UnitTestCase;

final class BatchEmailResolverTest extends UnitTestCase
{
    public function testResolveCastsNullCandidateToEmptyString(): void
    {
        $source = new class() implements BatchEmailSource {
            #[\Override]
            public function extract(mixed $entry): ?string
            {
                return null;
            }
        };

        $resolver = new BatchEmailResolver([$source]);

        self::assertNull($resolver->resolve(['email' => null]));
    }

    public function testResolveNormalizesMultibyteEmail(): void
    {
        $source = new class() implements BatchEmailSource {
            #[\Override]
            public function extract(mixed $entry): ?string
            {
                return 'ÜSER@Example.com';
            }
        };

        $resolver = new BatchEmailResolver([$source]);

        self::assertSame('üser@example.com', $resolver->resolve(['email' => 'ÜSER@Example.com']));
    }

    public function testResolveTrimsWhitespaceAroundEmail(): void
    {
        $source = new class() implements BatchEmailSource {
            #[\Override]
            public function extract(mixed $entry): ?string
            {
                return '  spaced@example.com  ';
            }
        };

        $resolver = new BatchEmailResolver([$source]);

        self::assertSame('spaced@example.com', $resolver->resolve([]));
    }
}
