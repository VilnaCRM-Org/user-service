<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Runtime\Reader;

use Closure;
use Symfony\Component\HttpFoundation\Request;

final class FrankenPhpRequestGlobalsReader implements FrankenPhpRequestGlobalsReaderInterface
{
    /**
     * @param array<string, mixed>|null $query
     * @param array<string, mixed>|null $request
     * @param array<string, mixed>|null $cookies
     * @param array<string, mixed>|null $server
     */
    public function __construct(
        private readonly ?array $query = null,
        private readonly ?array $request = null,
        private readonly ?array $cookies = null,
        private readonly ?array $server = null,
        private readonly ?string $content = null,
        private readonly ?Closure $contentReader = null,
    ) {
    }

    #[\Override]
    public function readRequest(): Request
    {
        return new Request(
            $this->resolveQuery(),
            $this->resolveRequest(),
            [],
            $this->resolveCookies(),
            $_FILES,
            $this->resolveServer(),
            $this->resolveContent(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveQuery(): array
    {
        return $this->query ?? $_GET;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveRequest(): array
    {
        return $this->request ?? $_POST;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveCookies(): array
    {
        return $this->cookies ?? $_COOKIE;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveServer(): array
    {
        return $this->server ?? $_SERVER;
    }

    private function resolveContent(): string
    {
        if ($this->content !== null) {
            return $this->content;
        }

        $content = $this->contentReader === null
            ? file_get_contents('php://input')
            : ($this->contentReader)();

        return is_string($content) ? $content : '';
    }
}
