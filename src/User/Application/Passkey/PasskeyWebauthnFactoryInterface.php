<?php

declare(strict_types=1);

namespace App\User\Application\Passkey;

use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponseValidator;

interface PasskeyWebauthnFactoryInterface
{
    public function createSerializer(): SerializerInterface;

    public function createAttestationValidator(): AuthenticatorAttestationResponseValidator;

    public function createAssertionValidator(): AuthenticatorAssertionResponseValidator;
}
