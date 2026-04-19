<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Runtime\Reader;

use App\Shared\Infrastructure\Runtime\Reader\FrankenPhpRequestGlobalsReader;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

final class FrankenPhpRequestGlobalsReaderTest extends UnitTestCase
{
    public function testReadRequestUsesInjectedRequestReader(): void
    {
        $request = $this->createRequest();
        $reader = new FrankenPhpRequestGlobalsReader(static fn (): Request => $request);

        self::assertSame($request, $reader->readRequest());
    }

    public function testReadRequestUsesHttpFoundationGlobalsFactoryWhenReaderIsMissing(): void
    {
        $readers = [
            new FrankenPhpRequestGlobalsReader(),
            new FrankenPhpRequestGlobalsReader(null),
        ];

        foreach ($readers as $reader) {
            self::assertInstanceOf(Request::class, $reader->readRequest());
        }
    }

    private function createRequest(): Request
    {
        $email = strtolower($this->faker->safeEmail());
        $session = $this->faker->sha1();
        $page = (string) $this->faker->numberBetween(2, 9);

        return Request::create(
            '/api/users',
            Request::METHOD_POST,
            ['email' => $email],
            ['session' => $session],
            [],
            [],
            sprintf('{"page":"%s"}', $page),
        );
    }
}
