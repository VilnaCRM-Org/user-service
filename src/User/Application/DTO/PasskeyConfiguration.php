<?php

declare(strict_types=1);

namespace App\User\Application\DTO;

use App\User\Application\Validator\PasskeyAllowedOriginNormalizer;
use App\User\Application\Validator\PasskeyRpIdValidator;

use function array_filter;
use function array_map;
use function array_values;

use DateTimeImmutable;

use function explode;
use function filter_var;

use InvalidArgumentException;

use function sprintf;
use function str_ends_with;
use function str_starts_with;
use function strtolower;
use function trim;

final readonly class PasskeyConfiguration
{
    private const MAX_TIMEOUT_SECONDS = 600;
    private const MAX_CHALLENGE_TTL_SECONDS = 600;
    private const PUBLIC_SUFFIX_LIST_PATH =
        __DIR__ . '/../../../../config/passkey/public_suffix_list.dat';
    private PasskeyAllowedOriginNormalizer $allowedOriginNormalizer;
    private PasskeyRpIdValidator $rpIdValidator;

    public function __construct(
        private string $rpId,
        private string $rpName,
        private string $allowedOrigins,
        private int $timeoutSeconds,
        private int $challengeTtlSeconds,
        private string $appEnv = 'dev',
        private string $publicSuffixListPath = self::PUBLIC_SUFFIX_LIST_PATH,
        ?PasskeyRpIdValidator $rpIdValidator = null
    ) {
        $this->allowedOriginNormalizer = new PasskeyAllowedOriginNormalizer();
        $this->rpIdValidator = $rpIdValidator ?? new PasskeyRpIdValidator();
        $this->assertRelyingParty($rpId, $rpName);
        $this->assertSecondsWithinLimit(
            $timeoutSeconds,
            self::MAX_TIMEOUT_SECONDS,
            'Passkey timeout'
        );
        $this->assertSecondsWithinLimit(
            $challengeTtlSeconds,
            self::MAX_CHALLENGE_TTL_SECONDS,
            'Passkey challenge TTL'
        );
        $this->resolveAllowedOrigins();
    }

    public function getRpId(): string
    {
        return strtolower(trim($this->rpId));
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
        return $this->resolveAllowedOrigins();
    }

    public function getTimeoutMilliseconds(): int
    {
        return $this->timeoutSeconds * 1000;
    }

    public function challengeExpiresAt(DateTimeImmutable $createdAt): DateTimeImmutable
    {
        return $createdAt->modify(sprintf('+%d seconds', $this->challengeTtlSeconds));
    }

    private function assertRelyingParty(string $rpId, string $rpName): void
    {
        $trimmedRpId = trim($rpId);
        if ($trimmedRpId === '') {
            throw new InvalidArgumentException('Passkey relying party ID must be configured.');
        }

        $this->assertRpId($trimmedRpId);

        if (trim($rpName) === '') {
            throw new InvalidArgumentException('Passkey relying party name must be configured.');
        }
    }

    private function assertSecondsWithinLimit(
        int $seconds,
        int $maximumSeconds,
        string $name
    ): void {
        if ($seconds <= 0) {
            throw new InvalidArgumentException(sprintf('%s must be greater than zero.', $name));
        }

        if ($seconds > $maximumSeconds) {
            throw new InvalidArgumentException(sprintf(
                '%s must not exceed %d seconds.',
                $name,
                $maximumSeconds
            ));
        }
    }

    /**
     * @return list<string>
     */
    private function resolveAllowedOrigins(): array
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

        return array_map($this->normalizeAllowedOrigin(...), $origins);
    }

    private function assertRpId(string $rpId): void
    {
        $normalizedRpId = strtolower($rpId);
        if (str_starts_with($normalizedRpId, '.') || str_ends_with($normalizedRpId, '.')) {
            throw new InvalidArgumentException('Passkey relying party ID must be a host name.');
        }

        if (
            filter_var($normalizedRpId, FILTER_VALIDATE_IP) !== false
            || filter_var($normalizedRpId, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false
        ) {
            throw new InvalidArgumentException('Passkey relying party ID must be a host name.');
        }

        $this->assertProductionRpId($normalizedRpId);
    }

    private function normalizeAllowedOrigin(string $origin): string
    {
        return $this->allowedOriginNormalizer->normalize(
            $origin,
            $this->appEnv,
            $this->getRpId()
        );
    }

    private function assertProductionRpId(string $rpId): void
    {
        $this->rpIdValidator->assertProductionHostName(
            $rpId,
            $this->appEnv,
            $this->publicSuffixListPath
        );
    }
}
