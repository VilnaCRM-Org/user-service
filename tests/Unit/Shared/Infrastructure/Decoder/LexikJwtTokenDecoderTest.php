<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Decoder;

use App\Shared\Infrastructure\Decoder\LexikJwtTokenDecoder;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use PHPUnit\Framework\TestCase;

final class LexikJwtTokenDecoderTest extends TestCase
{
    public function testReturnsPayloadArrayOnSuccessfulDecode(): void
    {
        $payload = ['sub' => 'user-123', 'iss' => 'vilnacrm-user-service'];

        $jwtEncoder = $this->createMock(JWTEncoderInterface::class);
        $jwtEncoder->method('decode')->willReturn($payload);

        $decoder = new LexikJwtTokenDecoder($jwtEncoder);

        self::assertSame($payload, $decoder->decode('valid.token'));
    }

    public function testReturnsNullWhenDecodeThrowsException(): void
    {
        $jwtEncoder = $this->createMock(JWTEncoderInterface::class);
        $jwtEncoder->method('decode')->willThrowException(
            new JWTDecodeFailureException(JWTDecodeFailureException::INVALID_TOKEN, 'Invalid token')
        );

        $decoder = new LexikJwtTokenDecoder($jwtEncoder);

        self::assertNull($decoder->decode('invalid.token'));
    }

    public function testReturnsNullWhenDecodeReturnsNonArray(): void
    {
        $jwtEncoder = $this->createMock(JWTEncoderInterface::class);
        $jwtEncoder->method('decode')->willReturn(false);

        $decoder = new LexikJwtTokenDecoder($jwtEncoder);

        self::assertNull($decoder->decode('token'));
    }
}
