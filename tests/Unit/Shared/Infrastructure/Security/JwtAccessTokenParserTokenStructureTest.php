<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Security;

final class JwtAccessTokenParserTokenStructureTest extends JwtAccessTokenParserTestCase
{
    public function testParseThrowsForTokenWithTwoParts(): void
    {
        $token = $this->createValidToken('RS256', 2);

        $this->expectInvalidTokenException();

        $this->parser->parse($token);
    }

    public function testParseThrowsForTokenWithFourParts(): void
    {
        $token = $this->createValidToken('RS256', 4);

        $this->expectInvalidTokenException();

        $this->parser->parse($token);
    }

    public function testParseThrowsForTokenWithOnePart(): void
    {
        $token = $this->base64UrlEncode(json_encode(['alg' => 'RS256'], JSON_THROW_ON_ERROR));

        $this->expectInvalidTokenException();

        $this->parser->parse($token);
    }

    public function testParseThrowsForTokenWithEmptyHeaderPart(): void
    {
        $token = '.'
            . $this->base64UrlEncode(
                json_encode(['sub' => $this->faker->email()], JSON_THROW_ON_ERROR)
            )
            . '.signature';

        $this->expectInvalidTokenException();

        $this->parser->parse($token);
    }

    public function testParseThrowsForHeaderWithInvalidBase64(): void
    {
        $token = '%%%.'
            . $this->base64UrlEncode(
                json_encode(['sub' => $this->faker->email()], JSON_THROW_ON_ERROR)
            )
            . '.signature';

        $this->expectInvalidTokenException();

        $this->parser->parse($token);
    }

    public function testParseThrowsForHeaderWithInvalidJson(): void
    {
        $token = $this->base64UrlEncode('{invalid json')
            . '.'
            . $this->base64UrlEncode(
                json_encode(['sub' => $this->faker->email()], JSON_THROW_ON_ERROR)
            )
            . '.signature';

        $this->expectInvalidTokenException();

        $this->parser->parse($token);
    }

    public function testParseThrowsForHeaderDecodingToNonArray(): void
    {
        $token = $this->base64UrlEncode(json_encode('just-a-string', JSON_THROW_ON_ERROR))
            . '.'
            . $this->base64UrlEncode(
                json_encode(['sub' => $this->faker->email()], JSON_THROW_ON_ERROR)
            )
            . '.signature';

        $this->expectInvalidTokenException();

        $this->parser->parse($token);
    }

    public function testParseThrowsForMissingAlgorithmInHeader(): void
    {
        $header = ['typ' => 'JWT'];
        $token = $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR))
            . '.'
            . $this->base64UrlEncode(
                json_encode(['sub' => $this->faker->email()], JSON_THROW_ON_ERROR)
            )
            . '.signature';

        $this->expectInvalidTokenException();

        $this->parser->parse($token);
    }

    public function testParseThrowsForWrongAlgorithm(): void
    {
        $token = $this->createValidToken('HS256');

        $this->expectInvalidTokenException();

        $this->parser->parse($token);
    }

    public function testParseThrowsForNonStringAlgorithmInHeader(): void
    {
        $header = ['alg' => 256, 'typ' => 'JWT'];
        $token = $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR))
            . '.'
            . $this->base64UrlEncode(
                json_encode(['sub' => $this->faker->email()], JSON_THROW_ON_ERROR)
            )
            . '.signature';

        $this->expectInvalidTokenException();

        $this->parser->parse($token);
    }

    public function testParseSucceedsForHeaderWithNestingAtMaxAllowedDepth(): void
    {
        $subject = $this->faker->email();
        $sid = $this->faker->uuid();
        $header = ['alg' => 'RS256', 'typ' => 'JWT', 'ext' => ['l2' => ['l3' => 'v']]];
        $token = $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR))
            . '.'
            . $this->base64UrlEncode(json_encode(['sub' => $subject], JSON_THROW_ON_ERROR))
            . '.signature';

        $payload = $this->buildPayload($subject, $sid, ['ROLE_USER']);
        $this->jwtEncoder->method('decode')->willReturn($payload);

        $result = $this->parser->parse($token);

        $this->assertSame($subject, $result['subject']);
    }

    public function testParseThrowsForHeaderExceedingMaxNesting(): void
    {
        $header = ['alg' => 'RS256', 'typ' => 'JWT', 'ext' => ['l2' => ['l3' => ['l4' => 'v']]]];
        $token = $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR))
            . '.'
            . $this->base64UrlEncode(
                json_encode(['sub' => $this->faker->email()], JSON_THROW_ON_ERROR)
            )
            . '.signature';

        $this->jwtEncoder->expects($this->never())->method('decode');

        $this->expectInvalidTokenException();

        $this->parser->parse($token);
    }

    public function testParseThrowsForWrongAlgorithmEvenWhenDecoderSucceeds(): void
    {
        $subject = $this->faker->email();
        $sid = $this->faker->uuid();
        $token = $this->createValidToken('HS256');
        $payload = $this->buildPayload($subject, $sid, ['ROLE_USER']);

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidTokenException();

        $this->parser->parse($token);
    }

    public function testParseThrowsForTwoPartTokenEvenWhenDecoderSucceeds(): void
    {
        $subject = $this->faker->email();
        $sid = $this->faker->uuid();
        $token = $this->createValidToken('RS256', 2);
        $payload = $this->buildPayload($subject, $sid, ['ROLE_USER']);

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidTokenException();

        $this->parser->parse($token);
    }

    public function testParseSucceedsWhenHeaderHasTypBeforeAlg(): void
    {
        $subject = $this->faker->email();
        $sid = $this->faker->uuid();
        $header = ['typ' => 'JWT', 'alg' => 'RS256'];
        $token = $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR))
            . '.' . $this->base64UrlEncode(
                json_encode(['sub' => $subject], JSON_THROW_ON_ERROR)
            )
            . '.signature';

        $payload = $this->buildPayload($subject, $sid, ['ROLE_USER']);
        $this->jwtEncoder->method('decode')->willReturn($payload);

        $result = $this->parser->parse($token);

        $this->assertSame($subject, $result['subject']);
    }
}
