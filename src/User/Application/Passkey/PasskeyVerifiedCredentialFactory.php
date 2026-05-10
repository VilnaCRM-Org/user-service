<?php

declare(strict_types=1);

namespace App\User\Application\Passkey;

use App\User\Application\DTO\VerifiedPasskeyCredential;
use Webauthn\CredentialRecord;

/**
 * @psalm-api
 */
final readonly class PasskeyVerifiedCredentialFactory
{
    public function __construct(
        private PasskeyEncoding $encoding,
        private PasskeyJsonCodecInterface $jsonCodec
    ) {
    }

    public function create(CredentialRecord $credentialRecord): VerifiedPasskeyCredential
    {
        return new VerifiedPasskeyCredential(
            $this->encoding->encode($credentialRecord->publicKeyCredentialId),
            $this->jsonCodec->encodeCredentialRecord($credentialRecord)
        );
    }
}
