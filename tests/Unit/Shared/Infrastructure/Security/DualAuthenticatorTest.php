<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Security;

use App\Shared\Infrastructure\Factory\UuidFactory as SharedUuidFactory;
use App\Shared\Infrastructure\Security\DualAuthenticator;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\AuthorizationUserDto;
use App\User\Application\Transformer\UserTransformer;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/** @SuppressWarnings(PHPMD.TooManyMethods) */
final class DualAuthenticatorTest extends UnitTestCase
{
    private JWTEncoderInterface&MockObject $jwtEncoder;
    private UserRepositoryInterface&MockObject $userRepository;
    private UserTransformer $userTransformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->jwtEncoder = $this->createMock(JWTEncoderInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->userTransformer = new UserTransformer(
            new UuidTransformer(new SharedUuidFactory())
        );
    }

    public function testAuthenticateUsesBearerTokenAndResolvesUser(): void
    {
        $email = $this->faker->email();
        $tokenValue = $this->createJwtToken('RS256');
        $payload = $this->validPayload($email, ['ROLE_USER']);
        $user = $this->createDomainUser($email);

        $this->jwtEncoder
            ->expects($this->once())
            ->method('decode')
            ->with($tokenValue)
            ->willReturn($payload);

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $request = Request::create('/api/users');
        $request->headers->set('Authorization', 'Bearer ' . $tokenValue);

        $passport = $this->createAuthenticator()->authenticate($request);

        $this->assertSame(['ROLE_USER'], $passport->getAttribute('roles'));
        $this->assertSame('sid-token', $passport->getAttribute('sid'));
        $this->assertInstanceOf(
            AuthorizationUserDto::class,
            $passport->getUser()
        );
        $this->assertSame($email, $passport->getUser()->getUserIdentifier());
    }

    public function testAuthenticateUsesCookieWhenBearerHeaderMissing(): void
    {
        $tokenValue = $this->createJwtToken('RS256');
        $payload = $this->validPayload(
            sprintf('service-%s', strtolower($this->faker->lexify('????'))),
            ['ROLE_SERVICE']
        );

        $this->jwtEncoder
            ->expects($this->once())
            ->method('decode')
            ->with($tokenValue)
            ->willReturn($payload);

        $this->userRepository
            ->expects($this->never())
            ->method('findByEmail')
            ->with($payload['sub']);

        $this->userRepository
            ->expects($this->never())
            ->method('findById');

        $request = Request::create('/api/users/batch');
        $request->cookies->set('__Host-auth_token', $tokenValue);

        $passport = $this->createAuthenticator()->authenticate($request);
        $user = $passport->getUser();

        $this->assertSame(['ROLE_SERVICE'], $passport->getAttribute('roles'));
        $this->assertSame($payload['sub'], $user->getUserIdentifier());
    }

    public function testAuthenticateRejectsWrongAlgorithm(): void
    {
        $request = Request::create('/api/users');
        $request->headers->set(
            'Authorization',
            'Bearer ' . $this->createJwtToken('HS256')
        );

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Invalid access token.');

        $this->createAuthenticator()->authenticate($request);
    }

