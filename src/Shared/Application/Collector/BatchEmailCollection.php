<?php

declare(strict_types=1);

namespace App\Shared\Application\Collector;

final class BatchEmailCollection
{
    /**
     * @param array<string> $emails
     */
    public function __construct(
        private readonly array $emails,
        private readonly bool $hasMissing
    ) {
    }

    public function hasMissing(): bool
    {
        return $this->hasMissing;
    }

    /**
     * @return array<string>
     */
    public function emails(): array
    {
        return $this->emails;
    }

    public function hasDuplicates(): bool
    {
        return $this->emails !== []
            && count($this->emails) !== count(array_unique($this->emails));
    }

    /**
     * @return string[]
     *
     * @psalm-return list<string>
     */
    public function messages(
        string $missingMessage,
        string $duplicateMessage
    ): array {
        return array_keys(array_filter([
            $missingMessage => $this->hasMissing,
            $duplicateMessage => $this->hasDuplicates(),
        ]));
    }
}
