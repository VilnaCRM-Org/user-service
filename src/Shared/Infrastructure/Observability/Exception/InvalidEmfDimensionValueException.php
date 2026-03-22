<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Exception;

/**
 * Exception thrown when an EMF dimension value is invalid.
 */
final class InvalidEmfDimensionValueException extends \InvalidArgumentException
{
}
