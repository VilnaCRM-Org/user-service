<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Validator;

use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;

final class AccessTokenValidatorPayloadTest extends AccessTokenValidatorTestCase
{
    public function testValidateReturnsSubjectSidAndRolesForValidToken(): void
    {
        $subject = $this->faker->email();
        $sid = $this->faker->uuid();
        $roles = ['ROLE_USER'];
        $token = $this->createValidToken();
        $payload = $this->buildPayload($subject, $sid, $roles);

        $this->jwtEncoder
            ->expects($this->once())
            ->method('decode')
            ->with($token)
            ->willReturn($payload);

        $result = $this->validator->validate($token);

        $this->assertSame($subject, $result['subject']);
        $this->assertSame($sid, $result['sid']);
        $this->assertSame($roles, $result['roles']);
    }

    public function testValidateDeduplicatesRoles(): void
    {
        $subject = $this->faker->email();
        $sid = $this->faker->uuid();
        $token = $this->createValidToken();
        $payload = $this->buildPayload($subject, $sid, ['ROLE_USER', 'ROLE_USER', 'ROLE_ADMIN']);

        $this->jwtEncoder
            ->method('decode')
            ->willReturn($payload);

        $result = $this->validator->validate($token);

        $this->assertSame(['ROLE_USER', 'ROLE_ADMIN'], $result['roles']);
    }

    public function testValidatePreservesOrderAfterDeduplication(): void
    {
        $subject = $this->faker->email();
        $sid = $this->faker->uuid();
        $token = $this->createValidToken();
        $payload = $this->buildPayload($subject, $sid, ['ROLE_ADMIN', 'ROLE_USER', 'ROLE_ADMIN']);

        $this->jwtEncoder
            ->method('decode')
            ->willReturn($payload);

        $result = $this->validator->validate($token);

        $this->assertSame(['ROLE_ADMIN', 'ROLE_USER'], $result['roles']);
        $this->assertIsArray($result['roles']);
    }

    public function testValidateReturnsMultipleUniqueRoles(): void
    {
        $subject = $this->faker->email();
        $sid = $this->faker->uuid();
        $roles = ['ROLE_USER', 'ROLE_ADMIN', 'ROLE_MANAGER'];
        $token = $this->createValidToken();
        $payload = $this->buildPayload($subject, $sid, $roles);

        $this->jwtEncoder
            ->method('decode')
            ->willReturn($payload);

        $result = $this->validator->validate($token);

        $this->assertSame($roles, $result['roles']);
    }

    public function testValidateAcceptsArrayAudienceWithSingleMatch(): void
    {
        $subject = $this->faker->email();
        $sid = $this->faker->uuid();
        $token = $this->createValidToken();
        $payload = $this->buildPayload($subject, $sid, ['ROLE_USER']);
        $payload['aud'] = ['vilnacrm-api'];

        $this->jwtEncoder
            ->method('decode')
            ->willReturn($payload);

        $result = $this->validator->validate($token);

        $this->assertSame($subject, $result['subject']);
        $this->assertSame($sid, $result['sid']);
        $this->assertSame(['ROLE_USER'], $result['roles']);
    }

    public function testValidateAcceptsArrayAudienceWithMultipleValuesIncludingExpected(): void
    {
        $subject = $this->faker->email();
        $sid = $this->faker->uuid();
        $token = $this->createValidToken();
        $payload = $this->buildPayload($subject, $sid, ['ROLE_USER']);
        $payload['aud'] = ['vilnacrm-api', 'other-service'];

        $this->jwtEncoder
            ->method('decode')
            ->willReturn($payload);

        $result = $this->validator->validate($token);

        $this->assertSame($subject, $result['subject']);
        $this->assertSame($sid, $result['sid']);
        $this->assertSame(['ROLE_USER'], $result['roles']);
    }

