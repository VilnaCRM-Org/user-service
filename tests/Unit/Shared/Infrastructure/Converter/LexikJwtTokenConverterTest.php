<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Converter;

use App\Shared\Infrastructure\Converter\LexikJwtTokenConverter;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use PHPUnit\Framework\TestCase;

final class LexikJwtTokenConverterTest extends TestCase
{
    public function testReturnsPayloadArrayOnSuccessfulDecode(): void
    {
        $payload = ['sub' => 'user-123', 'iss' => 'vilnacrm-user-service'];

        $jwtEncoder = $this->createMock(JWTEncoderInterface::class);
        $jwtEncoder->method('decode')->willReturn($payload);

        $converter = new LexikJwtTokenConverter($jwtEncoder);

        self::assertSame($payload, $converter->decode('valid.token'));
    }

    public function testReturnsNullWhenDecodeThrowsException(): void
    {
        $jwtEncoder = $this->createMock(JWTEncoderInterface::class);
        $jwtEncoder->method('decode')->willThrowException(
            new JWTDecodeFailureException(JWTDecodeFailureException::INVALID_TOKEN, 'Invalid token')
        );

        $converter = new LexikJwtTokenConverter($jwtEncoder);

        self::assertNull($converter->decode('invalid.token'));
    }

    public function testReturnsNullWhenDecodeReturnsNonArray(): void
    {
        $jwtEncoder = $this->createMock(JWTEncoderInterface::class);
        $jwtEncoder->method('decode')->willReturn(false);

        $converter = new LexikJwtTokenConverter($jwtEncoder);

        self::assertNull($converter->decode('token'));
    }
}
