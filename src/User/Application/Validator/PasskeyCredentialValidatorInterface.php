<?php

declare(strict_types=1);

namespace App\User\Application\Validator;

use App\User\Application\DTO\VerifiedPasskeyCredential;
use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\Entity\PasskeyCredential;

interface PasskeyCredentialValidatorInterface
{
    /**
     * @param array<string, scalar|array|null> $credential
     */
    public function extractCredentialId(array $credential): string;

    /**
     * @param array<string, scalar|array|null> $credential
     */
    public function verifyAttestation(
        PasskeyChallenge $challenge,
        array $credential
    ): VerifiedPasskeyCredential;

    /**
     * @param array<string, scalar|array|null> $credential
     */
    public function verifyAssertion(
        PasskeyChallenge $challenge,
        array $credential,
        PasskeyCredential $storedCredential
    ): VerifiedPasskeyCredential;
}
