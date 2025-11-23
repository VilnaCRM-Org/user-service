<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Schemathesis;

use Symfony\Component\HttpFoundation\Request;

final class SchemathesisEmailExtractor
{
    private readonly SchemathesisCleanupEvaluator $evaluator;
    private readonly SchemathesisPayloadDecoder $payloadDecoder;
    private readonly SchemathesisSingleUserEmailExtractor $singleUserExtractor;
    private readonly SchemathesisBatchUsersEmailExtractor $batchUsersExtractor;

    public function __construct(
        SchemathesisCleanupEvaluator $evaluator,
        SchemathesisPayloadDecoder $payloadDecoder,
        SchemathesisSingleUserEmailExtractor $singleUserExtractor,
        SchemathesisBatchUsersEmailExtractor $batchUsersExtractor
    ) {
        $this->evaluator = $evaluator;
        $this->payloadDecoder = $payloadDecoder;
        $this->singleUserExtractor = $singleUserExtractor;
        $this->batchUsersExtractor = $batchUsersExtractor;
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
     * @return array<string>
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