    public function testValidateThrowsWhenJwtDecoderFails(): void
    {
        $token = $this->createValidToken();

        $this->jwtEncoder
            ->expects($this->once())
            ->method('decode')
            ->with($token)
            ->willThrowException(
                new JWTDecodeFailureException(JWTDecodeFailureException::INVALID_TOKEN, 'invalid')
            );

        $this->expectInvalidTokenException();

        $this->validator->validate($token);
    }

    public function testValidateThrowsWhenPayloadIsNotAnArray(): void
    {
        $token = $this->createValidToken();

        $this->jwtEncoder
            ->expects($this->once())
            ->method('decode')
            ->with($token)
            ->willReturn('not-an-array');

        $this->expectInvalidTokenException();

        $this->validator->validate($token);
    }

    public function testValidateThrowsWhenPayloadIsNull(): void
    {
        $token = $this->createValidToken();

        $this->jwtEncoder
            ->expects($this->once())
            ->method('decode')
            ->with($token)
            ->willReturn(null);

        $this->expectInvalidTokenException();

        $this->validator->validate($token);
    }

    public function testValidateThrowsWhenPayloadIsInteger(): void
    {
        $token = $this->createValidToken();

        $this->jwtEncoder
            ->expects($this->once())
            ->method('decode')
            ->with($token)
            ->willReturn(42);

        $this->expectInvalidTokenException();

        $this->validator->validate($token);
    }

    public function testValidatePassesEntireTokenToJwtDecoder(): void
    {
        $subject = $this->faker->email();
        $sid = $this->faker->uuid();
        $token = $this->createValidToken();
        $payload = $this->buildPayload($subject, $sid, ['ROLE_USER']);

        $this->jwtEncoder
            ->expects($this->once())
            ->method('decode')
            ->with($this->identicalTo($token))
            ->willReturn($payload);

        $this->validator->validate($token);
    }

    public function testValidateSupportsOauthPayloadWithoutSidAndRoles(): void
    {
        $subject = $this->faker->email();
        $token = $this->createValidToken();
        $payload = [
            'sub' => $subject,
            'iss' => 'vilnacrm-user-service',
            'aud' => 'vilnacrm-api',
            'nbf' => time() - 10,
            'exp' => time() + 900,
        ];

        $this->jwtEncoder
            ->method('decode')
            ->willReturn($payload);

        $result = $this->validator->validate($token);

        $this->assertSame($subject, $result['subject']);
        $this->assertSame('', $result['sid']);
        $this->assertSame(['ROLE_USER'], $result['roles']);
    }

    public function testValidateUsesClientIdWhenSubjectMissing(): void
    {
        $clientId = strtolower($this->faker->bothify('oauth-client-####'));
        $token = $this->createValidToken();
        $payload = [
            'client_id' => $clientId,
            'iss' => 'vilnacrm-user-service',
            'aud' => 'vilnacrm-api',
            'nbf' => time() - 10,
            'exp' => time() + 900,
        ];

        $this->jwtEncoder
            ->method('decode')
            ->willReturn($payload);

        $result = $this->validator->validate($token);

        $this->assertSame($clientId, $result['subject']);
        $this->assertSame(['ROLE_USER'], $result['roles']);
    }

    public function testValidateThrowsWhenSubjectAndClientIdAreMissing(): void
    {
        $token = $this->createValidToken();
        $payload = [
            'iss' => 'vilnacrm-user-service',
            'aud' => 'vilnacrm-api',
            'nbf' => time() - 10,
            'exp' => time() + 900,
        ];

        $this->jwtEncoder
            ->method('decode')
            ->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->validator->validate($token);
    }

    public function testValidateThrowsWhenSubjectAndClientIdAreMissingAndAudienceIsArray(): void
    {
        $token = $this->createValidToken();
        $payload = [
            'iss' => 'vilnacrm-user-service',
            'aud' => [strtolower($this->faker->bothify('oauth-aud-####'))],
            'nbf' => time() - 10,
            'exp' => time() + 900,
        ];

        $this->jwtEncoder
            ->method('decode')
            ->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->validator->validate($token);
    }
}
