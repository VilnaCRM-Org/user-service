<?php

declare(strict_types=1);

namespace App\User\Application\Passkey;

use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;

interface PasskeyJsonCodecInterface
{
    /**
     * @return array<string, scalar|array|null>
     */
    public function optionsToArray(
        PublicKeyCredentialCreationOptions|PublicKeyCredentialRequestOptions $options
    ): array;

    public function encodeOptions(
        PublicKeyCredentialCreationOptions|PublicKeyCredentialRequestOptions $options
    ): string;

    public function decodeCreationOptions(string $json): PublicKeyCredentialCreationOptions;

    public function decodeRequestOptions(string $json): PublicKeyCredentialRequestOptions;

    /**
     * @param array<string, scalar|array|null> $credential
     */
    public function decodeCredential(array $credential): PublicKeyCredential;

    public function encodeCredentialRecord(CredentialRecord $credentialRecord): string;

    public function decodeCredentialRecord(string $json): CredentialRecord;
}
