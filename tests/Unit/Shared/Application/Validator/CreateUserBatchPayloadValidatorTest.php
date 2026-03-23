<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator;

use App\Shared\Application\Normalizer\BatchEntriesNormalizer;
use App\Shared\Application\Provider\BatchEmailProvider;
use App\Shared\Application\Resolver\BatchEmailResolver;
use App\Shared\Application\Validator\CreateUserBatchPayloadValidator;
use App\Tests\Unit\UnitTestCase;

final class CreateUserBatchPayloadValidatorTest extends UnitTestCase
{
    private CreateUserBatchPayloadValidator $validator;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new CreateUserBatchPayloadValidator(
            new BatchEntriesNormalizer(),
            new BatchEmailProvider(new BatchEmailResolver())
        );
    }

    public function testReturnsMissingEmailMessageWhenPayloadNotIterable(): void
    {
        $this->assertSame(['batch.email.missing'], $this->validator->validate('invalid'));
    }

    public function testReturnsEmptyMessageWhenPayloadEmpty(): void
    {
        $this->assertSame(['batch.empty'], $this->validator->validate([]));
    }

    public function testReturnsMissingAndDuplicateMessagesForInvalidBatchEntries(): void
    {
        $payload = [
            ['email' => 'USER@example.com'],
            ['email' => 'user@example.com'],
            ['name' => 'Missing'],
        ];

        $this->assertSame(
            ['batch.email.missing', 'batch.email.duplicate'],
            $this->validator->validate($payload)
        );
    }
}
