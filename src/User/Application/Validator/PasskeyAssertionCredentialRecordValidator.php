<?php

declare(strict_types=1);

namespace App\User\Application\Validator;

use App\User\Application\DTO\PasskeyConfiguration;
use App\User\Application\Factory\PasskeyWebauthnFactoryInterface;
use App\User\Application\Resolver\PasskeyCredentialResponseResolver;
use App\User\Application\Transformer\PasskeyJsonTransformerInterface;
use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\Entity\PasskeyCredential;
use LogicException;

/**
 * @psalm-api
 */
final readonly class PasskeyAssertionCredentialRecordValidator
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
    public function verify(
        PasskeyChallenge $challenge,
        PasskeyCredential $storedCredential,
        array $credential
    ): object {
        $publicKeyCredential = $this->jsonTransformer->decodeCredential($credential);

        return $this->verifyResponse(
            $challenge,
            $storedCredential,
            $this->responseResolver->resolveAssertion($publicKeyCredential)
        );
    }

    private function verifyResponse(
        PasskeyChallenge $challenge,
        PasskeyCredential $storedCredential,
        object $response
    ): object {
        $check = [$this->webauthnFactory->createAssertionValidator(), 'check'];
        if (!is_callable($check)) {
            throw new LogicException('Passkey assertion validator is invalid.');
        }

        $credentialRecord = $check(
            $this->jsonTransformer->decodeCredentialRecord(
                $storedCredential->getCredentialRecord()
            ),
            $response,
            $this->jsonTransformer->decodeRequestOptions($challenge->getOptions()),
            $this->configuration->getRpId(),
            $challenge->getUserId()
        );
        if (!is_object($credentialRecord)) {
            throw new LogicException('Passkey assertion validator returned invalid record.');
        }

        return $credentialRecord;
    }
}
