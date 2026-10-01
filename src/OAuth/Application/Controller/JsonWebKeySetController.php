<?php

declare(strict_types=1);

namespace App\OAuth\Application\Controller;

use App\Shared\Application\Provider\JwtVerificationKeyProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * GET /api/.well-known/jwks.json: the public JWT keys from KMS GetPublicKey
 * (the current key, then the previous key during a key-change window).
 * SecurityHeadersResponseListener sets Cache-Control: no-store on it, like
 * every response (NFR-66).
 */
#[AsController]
final readonly class JsonWebKeySetController
{
    public function __construct(private JwtVerificationKeyProviderInterface $keyProvider)
    {
    }

    public function __invoke(): JsonResponse
    {
        $keys = [];
        foreach ($this->keyProvider->verificationKeys() as $key) {
            $keys[] = $key->toJwk();
        }

        return new JsonResponse(['keys' => $keys]);
    }
}
