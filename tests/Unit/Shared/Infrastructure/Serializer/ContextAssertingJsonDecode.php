<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Serializer;

use PHPUnit\Framework\Assert;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

final class ContextAssertingJsonDecode extends JsonDecode
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
        parent::__construct();
    }

    /**
     * @param array<string, bool|int> $context
     *
     * @return array<array-key, bool|int|string|array<string, bool|string>>
     */
    #[\Override]
    public function decode(string $data, string $format, array $context = []): array
    {
        Assert::assertSame($this->expectedData, $data);
        Assert::assertSame(JsonEncoder::FORMAT, $format);
        Assert::assertSame($this->expectedContext, $context);

        return $this->decoded;
    }
}
