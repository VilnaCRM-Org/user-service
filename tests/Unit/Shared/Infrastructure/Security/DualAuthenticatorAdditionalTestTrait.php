<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

trait DualAuthenticatorAdditionalTestTrait
{
    public function testAuthenticateRejectsWhenJwtDecoderFails(): void
    {
        $tokenValue = $this->createJwtToken('RS256');
        $this->jwtEncoder
            ->expects($this->once())
            ->method('decode')
            ->with($tokenValue)
            ->willThrowException(
                new JWTDecodeFailureException(
                    JWTDecodeFailureException::INVALID_TOKEN,
                    'invalid token'
                )
            );

        $request = Request::create('/api/users');
        $request->headers->set('Authorization', 'Bearer ' . $tokenValue);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Invalid access token.');

        $this->createAuthenticator()->authenticate($request);
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
        $request->headers->set('Authorization', 'Bearer ' . $tokenValue);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Invalid access token.');

        $this->createAuthenticator()->authenticate($request);
    }

    public function testAuthenticateRejectsInvalidAudienceArrayItemType(): void
    {
        $tokenValue = $this->createJwtToken('RS256');
        $payload = $this->validPayload($this->faker->email(), ['ROLE_USER']);
        $payload['aud'] = ['vilnacrm-api', 123];

        $this->jwtEncoder
            ->expects($this->once())
            ->method('decode')
            ->with($tokenValue)
            ->willReturn($payload);

        $request = Request::create('/api/users');
        $request->headers->set('Authorization', 'Bearer ' . $tokenValue);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Invalid access token claims.');

        $this->createAuthenticator()->authenticate($request);
    }

    public function testAuthenticateRejectsEmptyAudienceArray(): void
    {
        $tokenValue = $this->createJwtToken('RS256');
        $payload = $this->validPayload($this->faker->email(), ['ROLE_USER']);
        $payload['aud'] = [];

        $this->jwtEncoder
            ->expects($this->once())
            ->method('decode')
            ->with($tokenValue)
            ->willReturn($payload);

        $request = Request::create('/api/users');
        $request->headers->set('Authorization', 'Bearer ' . $tokenValue);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Invalid access token claims.');

        $this->createAuthenticator()->authenticate($request);
    }

    public function testAuthenticateRejectsExpiredAccessTokenClaims(): void
    {
        $tokenValue = $this->createJwtToken('RS256');
        $payload = $this->validPayload($this->faker->email(), ['ROLE_USER']);
        $payload['exp'] = time() - 1;

        $this->jwtEncoder
            ->expects($this->once())
            ->method('decode')
            ->with($tokenValue)
            ->willReturn($payload);

        $request = Request::create('/api/users');
        $request->headers->set('Authorization', 'Bearer ' . $tokenValue);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Invalid access token claims.');

        $this->createAuthenticator()->authenticate($request);
    }

    public function testAuthenticateRejectsHeaderWithInvalidBase64(): void
    {
        $token = '%%%.'
            . $this->base64UrlEncode('{}')
            . '.signature';
        $request = Request::create('/api/users');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Invalid access token.');

        $this->createAuthenticator()->authenticate($request);
    }

    public function testAuthenticateRejectsInvalidTimestampType(): void
    {
        $tokenValue = $this->createJwtToken('RS256');
        $payload = $this->validPayload($this->faker->email(), ['ROLE_USER']);
        $payload['nbf'] = 'invalid';

        $this->jwtEncoder
            ->expects($this->once())
            ->method('decode')
            ->with($tokenValue)
            ->willReturn($payload);

        $request = Request::create('/api/users');
        $request->headers->set('Authorization', 'Bearer ' . $tokenValue);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Invalid access token claims.');

        $this->createAuthenticator()->authenticate($request);
    }

    public function testAuthenticateRejectsMissingRolesClaim(): void
    {
        $tokenValue = $this->createJwtToken('RS256');
        $payload = $this->validPayload($this->faker->email(), ['ROLE_USER']);
        unset($payload['roles']);

        $this->jwtEncoder
            ->expects($this->once())
            ->method('decode')
            ->with($tokenValue)
            ->willReturn($payload);

        $request = Request::create('/api/users');
        $request->headers->set('Authorization', 'Bearer ' . $tokenValue);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Invalid access token claims.');

        $this->createAuthenticator()->authenticate($request);
    }

    public function testAuthenticateRejectsRoleClaimWithNonStringValue(): void
    {
        $tokenValue = $this->createJwtToken('RS256');
        $payload = $this->validPayload($this->faker->email(), ['ROLE_USER']);
        $payload['roles'] = ['ROLE_USER', 123];

        $this->jwtEncoder
            ->expects($this->once())
            ->method('decode')
            ->with($tokenValue)
            ->willReturn($payload);

        $request = Request::create('/api/users');
        $request->headers->set('Authorization', 'Bearer ' . $tokenValue);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Invalid access token claims.');

        $this->createAuthenticator()->authenticate($request);
    }

    public function testAuthenticateRejectsMissingSubjectClaim(): void
    {
        $tokenValue = $this->createJwtToken('RS256');
        $payload = $this->validPayload($this->faker->email(), ['ROLE_USER']);
        unset($payload['sub']);

        $this->jwtEncoder
            ->expects($this->once())
            ->method('decode')
            ->with($tokenValue)
            ->willReturn($payload);

        $request = Request::create('/api/users');
        $request->headers->set('Authorization', 'Bearer ' . $tokenValue);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Invalid access token claims.');

        $this->createAuthenticator()->authenticate($request);
    }

