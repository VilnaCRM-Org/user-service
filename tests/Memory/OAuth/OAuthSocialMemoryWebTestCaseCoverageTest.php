<?php

declare(strict_types=1);

namespace App\Tests\Memory\OAuth;

use PHPUnit\Framework\Attributes\Group;

#[Group('memory')]
#[Group('memory-oauth')]
final class OAuthSocialMemoryWebTestCaseCoverageTest extends OAuthSocialMemoryWebTestCase
{
    public function testRunRepeatedOAuthScenarioRejectsNonPositiveIterations(): void
    {
        $runRepeatedOAuthScenario = \Closure::bind(
            function (callable $scenario, int $iterations): void {
                $this->runRepeatedOAuthScenario('oauth-social-coverage', $scenario, $iterations);
            },
            $this,
            OAuthSocialMemoryWebTestCase::class,
        );

        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Iterations must be greater than zero.');

        $runRepeatedOAuthScenario(static function (): void {
        }, 0);
    }
}
