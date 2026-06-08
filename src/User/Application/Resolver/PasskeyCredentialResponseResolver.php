<?php

declare(strict_types=1);

namespace App\User\Application\Resolver;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final readonly class PasskeyCredentialResponseResolver
{
    private const ASSERTION_RESPONSE_CLASS = 'Webauthn\\AuthenticatorAssertionResponse';
    private const ATTESTATION_RESPONSE_CLASS = 'Webauthn\\AuthenticatorAttestationResponse';

    public function resolveAttestation(object $credential): object
    {
        $response = $this->resolveResponse($credential);

        if (!is_a($response, self::ATTESTATION_RESPONSE_CLASS)) {
            throw new BadRequestHttpException('Passkey attestation response is required.');
        }

        return $response;
    }

    public function resolveAssertion(object $credential): object
    {
        $response = $this->resolveResponse($credential);

        if (!is_a($response, self::ASSERTION_RESPONSE_CLASS)) {
            throw new BadRequestHttpException('Passkey assertion response is required.');
        }

        return $response;
    }

    private function resolveResponse(object $credential): object
    {
        $response = $credential->response ?? null;

        if (!is_object($response)) {
            throw new BadRequestHttpException('Invalid passkey credential response.');
        }

        return $response;
    }
}
