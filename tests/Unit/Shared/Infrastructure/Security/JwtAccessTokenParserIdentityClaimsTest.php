<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Security;

final class JwtAccessTokenParserIdentityClaimsTest extends JwtAccessTokenParserTestCase
{
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
}
