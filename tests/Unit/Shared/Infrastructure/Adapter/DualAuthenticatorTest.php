<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Adapter;

use App\User\Application\DTO\AuthorizationUserDto;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class DualAuthenticatorTest extends DualAuthenticatorTestCase
{
    public function testAuthenticateUsesBearerTokenAndResolvesUser(): void
    {
        $email = $this->faker->email();
        $tokenValue = $this->createJwtToken('RS256');
        $payload = $this->validPayload($email, ['ROLE_USER']);
        $user = $this->createDomainUser($email);
        $this->expectJwtDecode($tokenValue, $payload);
        $this->expectUserAndSessionResolution($email, $user);
        $passport = $this->createAuthenticator()->authenticate(
            $this->createBearerRequest($tokenValue)
        );
        $this->assertSame(['ROLE_USER'], $passport->getAttribute('roles'));
        $this->assertSame('sid-token', $passport->getAttribute('sid'));
        $this->assertInstanceOf(AuthorizationUserDto::class, $passport->getUser());
        $this->assertSame($email, $passport->getUser()->getUserIdentifier());
    }

    public function testAuthenticateUsesCookieWhenBearerHeaderMissing(): void
    {
        $tokenValue = $this->createJwtToken('RS256');
        $subject = sprintf('service-%s', strtolower($this->faker->lexify('????')));
        $payload = $this->validPayload($subject, ['ROLE_SERVICE']);
        $this->expectJwtDecode($tokenValue, $payload);
        $this->userRepository->expects($this->never())->method('findByEmail')->with($subject);
        $this->userRepository->expects($this->never())->method('findById');
        $request = Request::create('/api/users/batch');
        $request->cookies->set('__Host-auth_token', $tokenValue);
        $passport = $this->createAuthenticator()->authenticate($request);
        $this->assertSame(['ROLE_SERVICE'], $passport->getAttribute('roles'));
        $this->assertSame($subject, $passport->getUser()->getUserIdentifier());
    }

    public function testAuthenticateRejectsWrongAlgorithm(): void
    {
        $request = Request::create('/api/users');
        $request->headers->set(
            'Authorization',
            'Bearer ' . $this->createJwtToken('HS256')
        );

        $this->expectException(
            CustomUserMessageAuthenticationException::class
        );
        $this->expectExceptionMessage('Invalid access token.');

        $this->createAuthenticator()->authenticate($request);
    }

    public function testAuthenticateRejectsInvalidIssuerClaimType(): void
    {
        $payload = $this->validPayload($this->faker->email(), ['ROLE_USER']);
        $payload['iss'] = ['vilnacrm-user-service'];
        $this->expectRejectWithInvalidClaims($payload);
    }

    public function testAuthenticateRejectsAudienceClaimArray(): void
    {
        $payload = $this->validPayload($this->faker->email(), ['ROLE_USER']);
        $payload['aud'] = ['vilnacrm-api'];
        $this->expectRejectWithInvalidClaims($payload);
    }

    public function testAuthenticateRejectsSidlessUnknownSubject(): void
    {
        $subject = sprintf('oauth-client-%s', strtolower($this->faker->lexify('????')));
        $tokenValue = $this->createJwtToken('RS256');
        $this->expectJwtDecode(
            $tokenValue,
            $this->clientCredentialsPayload($subject)
        );
        $this->authSessionRepository->expects($this->once())
            ->method('findById')
            ->with('')
            ->willReturn(null);
        $this->userRepository->expects($this->never())->method('findByEmail');
        $this->userRepository->expects($this->never())->method('findById');
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Invalid access token claims.');

        $this->createAuthenticator()->authenticate(
            $this->createBearerRequest($tokenValue)
        );
    }

    public function testAuthenticateRejectsWhenSessionIsMissing(): void
    {
        $tokenValue = $this->createJwtToken('RS256');
        $payload = $this->validPayload(
            $this->faker->email(),
            ['ROLE_USER']
        );
        $this->expectJwtDecode($tokenValue, $payload);
        $this->authSessionRepository->expects($this->once())
            ->method('findById')
            ->with('sid-token')
            ->willReturn(null);
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

    public function testAuthenticateRejectsWhenSessionIsRevoked(): void
    {
        $tokenValue = $this->createJwtToken('RS256');
        $payload = $this->validPayload($this->faker->email(), ['ROLE_USER']);
        $revokedSession = $this->createActiveSession('sid-token');
        $revokedSession->revoke();
        $this->expectJwtDecode($tokenValue, $payload);
        $this->authSessionRepository->expects($this->once())
            ->method('findById')
            ->with('sid-token')
            ->willReturn($revokedSession);
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

    public function testAuthenticateRejectsWhenSessionIsExpired(): void
    {
        $tokenValue = $this->createJwtToken('RS256');
        $payload = $this->validPayload(
            $this->faker->email(),
            ['ROLE_USER']
        );
        $this->expectJwtDecode($tokenValue, $payload);
        $this->authSessionRepository->expects($this->once())
            ->method('findById')
            ->with('sid-token')
            ->willReturn($this->createExpiredSession());
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

    public function testOnAuthenticationFailureReturnsProblemJsonAndBearerHeader(): void
    {
        $response = $this->createAuthenticator()
            ->onAuthenticationFailure(
                Request::create('/api/users'),
                new AuthenticationException('invalid')
            );

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame(
            'Bearer',
            $response->headers->get('WWW-Authenticate')
        );
        $this->assertStringContainsString(
            'application/problem+json',
            (string) $response->headers->get('Content-Type')
        );
    }

    public function testCreateTokenUsesRolesAndSidFromPassportAttributes(): void
    {
        $authorizationUser = $this->createAuthorizationUser($this->faker->email());
        $passport = new SelfValidatingPassport(
            new UserBadge(
                $authorizationUser->getUserIdentifier(),
                static fn () => $authorizationUser
            )
        );
        $passport->setAttribute('roles', ['ROLE_USER']);
        $passport->setAttribute('sid', 'sid-test');
        $token = $this->createAuthenticator()->createToken($passport, 'api');
        $this->assertInstanceOf(TokenInterface::class, $token);
        $this->assertSame(['ROLE_USER'], $token->getRoleNames());
        $this->assertSame('sid-test', $token->getAttribute('sid'));
    }

    public function testSupportsReturnsFalseWithoutToken(): void
    {
        $supports = $this->createAuthenticator()->supports(
            Request::create('/api/users')
        );

        $this->assertFalse($supports);
    }

    public function testSupportsReturnsTrueWhenCookieTokenIsPresent(): void
    {
        $request = Request::create('/api/users');
        $request->cookies->set(
            '__Host-auth_token',
            'cookie-token'
        );

        $supports = $this->createAuthenticator()->supports(
            $request
        );

        $this->assertTrue($supports);
    }

    public function testSupportsReturnsFalseOnPublicRouteEvenWhenTokenExists(): void
    {
        $request = Request::create('/api/signin', 'POST');
        $request->cookies->set(
            '__Host-auth_token',
            'cookie-token'
        );

        $supports = $this->createAuthenticator()->supports(
            $request
        );

        $this->assertFalse($supports);
    }

    public function testAuthenticateRejectsRequestWithoutToken(): void
    {
        $this->expectException(
            CustomUserMessageAuthenticationException::class
        );
        $this->expectExceptionMessage('Authentication required.');

        $this->createAuthenticator()->authenticate(
            Request::create('/api/users')
        );
    }

    public function testAuthenticateRejectsMalformedTokenSegments(): void
    {
        $request = Request::create('/api/users');
        $request->headers->set(
            'Authorization',
            'Bearer malformed-token'
        );

        $this->expectException(
            CustomUserMessageAuthenticationException::class
        );
        $this->expectExceptionMessage('Invalid access token.');

        $this->createAuthenticator()->authenticate($request);
    }

    public function testAuthenticateRejectsHeaderWithInvalidJson(): void
    {
        $token = $this->base64UrlEncode('{')
            . '.'
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

    public function testAuthenticateRejectsHeaderDecodedToNonArray(): void
    {
        $token = $this->base64UrlEncode(
            json_encode('header', JSON_THROW_ON_ERROR)
        )
            . '.'
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

    /**
     * @return array<string, int|string>
     */
    private function clientCredentialsPayload(string $subject): array
    {
        return [
            'client_id' => $subject,
            'iss' => 'vilnacrm-user-service',
            'aud' => 'vilnacrm-api',
            'nbf' => time() - 10,
            'exp' => time() + 900,
        ];
    }
}
