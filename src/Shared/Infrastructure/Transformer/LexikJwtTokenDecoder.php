<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Transformer;

use App\Shared\Application\Decoder\JwtTokenDecoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;

final readonly class LexikJwtTokenDecoder implements JwtTokenDecoderInterface
{
    public function __construct(private JWTEncoderInterface $jwtEncoder)
    {
    }

    /**
     * @return array<string, array<int, string>|bool|float|int|string|null>|null
     */
    #[\Override]
    public function decode(string $token): ?array
    {
        try {
            $payload = $this->jwtEncoder->decode($token);
        } catch (JWTDecodeFailureException) {
            return null;
        }

        return is_array($payload) ? $payload : null;
    }
}
