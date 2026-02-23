<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Security;

final class JwtAccessTokenParserTimeClaimsTest extends JwtAccessTokenParserTestCase
{
    public function testParseThrowsForMissingNbf(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        unset($payload['nbf']);

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->parser->parse($token);
    }

    public function testParseThrowsForNonIntegerNbf(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        $payload['nbf'] = 'not-an-integer';

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->parser->parse($token);
    }

    public function testParseThrowsForMissingExp(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        unset($payload['exp']);

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->parser->parse($token);
    }

    public function testParseThrowsForNonIntegerExp(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        $payload['exp'] = 'not-an-integer';

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->parser->parse($token);
    }

    public function testParseThrowsForNotYetValidToken(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        $payload['nbf'] = time() + 60;

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->parser->parse($token);
    }

    public function testParseThrowsForExpiredToken(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        $payload['exp'] = time() - 1;

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->parser->parse($token);
    }

    public function testParseThrowsForTokenExpiredExactlyAtCurrentTime(): void
    {
        $now = time();
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        $payload['exp'] = $now;

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->parser->parse($token);
    }

    public function testParseAcceptsTokenValidExactlyAtNbfTime(): void
    {
        $subject = $this->faker->email();
        $sid = $this->faker->uuid();
        $now = time();
        $token = $this->createValidToken();
        $payload = $this->buildPayload($subject, $sid, ['ROLE_USER']);
        $payload['nbf'] = $now;

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $result = $this->parser->parse($token);

        $this->assertSame($subject, $result['subject']);
    }
}
