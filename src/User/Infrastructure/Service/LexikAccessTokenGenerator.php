<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Service;

use App\User\Domain\Contract\AccessTokenGeneratorInterface;
use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class LexikAccessTokenGenerator implements
    AccessTokenGeneratorInterface
{
    public function __construct(
        #[Autowire(service: 'lexik_jwt_authentication.encoder')]
        private mixed $jwtEncoder
    ) {
    }

    #[\Override]
    public function generate(array $payload): string
    {
        if (!is_object($this->jwtEncoder)) {
            throw new RuntimeException('JWT encoder service must be an object.');
        }

        $encode = [$this->jwtEncoder, 'encode'];

        if (!is_callable($encode)) {
            throw new RuntimeException('JWT encoder service does not expose encode().');
        }

        $token = call_user_func($encode, $this->normalizePayload($payload));

        if (!is_string($token)) {
            throw new RuntimeException('JWT encoder service returned an invalid token.');
        }

        return $token;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function normalizePayload(array $payload): array
    {
        foreach (['iat', 'nbf', 'exp'] as $claim) {
            $value = $payload[$claim] ?? null;

            if (!is_int($value)) {
                continue;
            }

            $payload[$claim] = (new DateTimeImmutable(sprintf('@%d', $value)))
                ->setTimezone(new DateTimeZone('UTC'));
        }

        return $payload;
    }
}
