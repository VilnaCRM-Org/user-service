<?php

declare(strict_types=1);

namespace App\Shared\Application\Evaluator;

use App\Shared\Application\Collector\BatchEmailCollector;
use App\Shared\Application\Normalizer\BatchEntriesNormalizer;
use App\Shared\Application\Normalizer\BatchEntriesResult;

final class CreateUserBatchConstraintEvaluator
{
    private const MESSAGE_EMPTY = 'batch.empty';
    private const MESSAGE_EMAIL_MISSING = 'batch.email.missing';
    private const MESSAGE_EMAIL_DUPLICATE = 'batch.email.duplicate';

    public function __construct(
        private readonly BatchEntriesNormalizer $normalizer,
        private readonly BatchEmailCollector $collector
    ) {
    }

    /**
     * @return string[]
     *
     * @param string|array<array<string>> $value
     *
     * @psalm-param 'invalid'|list{0?: array{email?: 'user1@example.com'|'user@example.com', name?: 'Missing email'}, 1?: array{email: 'USER@example.com'|'user2@example.com'|'user@example.com'}, 2?: array{name: 'Missing'}} $value
     *
     * @psalm-return list<string>
     */
    public function evaluate(array|string $value): array
    {
        $result = $this->normalizer->normalize($value);
        $state = $result->state();

        return match ($state) {
            BatchEntriesResult::STATE_NOT_ITERABLE => [
                self::MESSAGE_EMAIL_MISSING,
            ],
            BatchEntriesResult::STATE_EMPTY => [
                self::MESSAGE_EMPTY,
            ],
            default => $this->collector
                ->collect($result->entries())
                ->messages(
                    self::MESSAGE_EMAIL_MISSING,
                    self::MESSAGE_EMAIL_DUPLICATE
                ),
        };
    }
}
