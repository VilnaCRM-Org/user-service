<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Formatter;

use App\Shared\Infrastructure\Observability\ValueObject\EmfPayload;
use Psr\Log\LoggerInterface;

/**
 * AWS EMF Log Formatter
 *
 * Formats EmfPayload objects as JSON for AWS CloudWatch Embedded Metric Format.
 */
final readonly class EmfLogFormatter
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function format(EmfPayload $payload): string
    {
        try {
            return json_encode($payload, JSON_THROW_ON_ERROR) . "\n";
        } catch (\JsonException $e) {
            $this->logger->error('Failed to encode EMF payload', [
                'exception' => $e->getMessage(),
            ]);

            return '';
        }
    }
}
