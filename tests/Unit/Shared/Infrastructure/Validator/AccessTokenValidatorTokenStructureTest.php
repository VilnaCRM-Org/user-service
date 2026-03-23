<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Validator;

final class AccessTokenValidatorTokenStructureTest extends AccessTokenValidatorTestCase
{
    public function testValidateThrowsForTokenWithTwoParts(): void
    {
        $token = $this->createValidToken('RS256', 2);

        $this->expectInvalidTokenException();

        $this->validator->validate($token);
    }

    public function testValidateThrowsForTokenWithFourParts(): void
    {
        $token = $this->createValidToken('RS256', 4);

        $this->expectInvalidTokenException();

        $this->validator->validate($token);
    }

    public function testValidateThrowsForTokenWithOnePart(): void
    {
        $token = $this->base64UrlEncode(json_encode(['alg' => 'RS256'], JSON_THROW_ON_ERROR));

        $this->expectInvalidTokenException();

        $this->validator->validate($token);
    }

    public function testValidateThrowsForTokenWithEmptyHeaderPart(): void
    {
        $token = '.'
            . $this->base64UrlEncode(
                json_encode(['sub' => $this->faker->email()], JSON_THROW_ON_ERROR)
            )
            . '.signature';

        $this->expectInvalidTokenException();

        $this->validator->validate($token);
    }

    public function testValidateThrowsForHeaderWithInvalidBase64(): void
    {
        $token = '%%%.'
            . $this->base64UrlEncode(
                json_encode(['sub' => $this->faker->email()], JSON_THROW_ON_ERROR)
            )
            . '.signature';

        $this->expectInvalidTokenException();

        $this->validator->validate($token);
    }

    public function testValidateThrowsForHeaderWithInvalidJson(): void
    {
        $token = $this->base64UrlEncode('{invalid json')
            . '.'
            . $this->base64UrlEncode(
                json_encode(['sub' => $this->faker->email()], JSON_THROW_ON_ERROR)
            )
            . '.signature';

        $this->expectInvalidTokenException();

        $this->validator->validate($token);
    }

    public function testValidateThrowsForHeaderDecodingToNonArray(): void
    {
        $token = $this->base64UrlEncode(json_encode('just-a-string', JSON_THROW_ON_ERROR))
            . '.'
            . $this->base64UrlEncode(
                json_encode(['sub' => $this->faker->email()], JSON_THROW_ON_ERROR)
            )
            . '.signature';

        $this->expectInvalidTokenException();

        $this->validator->validate($token);
    }

    public function testValidateThrowsForMissingAlgorithmInHeader(): void
    {
        $header = ['typ' => 'JWT'];
        $token = $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR))
            . '.'
            . $this->base64UrlEncode(
                json_encode(['sub' => $this->faker->email()], JSON_THROW_ON_ERROR)
            )
            . '.signature';

        $this->expectInvalidTokenException();

        $this->validator->validate($token);
    }

    public function testValidateThrowsForWrongAlgorithm(): void
    {
        $token = $this->createValidToken('HS256');

        $this->expectInvalidTokenException();

        $this->validator->validate($token);
    }

    public function testValidateThrowsForNonStringAlgorithmInHeader(): void
    {
        $header = ['alg' => 256, 'typ' => 'JWT'];
        $token = $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR))
            . '.'
            . $this->base64UrlEncode(
                json_encode(['sub' => $this->faker->email()], JSON_THROW_ON_ERROR)
            )
            . '.signature';

        $this->expectInvalidTokenException();

        $this->validator->validate($token);
    }

    public function testValidateSucceedsForHeaderWithNestingAtMaxAllowedDepth(): void
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

        $result = $this->validator->validate($token);

        $this->assertSame($subject, $result['subject']);
    }

    public function testValidateThrowsForHeaderExceedingMaxNesting(): void
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

        $this->validator->validate($token);
    }

    public function testValidateThrowsForWrongAlgorithmEvenWhenDecoderSucceeds(): void
    {
        $subject = $this->faker->email();
        $sid = $this->faker->uuid();
        $token = $this->createValidToken('HS256');
        $payload = $this->buildPayload($subject, $sid, ['ROLE_USER']);

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidTokenException();

        $this->validator->validate($token);
    }

    public function testValidateThrowsForTwoPartTokenEvenWhenDecoderSucceeds(): void
    {
        $subject = $this->faker->email();
        $sid = $this->faker->uuid();
        $token = $this->createValidToken('RS256', 2);
        $payload = $this->buildPayload($subject, $sid, ['ROLE_USER']);

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidTokenException();

        $this->validator->validate($token);
    }

    public function testValidateSucceedsWhenHeaderHasTypBeforeAlg(): void
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

        $result = $this->validator->validate($token);

        $this->assertSame($subject, $result['subject']);
    }
}
