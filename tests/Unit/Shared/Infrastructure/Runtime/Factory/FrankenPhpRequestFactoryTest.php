<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Runtime\Factory;

use App\Shared\Infrastructure\Runtime\Factory\FrankenPhpRequestFactory;
use App\Shared\Infrastructure\Runtime\Factory\FrankenPhpRequestGlobalsReader;
use App\Shared\Infrastructure\Runtime\Factory\FrankenPhpRequestGlobalsReaderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class FrankenPhpRequestFactoryTest extends TestCase
{
    /**
     * @var array<string, mixed>
     */
    private array $originalServer = [];

    /**
     * @var array<string, mixed>
     */
    private array $originalGet = [];

    /**
     * @var array<string, mixed>
     */
    private array $originalPost = [];

    /**
     * @var array<string, mixed>
     */
    private array $originalCookie = [];

    /**
     * @var array<string, mixed>
     */
    private array $originalFiles = [];

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->originalServer = $_SERVER;
        $this->originalGet = $_GET;
        $this->originalPost = $_POST;
        $this->originalCookie = $_COOKIE;
        $this->originalFiles = $_FILES;
    }

    #[\Override]
    protected function tearDown(): void
    {
        $_SERVER = $this->originalServer;
        $_GET = $this->originalGet;
        $_POST = $this->originalPost;
        $_COOKIE = $this->originalCookie;
        $_FILES = $this->originalFiles;

        parent::tearDown();
    }

    public function testCreateBaseRequestDelegatesToInjectedGlobalsReader(): void
    {
        $request = Request::create('/runtime-factory', 'GET');
        $factory = new FrankenPhpRequestFactory(
            requestGlobalsReader: new class ($request) implements FrankenPhpRequestGlobalsReaderInterface {
                public function __construct(private readonly Request $request)
                {
                }

                public function readRequest(): Request
                {
                    return $this->request;
                }
            },
        );

        self::assertSame($request, $factory->createBaseRequest());
    }

    public function testDefaultGlobalsReaderBuildsRequestsFromSuperglobals(): void
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/runtime-factory?source=globals',
            'QUERY_STRING' => 'source=globals',
            'HTTP_HOST' => 'localhost',
        ];
        $_GET = ['source' => 'globals'];
        $_POST = [];
        $_COOKIE = [];
        $_FILES = [];

        $request = (new FrankenPhpRequestGlobalsReader())->readRequest();

        self::assertSame('GET', $request->getMethod());
        self::assertSame('/runtime-factory', $request->getPathInfo());
        self::assertSame('globals', $request->query->get('source'));
    }
}
