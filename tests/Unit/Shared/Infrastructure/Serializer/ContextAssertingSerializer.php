<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Serializer;

use PHPUnit\Framework\Assert;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;

final class ContextAssertingSerializer implements SerializerInterface
{
    /**
     * @param array<string, bool|int> $expectedContext
     * @param array<array-key, bool|int|string|array<string, bool|string>> $decoded
     */
    public function __construct(
        private readonly string $expectedData,
        private readonly array $expectedContext,
        private readonly array $decoded
    ) {
    }

    /**
     * @param array<string, bool|int> $context
     *
     * @return array<array-key, bool|int|string|array<string, bool|string>>
     *
     * @psalm-suppress PossiblyUnusedMethod invoked by production code through a concrete serializer
     */
    public function decode(string $data, string $format, array $context = []): array
    {
        Assert::assertSame($this->expectedData, $data);
        Assert::assertSame(JsonEncoder::FORMAT, $format);
        Assert::assertSame($this->expectedContext, $context);

        return $this->decoded;
    }

    /**
     * @param array<string, mixed> $context
     */
    #[\Override]
    public function serialize(mixed $data, string $format, array $context = []): string
    {
        throw new \BadMethodCallException('Serialization is not used by this test double.');
    }

    /**
     * @param array<string, mixed> $context
     */
    #[\Override]
    public function deserialize(
        mixed $data,
        string $type,
        string $format,
        array $context = []
    ): mixed {
        throw new \BadMethodCallException(
            'Deserialization is not used by this test double.'
        );
    }
}
