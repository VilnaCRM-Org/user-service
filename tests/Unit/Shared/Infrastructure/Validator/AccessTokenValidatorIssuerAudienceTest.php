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

    public function testValidateRejectsOauthTokenWithoutIss(): void
    {
        // Regression for the ROLE_SERVICE privilege-escalation: a League OAuth2
        // access token is signed with the shared RSA key but carries
        // aud=client_id and no iss/roles claim. It must be rejected outright,
        // never silently elevated to ROLE_SERVICE.
        $token = $this->createValidToken();
        $payload = [
            'sub' => $this->faker->email(),
            'aud' => 'some-oauth-client-id',
            'nbf' => time() - 10,
            'exp' => time() + 900,
        ];

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->validator->validate($token);
    }

    public function testValidateRejectsTokenWithApiAudienceButNoIssuer(): void
    {
        // Even when a non-first-party token happens to carry aud=vilnacrm-api,
        // the absence of a valid issuer must cause rejection (no ROLE_SERVICE
        // inference from missing claims).
        $token = $this->createValidToken();
        $payload = [
            'sub' => $this->faker->email(),
            'aud' => 'vilnacrm-api',
            'nbf' => time() - 10,
            'exp' => time() + 900,
        ];

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->validator->validate($token);
    }

    public function testValidateDefaultsToRoleUserWhenRolesClaimAbsent(): void
    {
        // A fully validated first-party token (valid iss + aud) that omits the
        // explicit roles claim must default to the least-privilege ROLE_USER,
        // never ROLE_SERVICE.
        $subject = $this->faker->uuid();
        $token = $this->createValidToken();
        $payload = [
            'sub' => $subject,
            'iss' => 'vilnacrm-user-service',
            'aud' => 'vilnacrm-api',
            'nbf' => time() - 10,
            'exp' => time() + 900,
        ];

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $result = $this->validator->validate($token);

        $this->assertSame($subject, $result['subject']);
        $this->assertSame(['ROLE_USER'], $result['roles']);
    }

    public function testValidateAcceptsExplicitServiceTokenWithFirstPartyClaims(): void
    {
        // ROLE_SERVICE remains valid only when positively asserted inside a
        // fully validated first-party token (iss + aud + sid + roles).
        $subject = $this->faker->uuid();
        $sid = $this->faker->uuid();
        $token = $this->createValidToken();
        $payload = $this->buildPayload($subject, $sid, ['ROLE_SERVICE']);

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $result = $this->validator->validate($token);

        $this->assertSame($subject, $result['subject']);
        $this->assertSame($sid, $result['sid']);
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
