<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Formatter;

use App\Shared\Infrastructure\Observability\ValueObject\EmfPayload;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * AWS EMF Log Formatter
 *
 * Formats EmfPayload objects as JSON for AWS CloudWatch Embedded Metric Format.
 */
final readonly class EmfLogFormatter
{
    public function __construct(
        private LoggerInterface $logger,
        private SerializerInterface $serializer,
    ) {
    }

    public function format(EmfPayload $payload): string
    {
        try {
            return $this->serializer->serialize($payload, JsonEncoder::FORMAT)
                . "\n";
        } catch (NotEncodableValueException|\JsonException $e) {
            $this->logger->error('Failed to encode EMF payload', [
                'exception' => $e->getMessage(),
            ]);

            return '';
        }
    }
}
