<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext\Input;

abstract class RequestInput
{
    public function getJson(): string
    {
        $payload = array_filter(
            $this->toArray(),
            static fn (array|bool|float|int|object|string|null $value): bool => $value !== null
        );

        return json_encode((object) $payload, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, array|bool|float|int|object|string|null>
     */
    public function toArray(): array
    {
        $reflection = new \ReflectionObject($this);
        $values = [];

        foreach ($reflection->getProperties() as $property) {
            if (!$property->isInitialized($this)) {
                $values[$property->getName()] = null;
                continue;
            }

            $values[$property->getName()] = $property->getValue($this);
        }

        return $values;
    }
}
