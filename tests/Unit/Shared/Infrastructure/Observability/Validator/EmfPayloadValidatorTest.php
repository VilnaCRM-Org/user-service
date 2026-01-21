<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Observability\Validator;

use App\Shared\Infrastructure\Observability\Collection\EmfDimensionKeys;
use App\Shared\Infrastructure\Observability\Collection\EmfDimensionValueCollection;
use App\Shared\Infrastructure\Observability\Collection\EmfMetricDefinitionCollection;
use App\Shared\Infrastructure\Observability\Collection\EmfMetricValueCollection;
use App\Shared\Infrastructure\Observability\Exception\EmfKeyCollisionException;
use App\Shared\Infrastructure\Observability\Validator\EmfDimensionValueValidator;
use App\Shared\Infrastructure\Observability\Validator\EmfDimensionValueValidatorInterface;
use App\Shared\Infrastructure\Observability\Validator\EmfPayloadValidator;
use App\Shared\Infrastructure\Observability\ValueObject\EmfAwsMetadata;
use App\Shared\Infrastructure\Observability\ValueObject\EmfCloudWatchMetricConfig;
use App\Shared\Infrastructure\Observability\ValueObject\EmfDimensionValue;
use App\Shared\Infrastructure\Observability\ValueObject\EmfMetricDefinition;
use App\Shared\Infrastructure\Observability\ValueObject\EmfMetricValue;
use App\Shared\Infrastructure\Observability\ValueObject\EmfPayload;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\Validation;

final class EmfPayloadValidatorTest extends UnitTestCase
{
    private EmfPayloadValidator $validator;
    private EmfDimensionValueValidatorInterface $dimensionValidator;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new EmfPayloadValidator();
        $this->dimensionValidator = new EmfDimensionValueValidator(Validation::createValidator());
    }

    public function testValidatesPayloadWithoutCollisions(): void
    {
        $payload = $this->createValidPayload();

        $this->validator->validate($payload);

        $this->expectNotToPerformAssertions();
    }

    public function testThrowsExceptionWhenDimensionKeyMatchesMetricName(): void
    {
        $this->expectException(EmfKeyCollisionException::class);
        $this->expectExceptionMessage('Key collision detected');

        $payload = $this->createPayloadWithDimensionMetricCollision();

        $this->validator->validate($payload);
    }

    public function testThrowsExceptionWhenReservedAwsKeyIsUsedAsDimension(): void
    {
        $this->expectException(EmfKeyCollisionException::class);
        $this->expectExceptionMessage('reserved for metadata');

        $payload = $this->createPayloadWithReservedKeyAsDimension();

        $this->validator->validate($payload);
    }

    public function testThrowsExceptionWhenReservedAwsKeyIsUsedAsMetric(): void
    {
        $this->expectException(EmfKeyCollisionException::class);
        $this->expectExceptionMessage('reserved for metadata');

        $payload = $this->createPayloadWithReservedKeyAsMetric();

        $this->validator->validate($payload);
    }

    private function createValidPayload(): EmfPayload
    {
        $metricDefinition = new EmfMetricDefinition('CustomersCreated', 'Count');
        $dimensionKeys = new EmfDimensionKeys('Endpoint', 'Operation');
        $cloudWatchConfig = new EmfCloudWatchMetricConfig(
            'TestApp/Metrics',
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

    private function createPayloadWithDimensionMetricCollision(): EmfPayload
    {
        $metricDefinition = new EmfMetricDefinition('Endpoint', 'Count');
        $dimensionKeys = new EmfDimensionKeys('Endpoint');
        $cloudWatchConfig = new EmfCloudWatchMetricConfig(
            'TestApp/Metrics',
            $dimensionKeys,
            new EmfMetricDefinitionCollection($metricDefinition)
        );
        $awsMetadata = new EmfAwsMetadata(1702425600000, $cloudWatchConfig);

        $dimensionValues = new EmfDimensionValueCollection(
            $this->dimensionValidator,
            new EmfDimensionValue('Endpoint', 'Customer')
        );

        $metricValues = new EmfMetricValueCollection(
            new EmfMetricValue('Endpoint', 1)
        );

        return new EmfPayload($awsMetadata, $dimensionValues, $metricValues);
    }

    private function createPayloadWithReservedKeyAsDimension(): EmfPayload
    {
        $metricDefinition = new EmfMetricDefinition('CustomersCreated', 'Count');
        $dimensionKeys = new EmfDimensionKeys('_aws');
        $cloudWatchConfig = new EmfCloudWatchMetricConfig(
            'TestApp/Metrics',
            $dimensionKeys,
            new EmfMetricDefinitionCollection($metricDefinition)
        );
        $awsMetadata = new EmfAwsMetadata(1702425600000, $cloudWatchConfig);

        $dimensionValues = new EmfDimensionValueCollection(
            $this->dimensionValidator,
            new EmfDimensionValue('_aws', 'invalid')
        );

        $metricValues = new EmfMetricValueCollection(
            new EmfMetricValue('CustomersCreated', 1)
        );

        return new EmfPayload($awsMetadata, $dimensionValues, $metricValues);
    }

    private function createPayloadWithReservedKeyAsMetric(): EmfPayload
    {
        $metricDefinition = new EmfMetricDefinition('_aws', 'Count');
        $dimensionKeys = new EmfDimensionKeys('Endpoint');
        $cloudWatchConfig = new EmfCloudWatchMetricConfig(
            'TestApp/Metrics',
            $dimensionKeys,
            new EmfMetricDefinitionCollection($metricDefinition)
        );
        $awsMetadata = new EmfAwsMetadata(1702425600000, $cloudWatchConfig);

        $dimensionValues = new EmfDimensionValueCollection(
            $this->dimensionValidator,
            new EmfDimensionValue('Endpoint', 'Customer')
        );

        $metricValues = new EmfMetricValueCollection(
            new EmfMetricValue('_aws', 1)
        );

        return new EmfPayload($awsMetadata, $dimensionValues, $metricValues);
    }
}
