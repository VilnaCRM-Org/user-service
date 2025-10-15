<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\EventListener;

use App\Tests\Unit\UnitTestCase;
use App\User\Infrastructure\EventListener\SchemathesisBatchUsersEmailExtractor;

final class SchemathesisBatchUsersEmailExtractorTest extends UnitTestCase
{
    public function testExtractDeduplicatesValidEmails(): void
    {
        $extractor = new SchemathesisBatchUsersEmailExtractor();

        $emails = $extractor->extract([
            'users' => [
                ['email' => 'first@example.com'],
                ['email' => 'second@example.com'],
                ['email' => 'first@example.com'],
                ['email' => null],
                'invalid',
            ],
        ]);

        $this->assertSame(
            [
                0 => 'first@example.com',
                1 => 'second@example.com',
                2 => 'first@example.com',
            ],
            $emails
        );
    }

    public function testExtractSkipsEntriesWithoutEmails(): void
    {
        $extractor = new SchemathesisBatchUsersEmailExtractor();

        $emails = $extractor->extract([
            'users' => [
                ['email' => 'alpha@example.com'],
                ['name' => 'missing email'],
                ['email' => 'beta@example.com'],
            ],
        ]);

        $this->assertSame(
            ['alpha@example.com', 'beta@example.com'],
            $emails
        );
    }

    public function testExtractReindexesResult(): void
    {
        $extractor = new SchemathesisBatchUsersEmailExtractor();

        $emails = $extractor->extract([
            'users' => [
                3 => ['email' => 'gamma@example.com'],
                4 => ['email' => null],
                6 => ['email' => 'delta@example.com'],
            ],
        ]);

        $this->assertSame(
            ['gamma@example.com', 'delta@example.com'],
            $emails
        );
    }

    public function testExtractSkipsNonArrayUsers(): void
    {
        $extractor = new SchemathesisBatchUsersEmailExtractor();

        $emails = $extractor->extract([
            'users' => [
                new \stdClass(),
                ['email' => 'valid@example.com'],
            ],
        ]);

        $this->assertSame(['valid@example.com'], $emails);
    }
}
