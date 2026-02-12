<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Extractor;

use App\User\Infrastructure\Decoder\SchemathesisPayloadDecoder;
use App\User\Infrastructure\Evaluator\SchemathesisCleanupEvaluator;
use Symfony\Component\HttpFoundation\Request;

final readonly class SchemathesisEmailExtractor
{
    public function __construct(
        private SchemathesisCleanupEvaluator $evaluator,
        private SchemathesisPayloadDecoder $payloadDecoder,
        private SchemathesisSingleUserEmailExtractor $singleUserExtractor,
        private SchemathesisBatchUsersEmailExtractor $batchUsersExtractor
    ) {
    }

    /**
     * @return list<string>
     */
    public function extract(Request $request): array
    {
        $payload = $this->payloadDecoder->decode($request);

        if ($payload === []) {
            return [];
        }

        return $this->extractFromPayload($request, $payload);
    }

    /**
     * @param array<string, array|scalar|null> $payload
     *
     * @return string[]
     *
     * @psalm-return list<string>
     */
    private function extractFromPayload(
        Request $request,
        array $payload
    ): array {
        return $this->evaluator->isSingleUserPath($request)
            ? $this->singleUserExtractor->extract($payload)
            : $this->batchUsersExtractor->extract($payload);
    }
}
