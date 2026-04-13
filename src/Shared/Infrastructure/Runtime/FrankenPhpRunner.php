<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Runtime;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
use Symfony\Component\Runtime\RunnerInterface;

final class FrankenPhpRunner implements RunnerInterface
{
    public function __construct(
        private readonly HttpKernelInterface $kernel,
        private readonly int $loopMax,
    ) {
    }

    public function run(): int
    {
        ignore_user_abort(true);

        $server = array_filter(
            $_SERVER,
            static fn (string $key): bool => !str_starts_with($key, 'HTTP_'),
            ARRAY_FILTER_USE_KEY,
        );
        $server['APP_RUNTIME_MODE'] = 'web=1&worker=1';

        $sfRequest = null;
        $sfResponse = null;
        $handler = function () use ($server, &$sfRequest, &$sfResponse): void {
            $_SERVER += $server;

            $sfRequest = $this->createRequestFromGlobals();
            $sfResponse = $this->kernel->handle($sfRequest);

            $sfResponse->send();
        };

        $loops = 0;

        do {
            $handled = frankenphp_handle_request($handler);

            if ($this->kernel instanceof TerminableInterface && $sfRequest && $sfResponse) {
                $this->kernel->terminate($sfRequest, $sfResponse);
            }

            gc_collect_cycles();
            gc_mem_caches();
        } while ($handled && (-1 === $this->loopMax || ++$loops <= $this->loopMax));

        return 0;
    }

    private function createRequestFromGlobals(): Request
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? null;
        if (!\in_array($method, ['PUT', 'DELETE', 'PATCH', 'QUERY'], true)) {
            return new Request($_GET, $_POST, [], $_COOKIE, $_FILES, $_SERVER);
        }

        if (\function_exists('request_parse_body')) {
            return $this->createParsedBodyRequest();
        }

        return $this->createLegacyParsedBodyRequest();
    }

    private function createParsedBodyRequest(): Request
    {
        try {
            [$post, $files] = request_parse_body();
        } catch (\RequestParseBodyException) {
            $post = $_POST;
            $files = $_FILES;
        }

        return new Request($_GET, $post, [], $_COOKIE, $files, $_SERVER);
    }

    private function createLegacyParsedBodyRequest(): Request
    {
        $content = null;
        $post = $_POST;
        if (!isset($_SERVER['CONTENT_TYPE']) || str_starts_with((string) $_SERVER['CONTENT_TYPE'], 'application/x-www-form-urlencoded')) {
            $content = file_get_contents('php://input');
            parse_str(\is_string($content) ? $content : '', $post);
        }

        return new Request($_GET, $post, [], $_COOKIE, $_FILES, $_SERVER, $content);
    }
}
