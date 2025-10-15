<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Http;

use App\Shared\Application\Http\RouteIdentifierProvider;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class RouteIdentifierProviderTest extends UnitTestCase
{
    public function testIdentifierTrimsAttributeValue(): void
    {
        $request = new Request();
        $request->attributes->set('identifier', '  abc-123  ');

        $stack = new RequestStack();
        $stack->push($request);

        $provider = new RouteIdentifierProvider($stack);

        self::assertSame('abc-123', $provider->identifier('identifier'));
    }

    public function testReturnsNullWhenAttributeIsNotString(): void
    {
        $request = new Request();
        $request->attributes->set('identifier', 42);

        $stack = new RequestStack();
        $stack->push($request);

        $provider = new RouteIdentifierProvider($stack);

        self::assertNull($provider->identifier('identifier'));
    }
}
