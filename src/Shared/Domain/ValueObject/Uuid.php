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
        if (!$this->isConvertableToBinary($this->uid)) {
            return null;
        }

        $binary = hex2bin(str_replace('-', '', $this->uid));

        return $binary !== false ? $binary : null;
    }

    private function isConvertableToBinary(string $uid): bool
    {
        return strlen($uid) % 2 === 0;
    }
}
