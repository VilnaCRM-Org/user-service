<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Validator;

final class AccessTokenValidatorIssuerAudienceTest extends AccessTokenValidatorTestCase
{
    public function testValidateThrowsForMissingIssuer(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        unset($payload['iss']);

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->validator->validate($token);
    }

    public function testValidateThrowsForWrongIssuer(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        $payload['iss'] = $this->faker->domainName();

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->validator->validate($token);
    }

    public function testValidateThrowsForNonStringIssuer(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        $payload['iss'] = ['vilnacrm-user-service'];

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->validator->validate($token);
    }

    public function testValidateThrowsForMissingAudience(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        unset($payload['aud']);

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->validator->validate($token);
    }

    public function testValidateReturnsServiceRoleForOauthTokenWithoutIss(): void
    {
        $subject = $this->faker->email();
        $token = $this->createValidToken();
        $payload = [
            'sub' => $subject,
            'aud' => 'vilnacrm-api',
            'nbf' => time() - 10,
            'exp' => time() + 900,
        ];

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $result = $this->validator->validate($token);

        $this->assertSame($subject, $result['subject']);
        $this->assertSame('', $result['sid']);
        $this->assertSame(['ROLE_SERVICE'], $result['roles']);
    }

    public function testValidateThrowsForMissingAudienceWhenSidAndRolesAreMissing(): void
    {
        $token = $this->createValidToken();
        $payload = [
            'sub' => $this->faker->email(),
            'iss' => 'vilnacrm-user-service',
            'nbf' => time() - 10,
            'exp' => time() + 900,
        ];

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->validator->validate($token);
    }

    public function testValidateThrowsForWrongStringAudience(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        $payload['aud'] = $this->faker->domainName();

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->validator->validate($token);
    }

    public function testValidateThrowsForNonStringNonArrayAudience(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        $payload['aud'] = 42;

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->validator->validate($token);
    }

    public function testValidateThrowsForEmptyArrayAudience(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        $payload['aud'] = [];

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->validator->validate($token);
    }

    public function testValidateThrowsForArrayAudienceWithNonStringValue(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        $payload['aud'] = ['vilnacrm-api', 123];

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->validator->validate($token);
    }

    public function testValidateThrowsForArrayAudienceWithEmptyStringValue(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        $payload['aud'] = ['vilnacrm-api', ''];

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->validator->validate($token);
    }

    public function testValidateThrowsForArrayAudienceWithNoMatch(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        $payload['aud'] = ['other-service', 'another-service'];

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->validator->validate($token);
    }

    public function testValidateThrowsForWrongIssuerWhenRolesAreAbsent(): void
    {
        $token = $this->createValidToken();
        $payload = [
            'sub' => $this->faker->email(),
            'iss' => $this->faker->domainName(),
            'aud' => 'vilnacrm-api',
            'nbf' => time() - 10,
            'exp' => time() + 900,
        ];

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->validator->validate($token);
    }
}
