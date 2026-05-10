<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

final class Uuid implements UuidInterface
{
    private string $uid;

    public function __construct(string $uid)
    {
        $this->uid = $uid;
    }

    public function __toString(): string
    {
        return $this->uid;
    }

    #[\Override]
    public function toBinary(): ?string
    {
        $normalized = str_replace('-', '', $this->uid);
        $length = strlen($normalized);

        // Validate even length required for hex2bin
        if ($length === 0 || $length % 2 !== 0) {
            return null;
        }

        if (!ctype_xdigit($normalized)) {
            return null;
        }

        $binary = hex2bin($normalized);

        return $binary !== false ? $binary : null;
    }
}
