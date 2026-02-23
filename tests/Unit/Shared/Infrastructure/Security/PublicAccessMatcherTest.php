<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Security;

use App\Shared\Infrastructure\Security\PublicAccessMatcher;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

final class PublicAccessMatcherTest extends UnitTestCase
{
    public function testIsPublicReturnsFalseWhenNoRulesMatch(): void
    {
        $matcher = new PublicAccessMatcher([
            ['pattern' => '#^/api/private#'],
        ]);

        $request = Request::create('/api/public/resource');

        $this->assertFalse($matcher->isPublic($request));
    }

    public function testIsPublicReturnsTrueWhenPatternMatchesAndNoMethodsRestriction(): void
    {
        $matcher = new PublicAccessMatcher([
            ['pattern' => '#^/api/public#'],
        ]);

        $request = Request::create('/api/public/resource', 'GET');

        $this->assertTrue($matcher->isPublic($request));
    }

    public function testIsPublicReturnsTrueWhenPatternMatchesAndMethodsArrayIsEmpty(): void
    {
        $matcher = new PublicAccessMatcher([
            ['pattern' => '#^/api/public#', 'methods' => []],
        ]);

        $request = Request::create('/api/public/resource', 'POST');

        $this->assertTrue($matcher->isPublic($request));
    }

    public function testIsPublicReturnsTrueWhenPatternMatchesAndMethodMatches(): void
    {
        $matcher = new PublicAccessMatcher([
            ['pattern' => '#^/api/public#', 'methods' => ['GET', 'POST']],
        ]);

        $request = Request::create('/api/public/resource', 'GET');

        $this->assertTrue($matcher->isPublic($request));
    }

    public function testIsPublicReturnsTrueWhenPatternMatchesAndSecondMethodMatches(): void
    {
        $matcher = new PublicAccessMatcher([
            ['pattern' => '#^/api/public#', 'methods' => ['GET', 'POST']],
        ]);

        $request = Request::create('/api/public/resource', 'POST');

        $this->assertTrue($matcher->isPublic($request));
    }

    public function testIsPublicReturnsFalseWhenPatternMatchesButMethodDoesNotMatch(): void
    {
        $matcher = new PublicAccessMatcher([
            ['pattern' => '#^/api/public#', 'methods' => ['GET']],
        ]);

        $request = Request::create('/api/public/resource', 'POST');

        $this->assertFalse($matcher->isPublic($request));
    }

    public function testIsPublicReturnsFalseWhenNoRules(): void
    {
        $matcher = new PublicAccessMatcher([]);

        $request = Request::create('/api/any/path', 'GET');

        $this->assertFalse($matcher->isPublic($request));
    }

    public function testIsPublicSkipsNonMatchingRulesAndMatchesSubsequentRule(): void
    {
        $matcher = new PublicAccessMatcher([
            ['pattern' => '#^/api/private#'],
            ['pattern' => '#^/api/public#'],
        ]);

        $request = Request::create('/api/public/resource', 'GET');

        $this->assertTrue($matcher->isPublic($request));
    }

    public function testIsPublicReturnsFalseWhenMethodExcludedAndNoOtherRuleMatches(
    ): void {
        $matcher = new PublicAccessMatcher([
            ['pattern' => '#^/api/public#', 'methods' => ['DELETE']],
            ['pattern' => '#^/api/private#'],
        ]);

        $request = Request::create('/api/public/resource', 'GET');

        $this->assertFalse($matcher->isPublic($request));
    }
}
