<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Service;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Service\EmailNormalizer;

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
