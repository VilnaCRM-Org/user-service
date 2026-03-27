<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Validator;

use App\Shared\Infrastructure\Validator\AccessTokenValidator;
use App\Tests\Unit\UnitTestCase;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

abstract class AccessTokenValidatorTestCase extends UnitTestCase
{
    protected JWTEncoderInterface&MockObject $jwtEncoder;
    protected AccessTokenValidator $validator;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->jwtEncoder = $this->createMock(JWTEncoderInterface::class);
        $this->validator = new AccessTokenValidator(
            $this->createJsonSerializer(),
            $this->jwtEncoder,
        );
    }

    protected function createValidToken(string $algorithm = 'RS256', int $parts = 3): string
    {
        $header = ['alg' => $algorithm, 'typ' => 'JWT'];
        $encodedHeader = $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR));
        $encodedPayload = $this->base64UrlEncode(
            json_encode(['sub' => $this->faker->email()], JSON_THROW_ON_ERROR)
        );

        $segments = array_fill(0, $parts, 'segment');
        $segments[0] = $encodedHeader;

        if ($parts >= 2) {
            $segments[1] = $encodedPayload;
        }

        return implode('.', $segments);
    }

    /**
     * @param array<string> $roles
     *
     * @return array<string, array<int|string>|int|string>
     */
    protected function buildPayload(string $subject, string $sid, array $roles): array
    {
        $now = time();

        return [
            'sub' => $subject,
            'iss' => 'vilnacrm-user-service',
            'aud' => 'vilnacrm-api',
            'nbf' => $now - 10,
            'iat' => $now - 10,
            'exp' => $now + 900,
            'sid' => $sid,
            'roles' => $roles,
        ];
    }

    protected function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    protected function expectInvalidTokenException(): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Invalid access token.');
    }

    protected function expectInvalidClaimsException(): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Invalid access token claims.');
    }
}
