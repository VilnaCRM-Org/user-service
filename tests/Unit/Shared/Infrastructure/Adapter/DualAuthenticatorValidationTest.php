<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Adapter;

use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

final class DualAuthenticatorValidationTest extends DualAuthenticatorTestCase
{
    public function testAuthenticateRejectsWhenJwtDecoderFails(): void
    {
        $tokenValue = $this->createJwtToken('RS256');
        $this->jwtEncoder->expects($this->once())
            ->method('decode')
            ->with($tokenValue)
            ->willThrowException(
                new JWTDecodeFailureException(
                    JWTDecodeFailureException::INVALID_TOKEN,
                    'invalid token'
                )
            );
        $this->expectException(
            CustomUserMessageAuthenticationException::class
        );
        $this->expectExceptionMessage('Invalid access token.');
        $this->createAuthenticator()->authenticate(
            $this->createBearerRequest($tokenValue)
        );
    }

    public function testAuthenticateRejectsWhenJwtDecoderReturnsNonArray(): void
    {
        $tokenValue = $this->createJwtToken('RS256');
        $this->jwtEncoder
            ->expects($this->once())
            ->method('decode')
            ->with($tokenValue)
            ->willReturn('invalid');

        $request = Request::create('/api/users');
        $request->headers->set(
            'Authorization',
            'Bearer ' . $tokenValue
        );

        $this->expectException(
            CustomUserMessageAuthenticationException::class
        );
        $this->expectExceptionMessage('Invalid access token.');

        $this->createAuthenticator()->authenticate($request);
    }

    public function testAuthenticateRejectsInvalidAudienceArrayItemType(): void
    {
        $payload = $this->validPayload($this->faker->email(), ['ROLE_USER']);
        $payload['aud'] = ['vilnacrm-api', 123];
        $this->expectRejectWithInvalidClaims($payload);
    }

    public function testAuthenticateRejectsEmptyAudienceArray(): void
    {
        $payload = $this->validPayload($this->faker->email(), ['ROLE_USER']);
        $payload['aud'] = [];
        $this->expectRejectWithInvalidClaims($payload);
    }

    public function testAuthenticateRejectsExpiredAccessTokenClaims(): void
    {
        $payload = $this->validPayload($this->faker->email(), ['ROLE_USER']);
        $payload['exp'] = time() - 1;
        $this->expectRejectWithInvalidClaims($payload);
    }

    public function testAuthenticateRejectsHeaderWithInvalidBase64(): void
    {
        $token = '%%%.'
            . $this->base64UrlEncode('{}')
            . '.signature';
        $request = Request::create('/api/users');
        $request->headers->set(
            'Authorization',
            'Bearer ' . $token
        );

        $this->expectException(
            CustomUserMessageAuthenticationException::class
        );
        $this->expectExceptionMessage('Invalid access token.');

        $this->createAuthenticator()->authenticate($request);
    }

    public function testAuthenticateRejectsInvalidTimestampType(): void
    {
        $payload = $this->validPayload($this->faker->email(), ['ROLE_USER']);
        $payload['nbf'] = 'invalid';
        $this->expectRejectWithInvalidClaims($payload);
    }

    public function testAuthenticateRejectsMissingRolesClaim(): void
    {
        $payload = $this->validPayload($this->faker->email(), ['ROLE_USER']);
        unset($payload['roles']);
        $this->expectRejectWithInvalidClaims($payload);
    }

    public function testAuthenticateRejectsRoleClaimWithNonStringValue(): void
    {
        $payload = $this->validPayload($this->faker->email(), ['ROLE_USER']);
        $payload['roles'] = ['ROLE_USER', 123];
        $this->expectRejectWithInvalidClaims($payload);
    }

    public function testAuthenticateRejectsMissingSubjectClaim(): void
    {
        $payload = $this->validPayload($this->faker->email(), ['ROLE_USER']);
        unset($payload['sub']);
        $this->expectRejectWithInvalidClaims($payload);
    }

    public function testAuthenticateResolvesUserByIdWhenSubjectIsUuid(): void
    {
        $subject = $this->faker->uuid();
        $email = $this->faker->email();
        $tokenValue = $this->createJwtToken('RS256');
        $payload = $this->validPayload($subject, ['ROLE_USER']);
        $user = $this->createDomainUser($email);
        $this->expectUuidSubjectResolution(
            $tokenValue,
            $payload,
            $subject,
            $user
        );
        $passport = $this->createAuthenticator()->authenticate(
            $this->createBearerRequest($tokenValue)
        );
        $this->assertSame($email, $passport->getUser()->getUserIdentifier());
    }

    public function testAuthenticateRejectsUnknownUuidSubject(): void
    {
        $subject = $this->faker->uuid();
        $tokenValue = $this->createJwtToken('RS256');
        $payload = $this->validPayload($subject, ['ROLE_USER']);

        $this->expectUuidSubjectResolution(
            $tokenValue,
            $payload,
            $subject,
            null
        );

        $this->expectException(
            CustomUserMessageAuthenticationException::class
        );
        $this->expectExceptionMessage('Authentication required.');

        $this->createAuthenticator()->authenticate(
            $this->createBearerRequest($tokenValue)
        );
    }

    public function testOnAuthenticationSuccessReturnsNull(): void
    {
        $response = $this->createAuthenticator()
            ->onAuthenticationSuccess(
                Request::create('/api/users'),
                $this->createMock(TokenInterface::class),
                'api'
            );

        $this->assertNull($response);
    }

    public function testStartReturnsProblemJsonAndBearerHeader(): void
    {
        $response = $this->createAuthenticator()->start(
            Request::create('/api/users')
        );
        $this->assertUnauthorizedProblemResponse($response);
    }

    public function testOnAuthenticationFailureResponseBodyHasRequiredFields(): void
    {
        $response = $this->createAuthenticator()
            ->onAuthenticationFailure(
                Request::create('/api/users'),
                new AuthenticationException('invalid')
            );
        $body = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('about:blank', $body['type']);
        $this->assertSame('Unauthorized', $body['title']);
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $body['status']);
        $this->assertSame('Authentication required.', $body['detail']);
    }

    public function testStartResponseBodyHasRequiredFields(): void
    {
        $response = $this->createAuthenticator()->start(
            Request::create('/api/users')
        );
        $body = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('about:blank', $body['type']);
        $this->assertSame('Unauthorized', $body['title']);
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $body['status']);
        $this->assertSame('Authentication required.', $body['detail']);
    }

    /**
     * @param array<string, int|string|array<int|string>> $payload
     */
    private function expectRejectWithInvalidClaims(
        array $payload
    ): void {
        $tokenValue = $this->createJwtToken('RS256');
        $this->expectJwtDecode($tokenValue, $payload);
        $this->expectException(
            CustomUserMessageAuthenticationException::class
        );
        $this->expectExceptionMessage(
            'Invalid access token claims.'
        );
        $this->createAuthenticator()->authenticate(
            $this->createBearerRequest($tokenValue)
        );
    }
}
