<?php

declare(strict_types=1);

namespace App\User\Application\Passkey;

use function base64_decode;
use function base64_encode;

use InvalidArgumentException;

use function rtrim;
use function strtr;

final readonly class PasskeyEncoding
{
    public function encode(string $rawValue): string
    {
        return rtrim(strtr(base64_encode($rawValue), '+/', '-_'), '=');
    }

    public function decode(string $encodedValue): string
    {
        $decodedValue = base64_decode(strtr($encodedValue, '-_', '+/'), true);

        if ($decodedValue === false) {
            throw new InvalidArgumentException('Invalid base64url value.');
        }

        return $decodedValue;
    }
}
