<?php

declare(strict_types=1);

namespace App\User\Application\Validator;

use App\User\Application\DTO\PasskeyConfiguration;
use App\User\Application\Factory\PasskeyWebauthnFactoryInterface;
use App\User\Application\Resolver\PasskeyCredentialResponseResolver;
use App\User\Application\Transformer\PasskeyJsonTransformerInterface;
use App\User\Domain\Entity\PasskeyChallenge;
use LogicException;

/**
 * @psalm-api
 */
final readonly class PasskeyAttestationCredentialRecordValidator
{
    public function __construct(
        private PasskeyJsonTransformerInterface $jsonTransformer,
        private PasskeyWebauthnFactoryInterface $webauthnFactory,
        private PasskeyConfiguration $configuration,
        private PasskeyCredentialResponseResolver $responseResolver
    ) {
    }

    /**
     * @param array<string, scalar|array|null> $credential
     */
    public function verify(PasskeyChallenge $challenge, array $credential): object
    {
        $publicKeyCredential = $this->jsonTransformer->decodeCredential($credential);

        return $this->checkCredentialRecord(
            $this->webauthnFactory->createAttestationValidator(),
            $this->responseResolver->resolveAttestation($publicKeyCredential),
            $this->jsonTransformer->decodeCreationOptions($challenge->getOptions())
        );
    }

    private function checkCredentialRecord(
        object $validator,
        object $response,
        object $options
    ): object {
        $check = [$validator, 'check'];
        if (!is_callable($check)) {
            throw new LogicException('Passkey attestation validator is invalid.');
        }

        $credentialRecord = $check($response, $options, $this->configuration->getRpId());
        if (!is_object($credentialRecord)) {
            throw new LogicException('Passkey attestation validator returned invalid record.');
        }

        return $credentialRecord;
    }
}
