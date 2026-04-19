<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Runtime;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;

interface TestKernelInterface extends HttpKernelInterface, TerminableInterface
{
}
