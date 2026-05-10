<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext\Input;

/**
 * @psalm-api
 */
final class RawBodyInput extends RequestInput
{
    public function __construct(
        private readonly string $rawBody,
        private readonly ?string $contentType = null,
    ) {
    }

    public function getRawBody(): string
    {
        return $this->rawBody;
    }

    public function getContentType(): ?string
    {
        return $this->contentType;
    }

    /**
     * @return array<string, string|null>
     */
    #[\Override]
    public function toArray(): array
    {
        return ['_raw' => $this->rawBody];
    }
}
