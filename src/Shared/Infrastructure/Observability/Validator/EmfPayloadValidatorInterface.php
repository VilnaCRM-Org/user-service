<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability\Validator;

use App\Shared\Infrastructure\Observability\ValueObject\EmfPayload;

interface EmfPayloadValidatorInterface
{
    /**
     * Validates that the EMF payload has no key collisions between dimensions and metrics
     *
     * @throws \App\Shared\Infrastructure\Observability\Exception\EmfKeyCollisionException
     */
    public function validate(EmfPayload $payload): void;
}
