<?php

declare(strict_types=1);

namespace App\Tests\Unit\Behat\UserContext;

use App\Tests\Behat\UserContext\UserOperationsState;
use App\Tests\Behat\UserContext\UserResponseContext;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\ExpectationFailedException;
use Symfony\Component\HttpFoundation\Response;

final class UserResponseContextTest extends UnitTestCase
{
    public function testJsonFieldAssertionIgnoresTokenText(): void
    {
        $state = new UserOperationsState();
        $state->response = new Response('{"refresh_token":"synthetic_id_marker"}');

        (new UserResponseContext($state))
            ->theResponseJsonShouldNotHaveField('_id');
    }

    public function testFailedTextAssertionsDoNotExposeResponseValues(): void
    {
        $state = new UserOperationsState();
        $state->response = new Response('{"refresh_token":"synthetic_id_marker"}');
        $context = new UserResponseContext($state);

        foreach (
            [
                static fn () => $context->theResponseShouldNotContain('_id'),
                static fn () => $context->theResponseShouldContain('missing'),
                static fn () => $context->theResponseBodyShouldContain('missing'),
            ] as $assertion
        ) {
            try {
                $assertion();
                self::fail('Expected the response assertion to fail.');
            } catch (ExpectationFailedException $exception) {
                self::assertStringNotContainsString(
                    'synthetic_id_marker',
                    $exception->getMessage()
                );
            }
        }
    }
}