    public function testAuthenticateRejectsInvalidIssuerClaimType(): void
    {
        $tokenValue = $this->createJwtToken('RS256');
        $payload = $this->validPayload($this->faker->email(), ['ROLE_USER']);
        $payload['iss'] = ['vilnacrm-user-service'];

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

    public function testAuthenticateAcceptsAudienceClaimArray(): void
    {
        $email = $this->faker->email();
        $tokenValue = $this->createJwtToken('RS256');
        $payload = $this->validPayload($email, ['ROLE_USER']);
        $payload['aud'] = ['vilnacrm-api'];
        $user = $this->createDomainUser($email);

        $this->jwtEncoder
            ->expects($this->once())
            ->method('decode')
            ->with($tokenValue)
            ->willReturn($payload);

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $request = Request::create('/api/users');
        $request->headers->set('Authorization', 'Bearer ' . $tokenValue);

        $passport = $this->createAuthenticator()->authenticate($request);

        $this->assertSame(['ROLE_USER'], $passport->getAttribute('roles'));
        $this->assertInstanceOf(
            AuthorizationUserDto::class,
            $passport->getUser()
        );
        $this->assertSame($email, $passport->getUser()->getUserIdentifier());
    }

    public function testAuthenticateRejectsMissingSidClaim(): void
    {
        $tokenValue = $this->createJwtToken('RS256');
        $payload = $this->validPayload($this->faker->email(), ['ROLE_USER']);
        unset($payload['sid']);

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

    public function testOnAuthenticationFailureReturnsProblemJsonAndBearerHeader(): void
    {
        $response = $this->createAuthenticator()->onAuthenticationFailure(
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
        $authorizationUser = $this->createAuthorizationUser(
            $this->faker->email()
        );
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
        $request->cookies->set('__Host-auth_token', 'cookie-token');

        $supports = $this->createAuthenticator()->supports($request);

        $this->assertTrue($supports);
    }

    public function testAuthenticateRejectsRequestWithoutToken(): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Authentication required.');

        $this->createAuthenticator()->authenticate(Request::create('/api/users'));
    }

    public function testAuthenticateRejectsMalformedTokenSegments(): void
    {
        $request = Request::create('/api/users');
        $request->headers->set('Authorization', 'Bearer malformed-token');

        $this->expectException(CustomUserMessageAuthenticationException::class);
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
        $request->headers->set('Authorization', 'Bearer ' . $token);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Invalid access token.');

        $this->createAuthenticator()->authenticate($request);
    }

    public function testAuthenticateRejectsHeaderDecodedToNonArray(): void
    {
        $token = $this->base64UrlEncode(json_encode('header', JSON_THROW_ON_ERROR))
            . '.'
            . $this->base64UrlEncode('{}')
            . '.signature';
        $request = Request::create('/api/users');
        $request->headers->set('Authorization', 'Bearer ' . $token);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Invalid access token.');

        $this->createAuthenticator()->authenticate($request);
    }

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

        $request = Request::create('/api/users');
        $request->headers->set('Authorization', 'Bearer ' . $tokenValue);

        $passport = $this->createAuthenticator()->authenticate($request);

        $this->assertSame($email, $passport->getUser()->getUserIdentifier());
    }

    public function testAuthenticateRejectsUnknownUuidSubject(): void
    {
        $subject = $this->faker->uuid();
        $tokenValue = $this->createJwtToken('RS256');
        $payload = $this->validPayload($subject, ['ROLE_USER']);

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
            ->willReturn(null);

        $request = Request::create('/api/users');
        $request->headers->set('Authorization', 'Bearer ' . $tokenValue);

        $passport = $this->createAuthenticator()->authenticate($request);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Authentication required.');

        $passport->getUser();
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
        $response = $this->createAuthenticator()->start(
            Request::create('/api/users')
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

    public function testCreateTokenFallsBackToRoleUserWhenRolesMissing(): void
    {
        $authorizationUser = $this->createAuthorizationUser(
            $this->faker->email()
        );
        $passport = new SelfValidatingPassport(
            new UserBadge(
                $authorizationUser->getUserIdentifier(),
                static fn () => $authorizationUser
            )
        );

        $token = $this->createAuthenticator()->createToken($passport, 'api');

        $this->assertSame(['ROLE_USER'], $token->getRoleNames());
    }

    public function testCreateTokenFallsBackToRoleUserWhenRolesAreInvalid(): void
    {
        $authorizationUser = $this->createAuthorizationUser(
            $this->faker->email()
        );
        $passport = new SelfValidatingPassport(
            new UserBadge(
                $authorizationUser->getUserIdentifier(),
                static fn () => $authorizationUser
            )
        );
        $passport->setAttribute('roles', [1, null, '']);

        $token = $this->createAuthenticator()->createToken($passport, 'api');

        $this->assertSame(['ROLE_USER'], $token->getRoleNames());
    }

    private function createAuthenticator(): DualAuthenticator
    {
        return new DualAuthenticator(
            $this->jwtEncoder,
            $this->userRepository,
            $this->userTransformer
        );
    }

    /**
     * @param array<string> $roles
     *
     * @return (int|string|string[])[]
     *
     * @psalm-return array{sub: string, iss: 'vilnacrm-user-service', aud: 'vilnacrm-api', nbf: int<-9, max>, iat: int<-9, max>, exp: int<901, max>, sid: 'sid-token', roles: array<string>}
     */
    private function validPayload(string $subject, array $roles): array
    {
        $now = time();

        return [
            'sub' => $subject,
            'iss' => 'vilnacrm-user-service',
            'aud' => 'vilnacrm-api',
            'nbf' => $now - 10,
            'iat' => $now - 10,
            'exp' => $now + 900,
            'sid' => 'sid-token',
            'roles' => $roles,
        ];
    }

    private function createJwtToken(string $algorithm): string
    {
        $header = ['alg' => $algorithm, 'typ' => 'JWT'];
        $payload = ['sub' => $this->faker->email()];

        return $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR))
            . '.'
            . $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR))
            . '.signature';
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function createAuthorizationUser(string $email): AuthorizationUserDto
    {
        $transformer = new UuidTransformer(new SharedUuidFactory());

        return new AuthorizationUserDto(
            $email,
            $this->faker->name(),
            $this->faker->password(),
            $transformer->transformFromString($this->faker->uuid()),
            true
        );
    }

    private function createDomainUser(string $email): User
    {
        $transformer = new UuidTransformer(new SharedUuidFactory());

        return new User(
            $email,
            $this->faker->name(),
            $this->faker->sha256(),
            $transformer->transformFromString($this->faker->uuid())
        );
    }
}
