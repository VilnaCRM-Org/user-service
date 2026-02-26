<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Security;

final class JwtAccessTokenParserIssuerAudienceTest extends JwtAccessTokenParserTestCase
{
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

    public function testParseReturnsServiceRoleForOauthTokenWithoutIss(): void
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

        $result = $this->parser->parse($token);

        $this->assertSame($subject, $result['subject']);
        $this->assertSame('', $result['sid']);
        $this->assertSame(['ROLE_SERVICE'], $result['roles']);
    }

    public function testParseThrowsForMissingAudienceWhenSidAndRolesAreMissing(): void
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
}
