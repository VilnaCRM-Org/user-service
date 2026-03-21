<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Validator;

final class AccessTokenValidatorIdentityClaimsTest extends AccessTokenValidatorTestCase
{
    public function testValidateThrowsForMissingSubject(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        unset($payload['sub']);

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->validator->validate($token);
    }

    public function testValidateThrowsForEmptySubject(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        $payload['sub'] = '';

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->validator->validate($token);
    }

    public function testValidateThrowsForNonStringSubject(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        $payload['sub'] = 42;

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->validator->validate($token);
    }

    public function testValidateThrowsForMissingSid(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        unset($payload['sid']);

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->validator->validate($token);
    }

    public function testValidateThrowsForEmptySid(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        $payload['sid'] = '';

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->validator->validate($token);
    }

    public function testValidateThrowsForNonStringSid(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        $payload['sid'] = 99;

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->validator->validate($token);
    }

    public function testValidateFallsBackToRoleUserWhenRolesAreMissing(): void
    {
        $token = $this->createValidToken();
        $subject = $this->faker->email();
        $sid = $this->faker->uuid();
        $payload = $this->buildPayload($subject, $sid, ['ROLE_USER']);
        unset($payload['roles']);

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $result = $this->validator->validate($token);

        $this->assertSame(['ROLE_USER'], $result['roles']);
        $this->assertSame($subject, $result['subject']);
        $this->assertSame($sid, $result['sid']);
    }

    public function testValidateThrowsForEmptyRolesArray(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        $payload['roles'] = [];

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->validator->validate($token);
    }

    public function testValidateThrowsForNonArrayRoles(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        $payload['roles'] = 'ROLE_USER';

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->validator->validate($token);
    }

    public function testValidateThrowsForRolesWithNonStringValue(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        $payload['roles'] = ['ROLE_USER', 123];

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->validator->validate($token);
    }

    public function testValidateThrowsForRolesWithEmptyStringValue(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        $payload['roles'] = ['ROLE_USER', ''];

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->validator->validate($token);
    }
}
