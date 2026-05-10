<?php

declare(strict_types=1);

namespace App\Shared\Application\Observability\Metric\ValueObject;

use InvalidArgumentException;

/**
 * CloudWatch metric unit as per AWS EMF specification.
 *
 * Value Object instead of enum to keep a richer DDD model and allow behavior.
 */
final readonly class MetricUnit
{
    public const COUNT = 'Count';
    public const NONE = 'None';
    public const SECONDS = 'Seconds';
    public const MILLISECONDS = 'Milliseconds';
    public const BYTES = 'Bytes';
    public const PERCENT = 'Percent';

    private const ALLOWED = [
        self::COUNT,
        self::NONE,
        self::SECONDS,
        self::MILLISECONDS,
        self::BYTES,
        self::PERCENT,
    ];

    /** @var non-empty-string */
    private string $value;

    public function __construct(string $value)
    {
        if (!in_array($value, self::ALLOWED, true)) {
            throw new InvalidArgumentException(sprintf('Invalid metric unit "%s".', $value));
        }

        $this->value = $value;
    }

    /** @return non-empty-string */
    public function value(): string
    {
        return $this->value;
    }
}
