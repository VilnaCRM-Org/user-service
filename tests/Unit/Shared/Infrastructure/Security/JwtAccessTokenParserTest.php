<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Security;

use App\Shared\Infrastructure\Security\JwtAccessTokenParser;
use App\Tests\Unit\UnitTestCase;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

final class JwtAccessTokenParserTest extends UnitTestCase
{
    private JWTEncoderInterface&MockObject $jwtEncoder;
    private JwtAccessTokenParser $parser;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->jwtEncoder = $this->createMock(JWTEncoderInterface::class);
        $this->parser = new JwtAccessTokenParser($this->jwtEncoder);
    }

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
            . $this->base64UrlEncode(json_encode(['sub' => $this->faker->email()], JSON_THROW_ON_ERROR))
            . '.signature';

        $this->expectInvalidTokenException();

        $this->parser->parse($token);
    }

    public function testParseThrowsForHeaderWithInvalidBase64(): void
    {
        $token = '%%%'
            . '.'
            . $this->base64UrlEncode(json_encode(['sub' => $this->faker->email()], JSON_THROW_ON_ERROR))
            . '.signature';

        $this->expectInvalidTokenException();

        $this->parser->parse($token);
    }

    public function testParseThrowsForHeaderWithInvalidJson(): void
    {
        $token = $this->base64UrlEncode('{invalid json')
            . '.'
            . $this->base64UrlEncode(json_encode(['sub' => $this->faker->email()], JSON_THROW_ON_ERROR))
            . '.signature';

        $this->expectInvalidTokenException();

        $this->parser->parse($token);
    }

    public function testParseThrowsForHeaderDecodingToNonArray(): void
    {
        $token = $this->base64UrlEncode(json_encode('just-a-string', JSON_THROW_ON_ERROR))
            . '.'
            . $this->base64UrlEncode(json_encode(['sub' => $this->faker->email()], JSON_THROW_ON_ERROR))
            . '.signature';

        $this->expectInvalidTokenException();

        $this->parser->parse($token);
    }

    public function testParseThrowsForMissingAlgorithmInHeader(): void
    {
        $header = ['typ' => 'JWT'];
        $token = $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR))
            . '.'
            . $this->base64UrlEncode(json_encode(['sub' => $this->faker->email()], JSON_THROW_ON_ERROR))
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
            . $this->base64UrlEncode(json_encode(['sub' => $this->faker->email()], JSON_THROW_ON_ERROR))
            . '.signature';

        $this->expectInvalidTokenException();

        $this->parser->parse($token);
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

    public function testParseThrowsForMissingIssuer(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        unset($payload['iss']);

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->parser->parse($token);
    }

    public function testParseThrowsForWrongIssuer(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        $payload['iss'] = $this->faker->domainName();

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->parser->parse($token);
    }

    public function testParseThrowsForNonStringIssuer(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        $payload['iss'] = ['vilnacrm-user-service'];

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->parser->parse($token);
    }

    public function testParseThrowsForMissingAudience(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        unset($payload['aud']);

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->parser->parse($token);
    }

    public function testParseThrowsForWrongStringAudience(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        $payload['aud'] = $this->faker->domainName();

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->parser->parse($token);
    }

    public function testParseThrowsForNonStringNonArrayAudience(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        $payload['aud'] = 42;

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->parser->parse($token);
    }

    public function testParseThrowsForEmptyArrayAudience(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        $payload['aud'] = [];

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->parser->parse($token);
    }

    public function testParseThrowsForArrayAudienceWithNonStringValue(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        $payload['aud'] = ['vilnacrm-api', 123];

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->parser->parse($token);
    }

    public function testParseThrowsForArrayAudienceWithEmptyStringValue(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        $payload['aud'] = ['vilnacrm-api', ''];

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->parser->parse($token);
    }

    public function testParseThrowsForArrayAudienceWithNoMatch(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        $payload['aud'] = ['other-service', 'another-service'];

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->parser->parse($token);
    }

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

    public function testParseThrowsForMissingSubject(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        unset($payload['sub']);

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->parser->parse($token);
    }

    public function testParseThrowsForEmptySubject(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        $payload['sub'] = '';

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->parser->parse($token);
    }

    public function testParseThrowsForNonStringSubject(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        $payload['sub'] = 42;

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->parser->parse($token);
    }

    public function testParseThrowsForMissingSid(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        unset($payload['sid']);

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->parser->parse($token);
    }

    public function testParseThrowsForEmptySid(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        $payload['sid'] = '';

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->parser->parse($token);
    }

    public function testParseThrowsForNonStringSid(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        $payload['sid'] = 99;

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->parser->parse($token);
    }

    public function testParseThrowsForMissingRoles(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        unset($payload['roles']);

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->parser->parse($token);
    }

    public function testParseThrowsForEmptyRolesArray(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        $payload['roles'] = [];

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->parser->parse($token);
    }

    public function testParseThrowsForNonArrayRoles(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        $payload['roles'] = 'ROLE_USER';

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->parser->parse($token);
    }

    public function testParseThrowsForRolesWithNonStringValue(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        $payload['roles'] = ['ROLE_USER', 123];

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

        $this->parser->parse($token);
    }

    public function testParseThrowsForRolesWithEmptyStringValue(): void
    {
        $token = $this->createValidToken();
        $payload = $this->buildPayload($this->faker->email(), $this->faker->uuid(), ['ROLE_USER']);
        $payload['roles'] = ['ROLE_USER', ''];

        $this->jwtEncoder->method('decode')->willReturn($payload);

        $this->expectInvalidClaimsException();

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

    private function createValidToken(string $algorithm = 'RS256', int $parts = 3): string
    {
        $header = ['alg' => $algorithm, 'typ' => 'JWT'];
        $encodedHeader = $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR));
        $encodedPayload = $this->base64UrlEncode(
            json_encode(['sub' => $this->faker->email()], JSON_THROW_ON_ERROR)
        );

        $segments = array_fill(0, $parts, 'segment');
        $segments[0] = $encodedHeader;

        if ($parts >= 2) {
            $segments[1] = $encodedPayload;
        }

        return implode('.', $segments);
    }

    /**
     * @param array<string> $roles
     *
     * @return array<string, array<int|string>|int|string>
     */
    private function buildPayload(string $subject, string $sid, array $roles): array
    {
        $now = time();

        return [
            'sub' => $subject,
            'iss' => 'vilnacrm-user-service',
            'aud' => 'vilnacrm-api',
            'nbf' => $now - 10,
            'iat' => $now - 10,
            'exp' => $now + 900,
            'sid' => $sid,
            'roles' => $roles,
        ];
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function expectInvalidTokenException(): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Invalid access token.');
    }

    private function expectInvalidClaimsException(): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Invalid access token claims.');
    }
}
