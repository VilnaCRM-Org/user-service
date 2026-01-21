<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Observability\Emitter;

use App\Shared\Application\Observability\Metric\Collection\MetricCollection;
use App\Shared\Application\Observability\Metric\EndpointInvocationsMetric;
use App\Shared\Infrastructure\Observability\Emitter\AwsEmfBusinessMetricsEmitter;
use App\Shared\Infrastructure\Observability\Factory\EmfAwsMetadataFactory;
use App\Shared\Infrastructure\Observability\Factory\EmfPayloadFactory;
use App\Shared\Infrastructure\Observability\Factory\EmfPayloadFactoryInterface;
use App\Shared\Infrastructure\Observability\Formatter\EmfLogFormatter;
use App\Shared\Infrastructure\Observability\Provider\SystemEmfTimestampProvider;
use App\Shared\Infrastructure\Observability\Validator\EmfDimensionValueValidator;
use App\Shared\Infrastructure\Observability\Validator\EmfNamespaceValidator;
use App\Shared\Infrastructure\Observability\Validator\EmfPayloadValidator;
use App\Tests\Unit\Shared\Application\Observability\Metric\TestCustomerMetric;
use App\Tests\Unit\Shared\Application\Observability\Metric\TestInvalidUtf8Metric;
use App\Tests\Unit\Shared\Application\Observability\Metric\TestOrdersPlacedMetric;
use App\Tests\Unit\Shared\Application\Observability\Metric\TestOrderValueMetric;
use App\Tests\Unit\UnitTestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validation;

final class AwsEmfBusinessMetricsEmitterTest extends UnitTestCase
{
    private const string NAMESPACE = 'UserService/BusinessMetrics';

    /** @var array<string, string|int|float|array<array-key, string|int|float|array>>|null */
    private ?array $capturedContext = null;

    public function testEmitsValidEmfPayloadForSingleMetric(): void
    {
        $before = (int) (microtime(true) * 1000);
        $emitter = $this->createEmitterWithContextCapture();

        $emitter->emit(new EndpointInvocationsMetric('HealthCheck', 'get'));

        $this->assertTimestampWithinRange($before);
        $this->assertSingleMetricValues();
        $this->assertSingleMetricEmfStructure();
    }

    public function testEmitsValidEmfPayloadForMetricCollection(): void
    {
        $before = (int) (microtime(true) * 1000);
        $emitter = $this->createEmitterWithContextCapture();

        $emitter->emitCollection($this->createOrderMetricCollection());

        $this->assertTimestampWithinRange($before);
        $this->assertCollectionMetricValues();
        $this->assertCollectionEmfStructure();
    }

    public function testUsesCustomNamespace(): void
    {
        $customNamespace = 'CustomApp/Metrics';
        $emitter = $this->createEmitterWithContextCapture($customNamespace);

        $emitter->emit(new EndpointInvocationsMetric('Test', 'test'));

        $namespace = $this->capturedContext['_aws']['CloudWatchMetrics'][0]['Namespace'];
        self::assertSame($customNamespace, $namespace);
    }

    public function testDoesNotEmitForEmptyCollection(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('info');

        $emitter = $this->createEmitterWithLogger($logger);
        $emitter->emitCollection(new MetricCollection());
    }

    public function testLogsErrorWhenMetricHasInvalidDimensions(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('info');
        $logger->expects(self::once())->method('error')->with('Failed to emit EMF metric');

        $emitter = $this->createEmitterWithLogger($logger);

        $emitter->emit(new TestInvalidUtf8Metric());
    }

    public function testMetricValueIsCorrectlySet(): void
    {
        $emitter = $this->createEmitterWithContextCapture();

        $emitter->emit(new EndpointInvocationsMetric('Customer', 'create', 42));

        self::assertSame(42, $this->capturedContext['EndpointInvocations']);
    }

    public function testCollectionUsesDimensionsFromFirstMetric(): void
    {
        $emitter = $this->createEmitterWithContextCapture();

        $collection = new MetricCollection(
            new TestOrdersPlacedMetric(1),
            new TestCustomerMetric(1)
        );
        $emitter->emitCollection($collection);

        self::assertSame('Order', $this->capturedContext['Endpoint']);
        self::assertSame('create', $this->capturedContext['Operation']);
        $dims = $this->capturedContext['_aws']['CloudWatchMetrics'][0]['Dimensions'];
        self::assertSame([['Endpoint', 'Operation']], $dims);
    }

