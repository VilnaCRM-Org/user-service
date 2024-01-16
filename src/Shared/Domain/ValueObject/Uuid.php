<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

class Uuid implements UuidInterface
{
    private string $uid;

    public function __construct(string $uid)
    {
        $this->uid = $uid;
    }

    public function toBinary(): string
    {
        return hex2bin(str_replace('-', '', $this->uid));
    }

    public function __toString(): string
    {
        return $this->uid;
    }
}
