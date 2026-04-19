<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Runtime\Reader;

use App\Shared\Infrastructure\Runtime\Reader\FrankenPhpRequestGlobalsReader;
use App\Tests\Unit\UnitTestCase;

final class FrankenPhpRequestGlobalsReaderTest extends UnitTestCase
{
    public function testReadRequestUsesInjectedGlobals(): void
    {
        $email = strtolower($this->faker->safeEmail());
        $page = (string) $this->faker->numberBetween(2, 9);
        $session = $this->faker->sha1();

        $reader = new FrankenPhpRequestGlobalsReader(
            query: ['page' => $page],
            request: ['email' => $email],
            cookies: ['session' => $session],
            server: [
                'REQUEST_METHOD' => 'POST',
                'CONTENT_TYPE' => 'application/json',
            ],
            content: sprintf('{"email":"%s"}', $email),
        );

        $request = $reader->readRequest();

        self::assertSame($page, $request->query->get('page'));
        self::assertSame($email, $request->request->get('email'));
        self::assertSame($session, $request->cookies->get('session'));
        self::assertSame('POST', $request->getMethod());
        self::assertSame(sprintf('{"email":"%s"}', $email), $request->getContent());
    }

    public function testReadRequestUsesInjectedContentReaderWhenContentIsMissing(): void
    {
        $email = strtolower($this->faker->safeEmail());
        $reader = new FrankenPhpRequestGlobalsReader(
            request: ['email' => $email],
            contentReader: static fn (): string => sprintf('{"email":"%s"}', $email),
        );

        $request = $reader->readRequest();

        self::assertSame($email, $request->request->get('email'));
        self::assertSame(sprintf('{"email":"%s"}', $email), $request->getContent());
    }
}