    public function testAuthenticateResolvesUserByIdWhenSubjectIsUuid(): void
    {
        $subject = $this->faker->uuid();
        $email = $this->faker->email();
        $tokenValue = $this->createJwtToken('RS256');
        $payload = $this->validPayload($subject, ['ROLE_USER']);
        $user = $this->createDomainUser($email);

        $this->expectUuidSubjectResolution($tokenValue, $payload, $subject, $user);
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

        $this->expectUuidSubjectResolution($tokenValue, $payload, $subject, null);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Authentication required.');

        $this->createAuthenticator()->authenticate($this->createBearerRequest($tokenValue));
    }

    public function testOnAuthenticationSuccessReturnsNull(): void
    {
        $response = $this->createAuthenticator()->onAuthenticationSuccess(
            Request::create('/api/users'),
            $this->createMock(TokenInterface::class),
            'api'
        );

        $this->assertNull($response);
    }

    public function testStartReturnsProblemJsonAndBearerHeader(): void
    {
        $response = $this->createAuthenticator()->start(Request::create('/api/users'));
        $this->assertUnauthorizedProblemResponse($response);
    }

    public function testCreateTokenFallsBackToRoleUserWhenRolesMissing(): void
    {
        $passport = $this->createPassportWithoutRoles($this->faker->email());
        $token = $this->createAuthenticator()->createToken($passport, 'api');

        $this->assertSame(['ROLE_USER'], $token->getRoleNames());
    }

    public function testCreateTokenFallsBackToRoleUserWhenRolesAreInvalid(): void
    {
        $passport = $this->createPassportWithoutRoles($this->faker->email());
        $passport->setAttribute('roles', [1, null, '']);

        $token = $this->createAuthenticator()->createToken($passport, 'api');

        $this->assertSame(['ROLE_USER'], $token->getRoleNames());
    }

    public function testSupportsReturnsFalseWhenBearerHasNoTokenAfterPrefix(): void
    {
        $request = Request::create('/api/users');
        $request->headers->set('Authorization', 'Bearer ');

        $this->assertFalse($this->createAuthenticator()->supports($request));
    }

    public function testSupportsReturnsFalseWhenCookieIsEmptyString(): void
    {
        $request = Request::create('/api/users');
        $request->cookies->set('__Host-auth_token', '');

        $this->assertFalse($this->createAuthenticator()->supports($request));
    }

    public function testCreateTokenDoesNotSetSidWhenAttributeIsNull(): void
    {
        $passport = $this->createPassportWithoutRoles($this->faker->email());

        $token = $this->createAuthenticator()->createToken($passport, 'api');

        $this->assertFalse($token->hasAttribute('sid'));
    }

    public function testCreateTokenDoesNotSetSidWhenAttributeIsEmptyString(): void
    {
        $passport = $this->createPassportWithoutRoles($this->faker->email());
        $passport->setAttribute('sid', '');

        $token = $this->createAuthenticator()->createToken($passport, 'api');

        $this->assertFalse($token->hasAttribute('sid'));
    }

    public function testCreateTokenDoesNotSetSidWhenAttributeIsNotString(): void
    {
        $passport = $this->createPassportWithoutRoles($this->faker->email());
        $passport->setAttribute('sid', 42);

        $token = $this->createAuthenticator()->createToken($passport, 'api');

        $this->assertFalse($token->hasAttribute('sid'));
    }

    public function testCreateTokenFiltersOutEmptyStringRoles(): void
    {
        $passport = $this->createPassportWithoutRoles($this->faker->email());
        $passport->setAttribute('roles', ['', 'ROLE_ADMIN', '']);

        $token = $this->createAuthenticator()->createToken($passport, 'api');

        $this->assertSame(['ROLE_ADMIN'], $token->getRoleNames());
    }

    public function testOnAuthenticationFailureResponseBodyHasRequiredFields(): void
    {
        $response = $this->createAuthenticator()->onAuthenticationFailure(
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
        $response = $this->createAuthenticator()->start(Request::create('/api/users'));

        $body = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('about:blank', $body['type']);
        $this->assertSame('Unauthorized', $body['title']);
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $body['status']);
        $this->assertSame('Authentication required.', $body['detail']);
    }

    private function createPassportWithoutRoles(string $email): SelfValidatingPassport
    {
        $authorizationUser = $this->createAuthorizationUser($email);

        return new SelfValidatingPassport(
            new UserBadge(
                $authorizationUser->getUserIdentifier(),
                static fn () => $authorizationUser
            )
        );
    }

    /**
     * @param array<string, array<int, string>|int|string> $payload
     */
    private function expectUuidSubjectResolution(
        string $tokenValue,
        array $payload,
        string $subject,
        ?\App\User\Domain\Entity\User $user
    ): void {
        $this->jwtEncoder
            ->expects($this->once())
            ->method('decode')
            ->with($tokenValue)
            ->willReturn($payload);

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($subject)
            ->willReturn(null);

        $this->userRepository
            ->expects($this->once())
            ->method('findById')
            ->with($subject)
            ->willReturn($user);
        $this->authSessionRepository
            ->expects($this->once())
            ->method('findById')
            ->with('sid-token')
            ->willReturn($this->createActiveSession('sid-token'));
    }

    private function createBearerRequest(string $tokenValue): Request
    {
        $request = Request::create('/api/users');
        $request->headers->set('Authorization', 'Bearer ' . $tokenValue);

        return $request;
    }

    private function assertUnauthorizedProblemResponse(Response $response): void
    {
        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('Bearer', $response->headers->get('WWW-Authenticate'));
        $this->assertStringContainsString(
            'application/problem+json',
            (string) $response->headers->get('Content-Type')
        );
    }
}
