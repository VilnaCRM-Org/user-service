<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Validator;

final class AccessTokenValidatorTimeClaimsTest extends AccessTokenValidatorTestCase
{
    public function testValidateThrowsForMissingNbf(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        unset($payload['nbf']);

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->validator->validate($token);
    }

    public function testValidateThrowsForNonIntegerNbf(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        $payload['nbf'] = 'not-an-integer';

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->validator->validate($token);
    }

    public function testValidateThrowsForMissingExp(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        unset($payload['exp']);

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->validator->validate($token);
    }

    public function testValidateThrowsForNonIntegerExp(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        $payload['exp'] = 'not-an-integer';

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->validator->validate($token);
    }

    public function testValidateThrowsForNotYetValidToken(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        $payload['nbf'] = time() + 60;

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->validator->validate($token);
    }

    public function testValidateThrowsForExpiredToken(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        $payload['exp'] = time() - 1;

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->validator->validate($token);
    }

    public function testValidateThrowsForTokenExpiredExactlyAtCurrentTime(): void
    {
        $now = time();
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        $payload['exp'] = $now;

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->validator->validate($token);
    }

    public function testValidateAcceptsTokenValidExactlyAtNbfTime(): void
    {
        $subject = $this->faker->email();
        $sid = $this->faker->uuid();
        $now = time();
        $token = $this->createValidToken();
        $payload = $this->buildPayload($subject, $sid, ['ROLE_USER']);
        $payload['nbf'] = $now;

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $result = $this->validator->validate($token);

        $this->assertSame($subject, $result['subject']);
    }
}
