<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext\Input;

abstract class RequestInput
{
    /**
     * @return array<string, array|bool|float|int|object|string|null>
     */
    public function toArray(): array
    {
        $reflection = new \ReflectionObject($this);
        $values = [];

        foreach ($reflection->getProperties() as $property) {
            if (!$property->isPublic()) {
                /** @psalm-suppress UnusedMethodCall */
                $property->setAccessible(true);
            }
            $values[$property->getName()] = $property->getValue($this);
        }

        return $values;
    }
}
