<?php

declare(strict_types=1);

namespace App\User\Application\Passkey;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\PublicKeyCredential;

final readonly class PasskeyCredentialResponseResolver
{
    public function resolveAttestation(
        PublicKeyCredential $credential
    ): AuthenticatorAttestationResponse {
        if (!$credential->response instanceof AuthenticatorAttestationResponse) {
            throw new BadRequestHttpException('Passkey attestation response is required.');
        }

        return $credential->response;
    }

    public function resolveAssertion(
        PublicKeyCredential $credential
    ): AuthenticatorAssertionResponse {
        if (!$credential->response instanceof AuthenticatorAssertionResponse) {
            throw new BadRequestHttpException('Passkey assertion response is required.');
        }

        return $credential->response;
    }
}
