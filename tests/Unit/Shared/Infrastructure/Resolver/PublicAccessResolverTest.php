<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Resolver;

use App\Shared\Infrastructure\Resolver\PublicAccessResolver;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

final class PublicAccessResolverTest extends UnitTestCase
{
    public function testIsPublicReturnsFalseWhenNoRulesMatch(): void
    {
        $publicAccessResolver = new PublicAccessResolver([
            ['pattern' => '#^/api/private#'],
        ]);

        $request = Request::create('/api/public/resource');

        $this->assertFalse($publicAccessResolver->isPublic($request));
    }

    public function testIsPublicReturnsTrueWhenPatternMatchesAndNoMethodsRestriction(): void
    {
        $publicAccessResolver = new PublicAccessResolver([
            ['pattern' => '#^/api/public#'],
        ]);

        $request = Request::create('/api/public/resource', 'GET');

        $this->assertTrue($publicAccessResolver->isPublic($request));
    }

    public function testIsPublicReturnsTrueWhenPatternMatchesAndMethodsArrayIsEmpty(): void
    {
        $publicAccessResolver = new PublicAccessResolver([
            ['pattern' => '#^/api/public#', 'methods' => []],
        ]);

        $request = Request::create('/api/public/resource', 'POST');

        $this->assertTrue($publicAccessResolver->isPublic($request));
    }

    public function testIsPublicReturnsTrueWhenPatternMatchesAndMethodMatches(): void
    {
        $publicAccessResolver = new PublicAccessResolver([
            ['pattern' => '#^/api/public#', 'methods' => ['GET', 'POST']],
        ]);

        $request = Request::create('/api/public/resource', 'GET');

        $this->assertTrue($publicAccessResolver->isPublic($request));
    }

    public function testIsPublicReturnsTrueWhenPatternMatchesAndSecondMethodMatches(): void
    {
        $publicAccessResolver = new PublicAccessResolver([
            ['pattern' => '#^/api/public#', 'methods' => ['GET', 'POST']],
        ]);

        $request = Request::create('/api/public/resource', 'POST');

        $this->assertTrue($publicAccessResolver->isPublic($request));
    }

    public function testIsPublicReturnsFalseWhenPatternMatchesButMethodDoesNotMatch(): void
    {
        $publicAccessResolver = new PublicAccessResolver([
            ['pattern' => '#^/api/public#', 'methods' => ['GET']],
        ]);

        $request = Request::create('/api/public/resource', 'POST');

        $this->assertFalse($publicAccessResolver->isPublic($request));
    }

    public function testIsPublicReturnsFalseWhenNoRules(): void
    {
        $publicAccessResolver = new PublicAccessResolver([]);

        $request = Request::create('/api/any/path', 'GET');

        $this->assertFalse($publicAccessResolver->isPublic($request));
    }

    public function testIsPublicSkipsNonMatchingRulesAndMatchesSubsequentRule(): void
    {
        $publicAccessResolver = new PublicAccessResolver([
            ['pattern' => '#^/api/private#'],
            ['pattern' => '#^/api/public#'],
        ]);

        $request = Request::create('/api/public/resource', 'GET');

        $this->assertTrue($publicAccessResolver->isPublic($request));
    }

    public function testIsPublicReturnsFalseWhenMethodExcludedAndNoOtherRuleMatches(
    ): void {
        $publicAccessResolver = new PublicAccessResolver([
            ['pattern' => '#^/api/public#', 'methods' => ['DELETE']],
            ['pattern' => '#^/api/private#'],
        ]);

        $request = Request::create('/api/public/resource', 'GET');

        $this->assertFalse($publicAccessResolver->isPublic($request));
    }
}