    public function testLogsErrorWhenEmitFails(): void
    {
        $payloadFactory = $this->createMock(EmfPayloadFactoryInterface::class);
        $payloadFactory->expects(self::once())
            ->method('createFromMetric')
            ->willThrowException(new \RuntimeException('Factory failed'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error')->with('Failed to emit EMF metric');
        $logger->expects(self::never())->method('info');

        $formatterLogger = $this->createMock(LoggerInterface::class);
        $emitter = new AwsEmfBusinessMetricsEmitter(
            $logger,
            new EmfLogFormatter($formatterLogger),
            $payloadFactory
        );

        $emitter->emit(new EndpointInvocationsMetric('Test', 'test'));
    }

    public function testLogsErrorWhenEmitCollectionFails(): void
    {
        $payloadFactory = $this->createMock(EmfPayloadFactoryInterface::class);
        $payloadFactory->expects(self::once())
            ->method('createFromCollection')
            ->willThrowException(new \InvalidArgumentException('Collection error'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with('Failed to emit EMF metric collection');
        $logger->expects(self::never())->method('info');

        $formatterLogger = $this->createMock(LoggerInterface::class);
        $emitter = new AwsEmfBusinessMetricsEmitter(
            $logger,
            new EmfLogFormatter($formatterLogger),
            $payloadFactory
        );

        $emitter->emitCollection($this->createOrderMetricCollection());
    }

    public function testSkipsLoggingWhenFormatterReturnsEmptyString(): void
    {
        $formatter = $this->createMock(EmfLogFormatter::class);
        $formatter->expects(self::once())
            ->method('format')
            ->willReturn('');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('info');
        $logger->expects(self::never())->method('error');

        $payloadFactory = $this->createPayloadFactory(self::NAMESPACE);
        $emitter = new AwsEmfBusinessMetricsEmitter($logger, $formatter, $payloadFactory);

        $emitter->emit(new EndpointInvocationsMetric('Test', 'test'));
    }

    private function createEmitterWithContextCapture(
        string $namespace = self::NAMESPACE
    ): AwsEmfBusinessMetricsEmitter {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('info')
            ->with($this->callback(function (string $message): bool {
                /** @var array<string, string|int|float|array<array-key, string|int|float|array>> $decoded */
                $decoded = json_decode(rtrim($message, "\n"), true);
                self::assertIsArray($decoded);
                $this->capturedContext = $decoded;

                return true;
            }));

        return $this->createEmitterWithLoggerAndNamespace($logger, $namespace);
    }

    private function createEmitterWithLogger(LoggerInterface $logger): AwsEmfBusinessMetricsEmitter
    {
        return $this->createEmitterWithLoggerAndNamespace($logger, self::NAMESPACE);
    }

    private function createEmitterWithLoggerAndNamespace(
        LoggerInterface $logger,
        string $namespace
    ): AwsEmfBusinessMetricsEmitter {
        $payloadFactory = $this->createPayloadFactory($namespace);
        $formatterLogger = $this->createMock(LoggerInterface::class);

        return new AwsEmfBusinessMetricsEmitter(
            $logger,
            new EmfLogFormatter($formatterLogger),
            $payloadFactory
        );
    }

    private function createPayloadFactory(string $namespace): EmfPayloadFactory
    {
        $timestampProvider = new SystemEmfTimestampProvider();
        $validator = Validation::createValidator();
        $namespaceValidator = new EmfNamespaceValidator($validator);
        $metadataFactory = new EmfAwsMetadataFactory(
            $namespace,
            $timestampProvider,
            $namespaceValidator
        );
        $dimensionValidator = new EmfDimensionValueValidator($validator);
        $payloadValidator = new EmfPayloadValidator();

        return new EmfPayloadFactory(
            $metadataFactory,
            $dimensionValidator,
            $payloadValidator
        );
    }

    private function createOrderMetricCollection(): MetricCollection
    {
        return new MetricCollection(
            new TestOrdersPlacedMetric(1),
            new TestOrderValueMetric(99.9)
        );
    }

    private function assertTimestampWithinRange(int $before): void
    {
        self::assertArrayHasKey('_aws', $this->capturedContext);
        self::assertIsInt($this->capturedContext['_aws']['Timestamp']);
        self::assertGreaterThanOrEqual($before, $this->capturedContext['_aws']['Timestamp']);
        self::assertLessThanOrEqual($before + 10_000, $this->capturedContext['_aws']['Timestamp']);
    }

    private function assertSingleMetricValues(): void
    {
        self::assertSame(1, $this->capturedContext['EndpointInvocations']);
        self::assertSame('HealthCheck', $this->capturedContext['Endpoint']);
        self::assertSame('get', $this->capturedContext['Operation']);
    }

    private function assertSingleMetricEmfStructure(): void
    {
        $cw = $this->capturedContext['_aws']['CloudWatchMetrics'][0];
        self::assertSame(self::NAMESPACE, $cw['Namespace']);
        self::assertSame([['Endpoint', 'Operation']], $cw['Dimensions']);
        self::assertSame('EndpointInvocations', $cw['Metrics'][0]['Name']);
        self::assertSame('Count', $cw['Metrics'][0]['Unit']);
    }

    private function assertCollectionMetricValues(): void
    {
        self::assertSame(1, $this->capturedContext['OrdersPlaced']);
        self::assertSame(99.9, $this->capturedContext['OrderValue']);
        self::assertSame('Order', $this->capturedContext['Endpoint']);
        self::assertSame('create', $this->capturedContext['Operation']);
    }

    private function assertCollectionEmfStructure(): void
    {
        $cw = $this->capturedContext['_aws']['CloudWatchMetrics'][0];
        self::assertSame(self::NAMESPACE, $cw['Namespace']);
        self::assertSame([['Endpoint', 'Operation']], $cw['Dimensions']);
        $metrics = $cw['Metrics'];
        self::assertCount(2, $metrics);
        self::assertSame(['Name' => 'OrdersPlaced', 'Unit' => 'Count'], $metrics[0]);
        self::assertSame(['Name' => 'OrderValue', 'Unit' => 'None'], $metrics[1]);
    }
}
