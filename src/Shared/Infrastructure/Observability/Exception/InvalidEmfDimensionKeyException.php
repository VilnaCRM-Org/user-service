<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Exception;

/**
 * Exception thrown when an EMF dimension key is invalid.
 */
final class InvalidEmfDimensionKeyException extends \InvalidArgumentException
{
}
