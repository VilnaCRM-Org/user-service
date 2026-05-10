<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Resolver;

use App\User\Infrastructure\Converter\SchemathesisPayloadConverter;
use Symfony\Component\HttpFoundation\Request;

final readonly class SchemathesisEmailResolver
{
    public function __construct(
        private SchemathesisCleanupResolver $cleanupResolver,
        private SchemathesisPayloadConverter $payloadConverter,
        private SchemathesisSingleUserEmailResolver $singleUserEmailResolver,
        private SchemathesisBatchUsersEmailResolver $batchUsersEmailResolver
    ) {
    }

    /**
     * @return list<string>
     */
    public function extract(Request $request): array
    {
        $payload = $this->payloadConverter->decode($request);

        if ($payload === []) {
            return [];
        }

        return $this->extractFromPayload($request, $payload);
    }

    /**
     * @param array<string, array|scalar|null> $payload
     *
     * @return array<string>
     *
     * @psalm-return list<string>
     */
    private function extractFromPayload(
        Request $request,
        array $payload
    ): array {
        return $this->cleanupResolver->isSingleUserPath($request)
            ? $this->singleUserEmailResolver->extract($payload)
            : $this->batchUsersEmailResolver->extract($payload);
    }
}
