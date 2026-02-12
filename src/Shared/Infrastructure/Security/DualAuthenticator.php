<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Security;

use App\User\Application\Transformer\UserTransformer;
use App\User\Domain\Entity\UserInterface as DomainUserInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use JsonException;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @infection-ignore-all
 *
 * @phpstan-type JwtClaimValue array<int, string>|bool|float|int|string|null
 * @phpstan-type JwtPayload array<string, JwtClaimValue>
 */
final class DualAuthenticator extends AbstractAuthenticator implements
    AuthenticationEntryPointInterface
{
    private const AUTH_COOKIE_NAME = '__Host-auth_token';
    private const JWT_ISSUER = 'vilnacrm-user-service';
    private const JWT_AUDIENCE = 'vilnacrm-api';
    private const JWT_ALGORITHM = 'RS256';

    public function __construct(
        private readonly JWTEncoderInterface $jwtEncoder,
        private readonly UserRepositoryInterface $userRepository,
        private readonly UserTransformer $userTransformer,
    ) {
    }

    /**
     * @return bool
     */
    #[\Override]
    public function supports(Request $request): ?bool
    {
        return $this->extractToken($request) !== null;
    }

    /**
     * @return SelfValidatingPassport
     */
    #[\Override]
    public function authenticate(Request $request): Passport
    {
        $token = $this->requireToken($request);
        $header = $this->decodeHeader($token);
        $this->validateAlgorithm($header);
        $payload = $this->decodePayload($token);
        $this->validateClaims($payload);

        return $this->buildPassport($payload);
    }

    /**
     * @return PostAuthenticationToken
     */
    #[\Override]
    public function createToken(
        Passport $passport,
        string $firewallName
    ): TokenInterface {
        $roles = $this->extractTokenRoles($passport);
        $token = new PostAuthenticationToken(
            $passport->getUser(),
            $firewallName,
            $roles
        );

        $sid = $passport->getAttribute('sid');
        if (is_string($sid) && $sid !== '') {
            $token->setAttribute('sid', $sid);
        }

        return $token;
    }

    /**
     * @return null
     */
    #[\Override]
    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
        return null;
    }

    /**
     * @return JsonResponse
     */
    #[\Override]
    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception
    ): ?Response {
        return $this->buildFailureResponse(
            'Authentication required.'
        );
    }

    /**
     * @return JsonResponse
     */
    #[\Override]
    public function start(
        Request $request,
        ?AuthenticationException $authException = null
    ): Response {
        return $this->buildFailureResponse('Authentication required.');
    }

    /**
     * @param JwtPayload $payload
     */
    private function buildPassport(array $payload): SelfValidatingPassport
    {
        $subject = $this->extractSubject($payload);
        $roles = $this->extractRoles($payload);
        $sid = $this->extractSid($payload);

        $passport = new SelfValidatingPassport(
            new UserBadge(
                $subject,
                fn (string $identifier): ServicePrincipal|\App\User\Application\DTO\AuthorizationUserDto => $this->resolveUser(
                    $identifier,
                    $roles
                )
            )
        );
        $passport->setAttribute('roles', $roles);
        $passport->setAttribute('sid', $sid);

        return $passport;
    }

    /**
     * @param JwtPayload $header
     */
    private function validateAlgorithm(array $header): void
    {
        $algorithm = $header['alg'] ?? null;
        if (
            !is_string($algorithm)
            || $algorithm !== self::JWT_ALGORITHM
        ) {
            throw new CustomUserMessageAuthenticationException(
                'Invalid access token.'
            );
        }
    }

    /**
     * @param JwtPayload $payload
     */
    private function validateClaims(array $payload): void
    {
        if (!$this->hasExpectedIssuer($payload)) {
            throw new CustomUserMessageAuthenticationException(
                'Invalid access token claims.'
            );
        }

        if (!$this->hasExpectedAudience($payload)) {
            throw new CustomUserMessageAuthenticationException(
                'Invalid access token claims.'
            );
        }

        $now = time();
        $notBefore = $this->extractTimestamp($payload, 'nbf');
        $expiresAt = $this->extractTimestamp($payload, 'exp');

        if ($notBefore > $now || $expiresAt <= $now) {
            throw new CustomUserMessageAuthenticationException(
                'Invalid access token claims.'
            );
        }
    }

    /**
     * @param JwtPayload $payload
     */
    private function hasExpectedIssuer(array $payload): bool
    {
        $issuer = $payload['iss'] ?? null;

        return is_string($issuer)
            && $issuer === self::JWT_ISSUER;
    }

    /**
     * @param JwtPayload $payload
     */
    private function hasExpectedAudience(array $payload): bool
    {
        $audience = $payload['aud'] ?? null;

        if (is_string($audience)) {
            return $audience === self::JWT_AUDIENCE;
        }

        if (!is_array($audience) || $audience === []) {
            return false;
        }

        $hasExpectedAudience = false;
        foreach ($audience as $value) {
            if (!is_string($value) || $value === '') {
                return false;
            }

            if ($value === self::JWT_AUDIENCE) {
                $hasExpectedAudience = true;
            }
        }

        return $hasExpectedAudience;
    }

    /**
     * @param JwtPayload $payload
     */
    private function extractTimestamp(
        array $payload,
        string $field
    ): int {
        $value = $payload[$field] ?? null;
        if (!is_int($value)) {
            throw new CustomUserMessageAuthenticationException(
                'Invalid access token claims.'
            );
        }

        return $value;
    }

    /**
     * @param JwtPayload $payload
     */
    private function extractSubject(array $payload): string
    {
        $subject = $payload['sub'] ?? null;
        if (!is_string($subject) || $subject === '') {
            throw new CustomUserMessageAuthenticationException(
                'Invalid access token claims.'
            );
        }

        return $subject;
    }

    /**
     * @param JwtPayload $payload
     */
    private function extractSid(array $payload): string
    {
        $sid = $payload['sid'] ?? null;
        if (!is_string($sid) || $sid === '') {
            throw new CustomUserMessageAuthenticationException(
                'Invalid access token claims.'
            );
        }

        return $sid;
    }

    /**
     * @param JwtPayload $payload
     *
     * @return string[]
     *
     * @psalm-return non-empty-list<non-empty-string>
     */
    private function extractRoles(array $payload): array
    {
        $rawRoles = $payload['roles'] ?? null;
        if (!is_array($rawRoles) || $rawRoles === []) {
            throw new CustomUserMessageAuthenticationException(
                'Invalid access token claims.'
            );
        }

        $roles = [];
        foreach ($rawRoles as $role) {
            if (!is_string($role) || $role === '') {
                throw new CustomUserMessageAuthenticationException(
                    'Invalid access token claims.'
                );
            }

            $roles[] = $role;
        }

        return array_values(array_unique($roles));
    }

    /**
     * @param array<string> $roles
     *
     * @return ServicePrincipal|\App\User\Application\DTO\AuthorizationUserDto
     */
    private function resolveUser(
        string $subject,
        array $roles
    ): \App\User\Application\DTO\AuthorizationUserDto|ServicePrincipal {
        if (in_array('ROLE_SERVICE', $roles, true)) {
            return new ServicePrincipal($subject, $roles);
        }

        $user = $this->userRepository->findByEmail($subject);
        if ($user instanceof DomainUserInterface) {
            return $this->userTransformer->transformToAuthorizationUser($user);
        }

        if ($this->looksLikeUuid($subject)) {
            $user = $this->userRepository->findById($subject);
            if ($user instanceof DomainUserInterface) {
                return $this->userTransformer->transformToAuthorizationUser(
                    $user
                );
            }
        }

        throw new CustomUserMessageAuthenticationException(
            'Authentication required.'
        );
    }

    /**
     * @return JwtPayload
     */
    private function decodeHeader(string $token): array
    {
        $parts = explode('.', $token);
        $headerPart = $parts[0] ?? null;
        if (count($parts) !== 3 || !is_string($headerPart) || $headerPart === '') {
            throw new CustomUserMessageAuthenticationException(
                'Invalid access token.'
            );
        }

        $headerJson = $this->decodeBase64Url($headerPart);
        $header = $this->decodeJsonObject($headerJson);

        if (!is_array($header)) {
            throw new CustomUserMessageAuthenticationException(
                'Invalid access token.'
            );
        }

        return $header;
    }

    /**
     * @return JwtPayload
     */
    private function decodePayload(string $token): array
    {
        try {
            $payload = $this->jwtEncoder->decode($token);
        } catch (JWTDecodeFailureException) {
            throw new CustomUserMessageAuthenticationException(
                'Invalid access token.'
            );
        }

        if (!is_array($payload)) {
            throw new CustomUserMessageAuthenticationException(
                'Invalid access token.'
            );
        }

        return $payload;
    }

    private function decodeBase64Url(string $value): string
    {
        $decoded = base64_decode(strtr($value, '-_', '+/'), true);
        if (!is_string($decoded) || $decoded === '') {
            throw new CustomUserMessageAuthenticationException(
                'Invalid access token.'
            );
        }

        return $decoded;
    }

    private function requireToken(Request $request): string
    {
        $token = $this->extractToken($request);
        if ($token === null) {
            throw new CustomUserMessageAuthenticationException(
                'Authentication required.'
            );
        }

        return $token;
    }

    /**
     * @return JwtPayload
     */
    private function decodeJsonObject(string $json): array
    {
        try {
            $decodedObject = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new CustomUserMessageAuthenticationException(
                'Invalid access token.'
            );
        }

        if (!is_array($decodedObject)) {
            throw new CustomUserMessageAuthenticationException(
                'Invalid access token.'
            );
        }

        return $decodedObject;
    }

    private function extractToken(Request $request): ?string
    {
        $authorization = $request->headers->get('Authorization', '');
        if (
            str_starts_with($authorization, 'Bearer ')
            && strlen($authorization) > 7
        ) {
            return substr($authorization, 7);
        }

        $cookieToken = $request->cookies->get(self::AUTH_COOKIE_NAME);
        if (is_string($cookieToken) && $cookieToken !== '') {
            return $cookieToken;
        }

        return null;
    }

    /**
     * @return string[]
     *
     * @psalm-return list{string,...}
     */
    private function extractTokenRoles(Passport $passport): array
    {
        $rawRoles = $passport->getAttribute('roles');
        if (!is_array($rawRoles)) {
            return ['ROLE_USER'];
        }

        $roles = [];
        foreach ($rawRoles as $role) {
            if (is_string($role) && $role !== '') {
                $roles[] = $role;
            }
        }

        return $roles === [] ? ['ROLE_USER'] : array_values($roles);
    }

    private function buildFailureResponse(string $detail): JsonResponse
    {
        return new JsonResponse(
            [
                'type' => 'about:blank',
                'title' => 'Unauthorized',
                'status' => Response::HTTP_UNAUTHORIZED,
                'detail' => $detail,
            ],
            Response::HTTP_UNAUTHORIZED,
            [
                'Content-Type' => 'application/problem+json',
                'WWW-Authenticate' => 'Bearer',
            ]
        );
    }

    private function looksLikeUuid(string $subject): bool
    {
        return preg_match(
            '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-8][0-9a-fA-F]{3}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/',
            $subject
        ) === 1;
    }
}
