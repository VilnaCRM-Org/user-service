<?php

declare(strict_types=1);

namespace App\User\Application\Metric;

use App\Shared\Application\Observability\Metric\EndpointOperationBusinessMetric;
use App\Shared\Application\Observability\Metric\ValueObject\MetricUnit;

/**
 * Metric for tracking password reset request events.
 *
 * Uses pure Value Objects without service dependencies (DDD compliant).
 */
final readonly class PasswordResetRequestsMetric extends EndpointOperationBusinessMetric
{
    private const ENDPOINT = 'User';
    private const OPERATION = 'request-password-reset';

    public function __construct(float|int $value = 1)
    {
        parent::__construct($value, new MetricUnit(MetricUnit::COUNT));
    }

    /**
     * @return string
     *
     * @psalm-return 'PasswordResetRequests'
     */
    #[\Override]
    public function name(): string
    {
        return 'PasswordResetRequests';
    }

    /**
     * @return string
     *
     * @psalm-return 'User'
     */
    #[\Override]
    protected function endpoint(): string
    {
        return self::ENDPOINT;
    }

    /**
     * @return string
     *
     * @psalm-return 'request-password-reset'
     */
    #[\Override]
    protected function operation(): string
    {
        return self::OPERATION;
    }
}
