<?php

declare(strict_types=1);

namespace App\User\Application\Passkey;

use function array_filter;
use function array_map;

use function array_values;
use DateTimeImmutable;
use function explode;
use InvalidArgumentException;
use function sprintf;
use function trim;

final readonly class PasskeyConfiguration
{
    public function __construct(
        private string $rpId,
        private string $rpName,
        private string $allowedOrigins,
        private int $timeoutSeconds,
        private int $challengeTtlSeconds
    ) {
        if (trim($rpId) === '') {
            throw new InvalidArgumentException('Passkey relying party ID must be configured.');
        }

        if (trim($rpName) === '') {
            throw new InvalidArgumentException('Passkey relying party name must be configured.');
        }

        if ($timeoutSeconds <= 0) {
            throw new InvalidArgumentException('Passkey timeout must be greater than zero.');
        }

        if ($challengeTtlSeconds <= 0) {
            throw new InvalidArgumentException('Passkey challenge TTL must be greater than zero.');
        }
    }

    public function getRpId(): string
    {
        return trim($this->rpId);
    }

    public function getRpName(): string
    {
        return trim($this->rpName);
    }

    /**
     * @return list<string>
     */
    public function getAllowedOrigins(): array
    {
        $origins = array_values(array_filter(
            array_map(
                static fn (string $origin): string => trim($origin),
                explode(',', $this->allowedOrigins)
            ),
            static fn (string $origin): bool => $origin !== ''
        ));

        if ($origins === []) {
            throw new InvalidArgumentException(
                'At least one passkey allowed origin must be configured.'
            );
        }

        return $origins;
    }

    public function getTimeoutMilliseconds(): int
    {
        return $this->timeoutSeconds * 1000;
    }

    public function challengeExpiresAt(DateTimeImmutable $createdAt): DateTimeImmutable
    {
        return $createdAt->modify(sprintf('+%d seconds', $this->challengeTtlSeconds));
    }
}
