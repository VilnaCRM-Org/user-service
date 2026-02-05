<?php

declare(strict_types=1);

namespace App\Tests\Behat\OAuthContext\Input;

abstract class ObtainAccessTokenInput implements \JsonSerializable
{
    public function __construct(private ?string $grant_type = null)
    {
    }

    public function setGrantType(?string $grantType): void
    {
        $this->grant_type = $grantType;
    }

    public function getGrantType(): ?string
    {
        return $this->grant_type;
    }

    /**
     * @return array<string, array|bool|float|int|object|string|null>
     */
    public function toArray(): array
    {
        $reflection = new \ReflectionObject($this);
        $values = [];

        foreach ($reflection->getProperties() as $property) {
            $values[$property->getName()] = $property->getValue($this);
        }

        return $values;
    }

    /**
     * @return array<string, array|bool|float|int|object|string|null>
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
