<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\DTO\VerifiedPasskeyCredential;
use App\User\Application\Transformer\PasskeyEncodingTransformer;
use App\User\Application\Transformer\PasskeyJsonTransformerInterface;

/**
 * @psalm-api
 */
final readonly class PasskeyVerifiedCredentialFactory
{
    public function __construct(
        private PasskeyEncodingTransformer $encoding,
        private PasskeyJsonTransformerInterface $jsonTransformer
    ) {
    }

    public function create(object $credentialRecord): VerifiedPasskeyCredential
    {
        return new VerifiedPasskeyCredential(
            $this->encoding->encode($credentialRecord->publicKeyCredentialId),
            $this->jsonTransformer->encodeCredentialRecord($credentialRecord)
        );
    }
}
