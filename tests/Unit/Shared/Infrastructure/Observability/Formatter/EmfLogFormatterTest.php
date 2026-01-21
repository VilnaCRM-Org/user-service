<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Observability\Formatter;

use App\Shared\Infrastructure\Observability\Collection\EmfDimensionKeys;
use App\Shared\Infrastructure\Observability\Collection\EmfDimensionValueCollection;
use App\Shared\Infrastructure\Observability\Collection\EmfMetricDefinitionCollection;
use App\Shared\Infrastructure\Observability\Collection\EmfMetricValueCollection;
use App\Shared\Infrastructure\Observability\Formatter\EmfLogFormatter;
use App\Shared\Infrastructure\Observability\Validator\EmfDimensionValueValidator;
use App\Shared\Infrastructure\Observability\Validator\EmfDimensionValueValidatorInterface;
use App\Shared\Infrastructure\Observability\ValueObject\EmfAwsMetadata;
use App\Shared\Infrastructure\Observability\ValueObject\EmfCloudWatchMetricConfig;
use App\Shared\Infrastructure\Observability\ValueObject\EmfDimensionValue;
use App\Shared\Infrastructure\Observability\ValueObject\EmfMetricDefinition;
use App\Shared\Infrastructure\Observability\ValueObject\EmfMetricValue;
use App\Shared\Infrastructure\Observability\ValueObject\EmfPayload;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validation;

final class EmfLogFormatterTest extends UnitTestCase
{
    private LoggerInterface&MockObject $logger;
    private EmfLogFormatter $formatter;
    private EmfDimensionValueValidatorInterface $dimensionValidator;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->formatter = new EmfLogFormatter($this->logger);
        $this->dimensionValidator = new EmfDimensionValueValidator(Validation::createValidator());
    }

    public function testFormatsPayloadAsJson(): void
    {
        $payload = $this->createTestPayload();

        $formatted = $this->formatter->format($payload);

        self::assertStringStartsWith('{', $formatted);
        self::assertStringEndsWith("}\n", $formatted);

        $decoded = json_decode(rtrim($formatted, "\n"), true);
        $expected = [
            '_aws' => [
                'Timestamp' => 1702425600000,
                'CloudWatchMetrics' => [
                    [
                        'Namespace' => 'UserService/BusinessMetrics',
                        'Dimensions' => [['Endpoint', 'Operation']],
                        'Metrics' => [['Name' => 'CustomersCreated', 'Unit' => 'Count']],
                    ],
                ],
            ],
            'Endpoint' => 'Customer',
            'Operation' => 'create',
            'CustomersCreated' => 1,
        ];
        self::assertSame($expected, $decoded);
    }

    public function testFormatsPayloadWithProperStructure(): void
    {
        $payload = $this->createTestPayload();

        $formatted = $this->formatter->format($payload);
        $decoded = json_decode(rtrim($formatted, "\n"), true);

        self::assertArrayHasKey('_aws', $decoded);
        self::assertArrayHasKey('Timestamp', $decoded['_aws']);
        self::assertArrayHasKey('CloudWatchMetrics', $decoded['_aws']);
        self::assertArrayHasKey('Endpoint', $decoded);
        self::assertArrayHasKey('Operation', $decoded);
        self::assertArrayHasKey('CustomersCreated', $decoded);
    }

    public function testLogsErrorAndReturnsEmptyStringOnJsonEncodingFailure(): void
    {
        $payload = $this->createMock(EmfPayload::class);
        $payload->method('jsonSerialize')
            ->willThrowException(new \JsonException('Encoding failed'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Failed to encode EMF payload',
                $this->callback(static function (array $context): bool {
                    return isset($context['exception'])
                        && $context['exception'] === 'Encoding failed';
                })
            );

        $formatted = $this->formatter->format($payload);

        self::assertSame('', $formatted);
    }

    private function createTestPayload(): EmfPayload
    {
        $metricDefinition = new EmfMetricDefinition('CustomersCreated', 'Count');
        $dimensionKeys = new EmfDimensionKeys('Endpoint', 'Operation');
        $cloudWatchConfig = new EmfCloudWatchMetricConfig(
            'UserService/BusinessMetrics',
            $dimensionKeys,
            new EmfMetricDefinitionCollection($metricDefinition)
        );
        $awsMetadata = new EmfAwsMetadata(1702425600000, $cloudWatchConfig);

        $dimensionValues = new EmfDimensionValueCollection(
            $this->dimensionValidator,
            new EmfDimensionValue('Endpoint', 'Customer'),
            new EmfDimensionValue('Operation', 'create')
        );

        $metricValues = new EmfMetricValueCollection(
            new EmfMetricValue('CustomersCreated', 1)
        );

        return new EmfPayload($awsMetadata, $dimensionValues, $metricValues);
    }
}
