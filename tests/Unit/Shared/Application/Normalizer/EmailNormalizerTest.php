<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Normalizer;

use App\Shared\Application\Normalizer\EmailNormalizer;
use App\Tests\Unit\UnitTestCase;

final class EmailNormalizerTest extends UnitTestCase
{
    public function testNormalizeTrimsAndLowercasesEmail(): void
    {
        $email = ' ' . "\u{00C4}" . strtoupper($this->faker->safeEmail()) . ' ';

        $this->assertSame(
            mb_strtolower(trim($email), 'UTF-8'),
            (new EmailNormalizer())->normalize($email)
        );
    }
}
