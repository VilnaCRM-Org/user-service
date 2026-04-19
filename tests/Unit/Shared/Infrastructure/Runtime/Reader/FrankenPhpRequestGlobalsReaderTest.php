<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Runtime\Reader;

use App\Shared\Infrastructure\Runtime\Reader\FrankenPhpRequestGlobalsReader;
use App\Tests\Unit\UnitTestCase;

final class FrankenPhpRequestGlobalsReaderTest extends UnitTestCase
{
    public function testReadRequestUsesInjectedRequestReader(): void
    {
        $email = strtolower($this->faker->safeEmail());
        $page = (string) $this->faker->numberBetween(2, 9);
        $session = $this->faker->sha1();

        $reader = new FrankenPhpRequestGlobalsReader(
            static fn (): \Symfony\Component\HttpFoundation\Request => \Symfony\Component\HttpFoundation\Request::create(
                '/api/users',
                \Symfony\Component\HttpFoundation\Request::METHOD_POST,
                ['email' => $email],
                ['session' => $session],
                [],
                [],
                sprintf('{"page":"%s"}', $page),
            ),
        );

        $request = $reader->readRequest();

        self::assertSame($email, $request->request->get('email'));
        self::assertSame($session, $request->cookies->get('session'));
        self::assertSame('POST', $request->getMethod());
        self::assertSame(sprintf('{"page":"%s"}', $page), $request->getContent());
    }

    public function testReadRequestUsesHttpFoundationGlobalsFactoryByDefault(): void
    {
        $email = strtolower($this->faker->safeEmail());
        $page = (string) $this->faker->numberBetween(2, 9);
        $session = $this->faker->sha1();

        $previousGet = $_GET;
        $previousPost = $_POST;
        $previousCookie = $_COOKIE;
        $previousServer = $_SERVER;

        $_GET = ['page' => $page];
        $_POST = ['email' => $email];
        $_COOKIE = ['session' => $session];
        $_SERVER['REQUEST_METHOD'] = 'POST';

        try {
            $request = (new FrankenPhpRequestGlobalsReader())->readRequest();
        } finally {
            $_GET = $previousGet;
            $_POST = $previousPost;
            $_COOKIE = $previousCookie;
            $_SERVER = $previousServer;
        }

        self::assertSame($page, $request->query->get('page'));
        self::assertSame($email, $request->request->get('email'));
        self::assertSame($session, $request->cookies->get('session'));
        self::assertSame('POST', $request->getMethod());
    }

    public function testReadRequestFallsBackToDefaultFactoryWhenReaderIsNull(): void
    {
        $email = strtolower($this->faker->safeEmail());
        $previousPost = $_POST;
        $previousServer = $_SERVER;

        $_POST = ['email' => $email];
        $_SERVER['REQUEST_METHOD'] = 'POST';

        try {
            $request = new FrankenPhpRequestGlobalsReader(null);
            $resolvedRequest = $request->readRequest();
        } finally {
            $_POST = $previousPost;
            $_SERVER = $previousServer;
        }

        self::assertSame($email, $resolvedRequest->request->get('email'));
    }
}
