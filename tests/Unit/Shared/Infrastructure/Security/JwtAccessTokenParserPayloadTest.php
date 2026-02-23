<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;

final class JwtAccessTokenParserPayloadTest extends JwtAccessTokenParserTestCase
{
    public function testParseReturnsSubjectSidAndRolesForValidToken(): void
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

        $result = $this->parser->parse($token);

        $this->assertSame($subject, $result['subject']);
        $this->assertSame($sid, $result['sid']);
        $this->assertSame($roles, $result['roles']);
    }

    public function testParseDeduplicatesRoles(): void
    {
        $subject = $this->faker->email();
        $sid = $this->faker->uuid();
        $token = $this->createValidToken();
        $payload = $this->buildPayload($subject, $sid, ['ROLE_USER', 'ROLE_USER', 'ROLE_ADMIN']);

        $this->jwtEncoder
            ->method('decode')
            ->willReturn($payload);

        $result = $this->parser->parse($token);

        $this->assertSame(['ROLE_USER', 'ROLE_ADMIN'], $result['roles']);
    }

    public function testParsePreservesOrderAfterDeduplication(): void
    {
        $subject = $this->faker->email();
        $sid = $this->faker->uuid();
        $token = $this->createValidToken();
        $payload = $this->buildPayload($subject, $sid, ['ROLE_ADMIN', 'ROLE_USER', 'ROLE_ADMIN']);

        $this->jwtEncoder
            ->method('decode')
            ->willReturn($payload);

        $result = $this->parser->parse($token);

        $this->assertSame(['ROLE_ADMIN', 'ROLE_USER'], $result['roles']);
        $this->assertIsArray($result['roles']);
    }

    public function testParseReturnsMultipleUniqueRoles(): void
    {
        $subject = $this->faker->email();
        $sid = $this->faker->uuid();
        $roles = ['ROLE_USER', 'ROLE_ADMIN', 'ROLE_MANAGER'];
        $token = $this->createValidToken();
        $payload = $this->buildPayload($subject, $sid, $roles);

        $this->jwtEncoder
            ->method('decode')
            ->willReturn($payload);

        $result = $this->parser->parse($token);

        $this->assertSame($roles, $result['roles']);
    }

    public function testParseAcceptsArrayAudienceWithSingleMatch(): void
    {
        $subject = $this->faker->email();
        $sid = $this->faker->uuid();
        $token = $this->createValidToken();
        $payload = $this->buildPayload($subject, $sid, ['ROLE_USER']);
        $payload['aud'] = ['vilnacrm-api'];

        $this->jwtEncoder
            ->method('decode')
            ->willReturn($payload);

        $result = $this->parser->parse($token);

        $this->assertSame($subject, $result['subject']);
    }

    public function testParseAcceptsArrayAudienceWithMultipleValuesIncludingExpected(): void
    {
        $subject = $this->faker->email();
        $sid = $this->faker->uuid();
        $token = $this->createValidToken();
        $payload = $this->buildPayload($subject, $sid, ['ROLE_USER']);
        $payload['aud'] = ['vilnacrm-api', 'other-service'];

        $this->jwtEncoder
            ->method('decode')
            ->willReturn($payload);

        $result = $this->parser->parse($token);

        $this->assertSame($subject, $result['subject']);
    }

    public function testParseThrowsWhenJwtDecoderFails(): void
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

        $this->parser->parse($token);
    }

    public function testParseThrowsWhenPayloadIsNotAnArray(): void
    {
        $token = $this->createValidToken();

        $this->jwtEncoder
            ->expects($this->once())
            ->method('decode')
            ->with($token)
            ->willReturn('not-an-array');

        $this->expectInvalidTokenException();

        $this->parser->parse($token);
    }

    public function testParseThrowsWhenPayloadIsNull(): void
    {
        $token = $this->createValidToken();

        $this->jwtEncoder
            ->expects($this->once())
            ->method('decode')
            ->with($token)
            ->willReturn(null);

        $this->expectInvalidTokenException();

        $this->parser->parse($token);
    }

    public function testParseThrowsWhenPayloadIsInteger(): void
    {
        $token = $this->createValidToken();

        $this->jwtEncoder
            ->expects($this->once())
            ->method('decode')
            ->with($token)
            ->willReturn(42);

        $this->expectInvalidTokenException();

        $this->parser->parse($token);
    }

    public function testParsePassesEntireTokenToJwtDecoder(): void
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

        $this->parser->parse($token);
    }
}
