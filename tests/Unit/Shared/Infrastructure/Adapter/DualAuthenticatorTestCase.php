<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Adapter;

use App\Shared\Infrastructure\Adapter\DualAuthenticator;
use App\Shared\Infrastructure\Factory\AccessTokenPassportFactory;
use App\Shared\Infrastructure\Factory\UuidFactory as SharedUuidFactory;
use App\Shared\Infrastructure\Resolver\AccessTokenUserResolver;
use App\Shared\Infrastructure\Resolver\PublicAccessResolver;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Shared\Infrastructure\Validator\AccessTokenValidator;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\AuthorizationUserDto;
use App\User\Application\Transformer\UserTransformer;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

abstract class DualAuthenticatorTestCase extends UnitTestCase
{
    protected JWTEncoderInterface&MockObject $jwtEncoder;
    protected UserRepositoryInterface&MockObject $userRepository;
    protected AuthSessionRepositoryInterface&MockObject $authSessionRepository;
    protected UserTransformer $userTransformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->jwtEncoder = $this->createMock(
            JWTEncoderInterface::class
        );
        $this->userRepository = $this->createMock(
            UserRepositoryInterface::class
        );
        $this->authSessionRepository = $this->createMock(
            AuthSessionRepositoryInterface::class
        );
        $this->userTransformer = new UserTransformer(
            new UuidTransformer(new SharedUuidFactory())
        );
    }

    protected function createAuthenticator(): DualAuthenticator
    {
        return new DualAuthenticator(
            $this->createPassportFactory(),
            new PublicAccessResolver($this->publicRoutes())
        );
    }

    protected function createPassportFactory(): AccessTokenPassportFactory
    {
        return new AccessTokenPassportFactory(
            new AccessTokenValidator($this->jwtEncoder),
            new AccessTokenUserResolver(
                $this->userRepository,
                $this->userTransformer,
                $this->authSessionRepository
            )
        );
    }

    /**
     * @return array<array{pattern: string, methods?: array<string>}>
     */
    protected function publicRoutes(): array
    {
        return [
            ['pattern' => '#^/api/users$#', 'methods' => ['POST']],
            [
                'pattern' => '#^/api/users/confirm$#',
                'methods' => ['PATCH'],
            ],
            ['pattern' => '#^/api/reset-password#'],
            ['pattern' => '#^/api/signin#'],
            ['pattern' => '#^/api/token$#', 'methods' => ['POST']],
            ['pattern' => '#^/api/docs#'],
            ['pattern' => '#^/api/health#'],
            ['pattern' => '#^/api/oauth#'],
            ['pattern' => '#^/api/\.well-known#'],
            ['pattern' => '#^/healthz#'],
        ];
    }

    /**
     * @param array<string> $roles
     *
     * @return array<int|string|array<string>>
     */
    protected function validPayload(
        string $subject,
        array $roles
    ): array {
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

    protected function createJwtToken(string $algorithm): string
    {
        $header = ['alg' => $algorithm, 'typ' => 'JWT'];
        $payload = ['sub' => $this->faker->email()];

        return $this->base64UrlEncode(
            json_encode($header, JSON_THROW_ON_ERROR)
        )
            . '.'
            . $this->base64UrlEncode(
                json_encode($payload, JSON_THROW_ON_ERROR)
            )
            . '.signature';
    }

    protected function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    protected function createAuthorizationUser(
        string $email
    ): AuthorizationUserDto {
        $transformer = new UuidTransformer(new SharedUuidFactory());

        return new AuthorizationUserDto(
            $email,
            $this->faker->name(),
            $this->faker->password(),
            $transformer->transformFromString($this->faker->uuid()),
            true
        );
    }

    protected function createDomainUser(string $email): User
    {
        $transformer = new UuidTransformer(new SharedUuidFactory());

        return new User(
            $email,
            $this->faker->name(),
            $this->faker->sha256(),
            $transformer->transformFromString($this->faker->uuid())
        );
    }

    protected function createActiveSession(
        string $sessionId
    ): AuthSession {
        return new AuthSession(
            $sessionId,
            $this->faker->uuid(),
            '127.0.0.1',
            'Test Agent',
            new DateTimeImmutable('-10 minutes'),
            new DateTimeImmutable('+10 minutes'),
            false
        );
    }

    protected function createPassportWithoutRoles(
        string $email
    ): SelfValidatingPassport {
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
    protected function expectUuidSubjectResolution(
        string $tokenValue,
        array $payload,
        string $subject,
        ?User $user
    ): void {
        $this->expectJwtDecode($tokenValue, $payload);
        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($subject)->willReturn(null);
        $this->userRepository->expects($this->once())
            ->method('findById')
            ->with($subject)->willReturn($user);
        $this->authSessionRepository->expects($this->once())
            ->method('findById')
            ->with('sid-token')
            ->willReturn($this->createActiveSession('sid-token'));
    }

    protected function createBearerRequest(
        string $tokenValue
    ): Request {
        $request = Request::create('/api/users');
        $request->headers->set(
            'Authorization',
            'Bearer ' . $tokenValue
        );

        return $request;
    }

    protected function assertUnauthorizedProblemResponse(
        Response $response
    ): void {
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

    protected function expectJwtDecode(
        string $tokenValue,
        mixed $payload
    ): void {
        $this->jwtEncoder->expects($this->once())
            ->method('decode')
            ->with($tokenValue)->willReturn($payload);
    }

    protected function expectUserAndSessionResolution(
        string $email,
        User $user
    ): void {
        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($email)->willReturn($user);
        $this->authSessionRepository->expects($this->once())
            ->method('findById')
            ->with('sid-token')
            ->willReturn($this->createActiveSession('sid-token'));
    }

    protected function createExpiredSession(): AuthSession
    {
        return new AuthSession(
            'sid-token',
            $this->faker->uuid(),
            '127.0.0.1',
            'Test Agent',
            new DateTimeImmutable('-2 hours'),
            new DateTimeImmutable('-1 hour'),
            false
        );
    }
}
