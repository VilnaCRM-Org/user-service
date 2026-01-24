<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Bus\Event\Async;

use Symfony\Component\Messenger\Exception\ExceptionInterface;

final class TestMessengerException extends \RuntimeException implements ExceptionInterface
{
}
