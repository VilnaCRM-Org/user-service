<?php

declare(strict_types=1);

namespace App\User\Application\Resolver;

use Symfony\Component\HttpFoundation\Request;

interface HttpRequestContextResolverInterface
{
    public function resolveRequest(mixed $contextRequest): ?Request;

    public function resolveIpAddress(?Request $request): string;

    public function resolveUserAgent(?Request $request): string;
}
