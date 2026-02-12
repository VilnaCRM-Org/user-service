<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use App\User\Application\DTO\AuthorizationUserDto;
use App\User\Domain\Entity\User;
use PHPUnit\Framework\Assert;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

trait AuthenticatedUserContextTrait
{
    private const JWT_ISSUER = 'vilnacrm-user-service';
    private const JWT_AUDIENCE = 'vilnacrm-api';
    private const JWT_ACCESS_TTL_SECONDS = 900;

    /**
     * @Given I am authenticated via bearer token as user :email
     */
    public function iAmAuthenticatedViaBearerTokenAsUser(string $email): void
    {
        $this->authenticateAsUserEmail($email);
    }

    /**
     * @Given I have a valid Bearer token for user :email
     */
    public function iHaveAValidBearerTokenForUser(string $email): void
    {
        $this->authenticateAsUserEmail($email);
    }

    /**
     * @Given I have a valid session cookie for user :email
     */
    public function iHaveAValidSessionCookieForUser(string $email): void
    {
        $user = $this->resolveAuthenticationUser($email, null);
        $cookieToken = $this->testAccessTokenFactory->createToken(
            $user->getId(),
            ['ROLE_USER']
        );

        $this->state->currentUserEmail = $user->getEmail();
        $this->state->useAuthCookie = true;
        $this->state->authCookieToken = $cookieToken;
    }

    /**
     * @Given I have an invalid Bearer token
     */
    public function iHaveAnInvalidBearerToken(): void
    {
        $this->state->useAuthCookie = false;
        $this->state->authCookieToken = '';
        $this->state->accessToken = 'invalid-token-value';
    }

    /**
     * @Given I have a JWT with issuer :issuer
     */
    public function iHaveAJwtWithIssuer(string $issuer): void
    {
        $payload = $this->createDefaultJwtPayload();
        $payload['iss'] = $issuer;

        $this->state->accessToken = $this->createSignedJwt($payload);
        $this->state->useAuthCookie = false;
        $this->state->authCookieToken = '';
    }

    /**
     * @Given I have a JWT with audience :audience
     */
    public function iHaveAJwtWithAudience(string $audience): void
    {
        $payload = $this->createDefaultJwtPayload();
        $payload['aud'] = $audience;

        $this->state->accessToken = $this->createSignedJwt($payload);
        $this->state->useAuthCookie = false;
        $this->state->authCookieToken = '';
    }

    /**
     * @Given I have a JWT signed with algorithm :algorithm
     */
    public function iHaveAJwtSignedWithAlgorithm(string $algorithm): void
    {
        $header = ['typ' => 'JWT', 'alg' => $algorithm];
        $payload = $this->createDefaultJwtPayload();

        $this->state->accessToken = sprintf(
            '%s.%s.%s',
            $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR)),
            $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR)),
            'signature'
        );
        $this->state->useAuthCookie = false;
        $this->state->authCookieToken = '';
    }

    /**
     * @Given I have an expired JWT
     */
    public function iHaveAnExpiredJwt(): void
    {
        $payload = $this->createDefaultJwtPayload();
        $payload['exp'] = time() - 1;

        $this->state->accessToken = $this->createSignedJwt($payload);
        $this->state->useAuthCookie = false;
        $this->state->authCookieToken = '';
    }

    /**
     * @Given I have a JWT with nbf set to 1 hour in the future
     */
    public function iHaveAJwtWithNbfSetToHourInFuture(): void
    {
        $payload = $this->createDefaultJwtPayload();
        $payload['nbf'] = time() + 3600;

        $this->state->accessToken = $this->createSignedJwt($payload);
        $this->state->useAuthCookie = false;
        $this->state->authCookieToken = '';
    }

    /**
     * @Given I have a JWT with issuer as array :issuerJson
     */
    public function iHaveAJwtWithIssuerAsArray(string $issuerJson): void
    {
        $header = ['typ' => 'JWT', 'alg' => 'RS256'];
        $payload = $this->createDefaultJwtPayload();
        $payload['iss'] = $this->decodeJsonArray($issuerJson);

        $this->state->accessToken = sprintf(
            '%s.%s.%s',
            $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR)),
            $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR)),
            'signature'
        );
        $this->state->useAuthCookie = false;
        $this->state->authCookieToken = '';
    }

    /**
     * @Given I have a JWT with issuer as array ["vilnacrm-user-service"]
     */
    public function iHaveAJwtWithIssuerAsSingleValueArray(): void
    {
        $this->iHaveAJwtWithIssuerAsArray('["vilnacrm-user-service"]');
    }

    /**
     * @Given I have a JWT without the :claim claim
     */
    public function iHaveAJwtWithoutTheClaim(string $claim): void
    {
        $normalizedClaim = trim($claim, "\"'");
        $payload = $this->createDefaultJwtPayload();
        unset($payload[$normalizedClaim]);

        $this->state->accessToken = $this->createSignedJwt($payload);
        $this->state->useAuthCookie = false;
        $this->state->authCookieToken = '';
    }

    /**
     * @Given I have a JWT with tampered payload
     */
    public function iHaveAJwtWithTamperedPayload(): void
    {
        $signedToken = $this->createSignedJwt($this->createDefaultJwtPayload());
        $parts = explode('.', $signedToken);
        if (count($parts) !== 3) {
            throw new \RuntimeException('Could not tamper JWT payload.');
        }

        $tamperedPayload = ['sub' => 'tampered'];

        $this->state->accessToken = sprintf(
            '%s.%s.%s',
            $parts[0],
            $this->base64UrlEncode(
                json_encode($tamperedPayload, JSON_THROW_ON_ERROR)
            ),
            $parts[2]
        );
        $this->state->useAuthCookie = false;
        $this->state->authCookieToken = '';
    }

    /**
     * @Given I have a malformed JWT :jwt
     */
    public function iHaveAMalformedJwt(string $jwt): void
    {
        $this->state->accessToken = $jwt;
        $this->state->useAuthCookie = false;
        $this->state->authCookieToken = '';
    }

    /**
     * @Given I have an empty Authorization header
     */
    public function iHaveAnEmptyAuthorizationHeader(): void
    {
        $this->state->accessToken = ' ';
        $this->state->useAuthCookie = false;
        $this->state->authCookieToken = '';
    }

    /**
     * @Given I have a Bearer token with extra leading space
     */
    public function iHaveABearerTokenWithExtraLeadingSpace(): void
    {
        $this->state->accessToken = ' ' . $this->createSignedJwt(
            $this->createDefaultJwtPayload()
        );
        $this->state->useAuthCookie = false;
        $this->state->authCookieToken = '';
    }

    /**
     * @Given I have a JWT with valid claims and an extra claim :claim = :value
     */
    public function iHaveAJwtWithValidClaimsAndExtraClaim(
        string $claim,
        string $value
    ): void {
        $payload = $this->createDefaultJwtPayload();
        $normalizedClaim = trim($claim, "\"'");
        $payload[$normalizedClaim] = trim($value, "\"'");

        $this->state->accessToken = $this->createSignedJwt($payload);
        $this->state->useAuthCookie = false;
        $this->state->authCookieToken = '';
    }

    /**
     * @Given I have a JWT with audience as array :audienceJson
     */
    public function iHaveAJwtWithAudienceAsArray(string $audienceJson): void
    {
        $header = ['typ' => 'JWT', 'alg' => 'RS256'];
        $payload = $this->createDefaultJwtPayload();
        $payload['aud'] = $this->decodeJsonArray($audienceJson);

        $this->state->accessToken = sprintf(
            '%s.%s.%s',
            $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR)),
            $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR)),
            'signature'
        );
        $this->state->useAuthCookie = false;
        $this->state->authCookieToken = '';
    }

    /**
     * @Given I have a JWT with audience as array ["vilnacrm-api"]
     */
    public function iHaveAJwtWithAudienceAsSingleValueArray(): void
    {
        $this->iHaveAJwtWithAudienceAsArray('["vilnacrm-api"]');
    }

    /**
     * @Given I have a JWT with nbf set to the current time
     */
    public function iHaveAJwtWithNbfSetToCurrentTime(): void
    {
        $payload = $this->createDefaultJwtPayload();
        $payload['nbf'] = time();

        $this->state->accessToken = $this->createSignedJwt($payload);
        $this->state->useAuthCookie = false;
        $this->state->authCookieToken = '';
    }

    /**
     * @Given I have a JWT with exp set to the current time
     */
    public function iHaveAJwtWithExpSetToCurrentTime(): void
    {
        $payload = $this->createDefaultJwtPayload();
        $payload['exp'] = time();

        $this->state->accessToken = $this->createSignedJwt($payload);
        $this->state->useAuthCookie = false;
        $this->state->authCookieToken = '';
    }

    /**
     * @Given I have a JWT with only header and payload :jwt
     */
    public function iHaveAJwtWithOnlyHeaderAndPayload(string $jwt): void
    {
        $this->iHaveAMalformedJwt($jwt);
    }

    /**
     * @Given I have a JWT with empty signature :jwt
     */
    public function iHaveAJwtWithEmptySignature(string $jwt): void
    {
        $this->iHaveAMalformedJwt($jwt);
    }

    /**
     * @Given I am authenticated as user :email
     */
    public function iAmAuthenticatedAsUser(string $email): void
    {
        $this->authenticateAsUserEmail($email);
    }

    /**
     * @Given I am authenticated as user :email with role :role
     */
    public function iAmAuthenticatedAsUserWithRole(
        string $email,
        string $role
    ): void {
        $this->authenticateAsUserEmail($email, [$role]);
    }

    /**
     * @Given I am authenticated as user :email with id :id
     */
    public function iAmAuthenticatedAsUserWithId(
        string $email,
        string $id
    ): void {
        $this->authenticateAsUserEmail($email, ['ROLE_USER'], $id);
    }

    /**
     * @Given I am authenticated via session cookie as user :email
     */
    public function iAmAuthenticatedViaSessionCookieAsUser(string $email): void
    {
        $existingAccessToken = $this->state->accessToken;
        $this->authenticateAsUserEmail($email, ['ROLE_USER'], null, true);

        if (is_string($existingAccessToken) && $existingAccessToken !== '') {
            $this->state->accessToken = $existingAccessToken;
        }
    }

    /**
     * @Given I am authenticated with role :role
     */
    public function iAmAuthenticatedWithRole(string $role): void
    {
        $this->state->useAuthCookie = false;
        $this->state->authCookieToken = '';
        $this->state->accessToken = $this->testAccessTokenFactory->createToken(
            sprintf('service-%s', strtolower($this->faker->lexify('????'))),
            [$role]
        );

        $authorizationUser = new AuthorizationUserDto(
            sprintf('service-%s@example.test', strtolower($this->faker->lexify('????'))),
            strtoupper($this->faker->lexify('??')),
            $this->faker->sha256(),
            $this->transformer->transformFromSymfonyUuid($this->uuidFactory->create()),
            true
        );

        $this->tokenStorage->setToken(
            new UsernamePasswordToken(
                $authorizationUser,
                'behat-service-token',
                [$role]
            )
        );
    }

    /**
     * @Then the authenticated user should be :email
     */
    public function theAuthenticatedUserShouldBe(string $email): void
    {
        $token = $this->tokenStorage->getToken();
        Assert::assertNotNull($token);

        $authenticatedUser = $token->getUser();
        if ($authenticatedUser instanceof AuthorizationUserDto) {
            Assert::assertSame($email, $authenticatedUser->getUserIdentifier());

            return;
        }

        if (is_string($authenticatedUser)) {
            Assert::assertSame($email, $authenticatedUser);

            return;
        }

        if (
            is_object($authenticatedUser)
            && method_exists($authenticatedUser, 'getUserIdentifier')
        ) {
            $identifier = $authenticatedUser->getUserIdentifier();
            Assert::assertIsString($identifier);
            Assert::assertSame($email, $identifier);

            return;
        }

        throw new \RuntimeException('Unable to resolve authenticated user.');
    }

    /**
     * @Given I am authenticated with the access token
     */
    public function iAmAuthenticatedWithTheAccessToken(): void
    {
        $accessToken = $this->state->accessToken;

        Assert::assertIsString($accessToken);
        Assert::assertNotSame('', $accessToken);

        $this->authenticateFromAccessToken($accessToken);
    }

    /**
     * @Given I am authenticated with the stored access token
     */
    public function iAmAuthenticatedWithTheStoredAccessToken(): void
    {
        $storedTokens = $this->state->storedAccessTokens;
        if (!is_array($storedTokens) || !isset($storedTokens['default'])) {
            throw new \RuntimeException('No stored access token found.');
        }

        $accessToken = $storedTokens['default'];
        Assert::assertIsString($accessToken);
        Assert::assertNotSame('', $accessToken);

        $this->authenticateFromAccessToken($accessToken);
    }

    /**
     * @Given I am authenticated with stored access token :tokenKey
     */
    public function iAmAuthenticatedWithStoredAccessToken(
        string $tokenKey
    ): void {
        $storedTokens = $this->state->storedAccessTokens;
        if (!is_array($storedTokens) || !array_key_exists($tokenKey, $storedTokens)) {
            throw new \RuntimeException(
                sprintf('Stored access token "%s" was not found.', $tokenKey)
            );
        }

        $accessToken = $storedTokens[$tokenKey];
        Assert::assertIsString($accessToken);
        Assert::assertNotSame('', $accessToken);

        $this->authenticateFromAccessToken($accessToken);
    }

    /**
     * @param list<string> $roles
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    private function authenticateAsUserEmail(
        string $email,
        array $roles = ['ROLE_USER'],
        ?string $forcedUserId = null,
        bool $viaCookie = false
    ): void {
        $user = $this->resolveAuthenticationUser($email, $forcedUserId);
        $accessToken = $this->testAccessTokenFactory->createToken(
            $user->getId(),
            $roles
        );

        $this->setAuthenticatedUserToken($user, $roles);
        $this->state->currentUserEmail = $user->getEmail();
        $this->state->storedAccessTokens = ['default' => $accessToken];

        if ($viaCookie) {
            $this->state->accessToken = '';
            $this->state->useAuthCookie = true;
            $this->state->authCookieToken = $accessToken;

            return;
        }

        $this->state->useAuthCookie = false;
        $this->state->authCookieToken = '';
        $this->state->accessToken = $accessToken;
    }

    private function authenticateFromAccessToken(string $accessToken): void
    {
        $payload = $this->decodeJwtPayload($accessToken);
        $roles = $this->extractRoles($payload);

        $subject = $payload['sub'] ?? null;
        if (is_string($subject) && $subject !== '') {
            $user = $this->userRepository->findById($subject);
            if ($user instanceof User) {
                $this->setAuthenticatedUserToken($user, $roles);
                $this->state->currentUserEmail = $user->getEmail();
                self::$userIdsByEmail[$user->getEmail()] = $user->getId();
            } else {
                $this->tokenStorage->setToken(
                    new UsernamePasswordToken(
                        $subject,
                        'behat-bearer-token',
                        $roles
                    )
                );
            }
        }

        $this->state->useAuthCookie = false;
        $this->state->authCookieToken = '';
        $this->state->accessToken = $accessToken;
        $this->state->storedAccessTokens = ['default' => $accessToken];
    }

    private function resolveAuthenticationUser(
        string $email,
        ?string $forcedUserId
    ): User {
        if ($forcedUserId === null) {
            $this->userWithEmailExists($email);
            $user = $this->userRepository->findByEmail($email);
            if (!$user instanceof User) {
                throw new \RuntimeException("User with email {$email} not found");
            }

            self::$userIdsByEmail[$email] = $user->getId();

            return $user;
        }

        $existingUser = $this->userRepository->findByEmail($email);
        if ($existingUser instanceof User) {
            if ($existingUser->getId() !== $forcedUserId) {
                throw new \RuntimeException(
                    sprintf(
                        'User %s already exists with id %s (expected %s).',
                        $email,
                        $existingUser->getId(),
                        $forcedUserId
                    )
                );
            }

            self::$userIdsByEmail[$email] = $existingUser->getId();

            return $existingUser;
        }

        $password = $this->faker->password;
        $user = $this->userFactory->create(
            $email,
            $this->faker->name,
            $password,
            $this->transformer->transformFromString($forcedUserId)
        );
        $hasher = $this->hasherFactory->getPasswordHasher($user::class);
        $user->setPassword($hasher->hash($password, null));
        $this->userRepository->save($user);

        self::$userIdsByEmail[$email] = $user->getId();

        return $user;
    }

    /**
     * @param list<string> $roles
     */
    private function setAuthenticatedUserToken(User $user, array $roles): void
    {
        $authorizationUser = new AuthorizationUserDto(
            $user->getEmail(),
            $user->getInitials(),
            $user->getPassword(),
            $this->transformer->transformFromString($user->getId()),
            $user->isConfirmed()
        );

        $this->tokenStorage->setToken(
            new UsernamePasswordToken(
                $authorizationUser,
                'behat-user-token',
                $roles
            )
        );
    }

    /**
     * @param array<string, array<string>|int|string> $payload
     *
     * @return string[]
     *
     * @psalm-return list{string,...}
     */
    private function extractRoles(array $payload): array
    {
        $roles = $payload['roles'] ?? [];
        if (!is_array($roles)) {
            return ['ROLE_USER'];
        }

        $normalizedRoles = array_values(
            array_filter(
                $roles,
                static fn ($role): bool => is_string($role) && $role !== ''
            )
        );

        return $normalizedRoles !== [] ? $normalizedRoles : ['ROLE_USER'];
    }

    /**
     * @return array<string, array<string>|int|string>
     */
    private function decodeJwtPayload(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return [];
        }

        $payload = $this->base64UrlDecode($parts[1]);
        if ($payload === '') {
            return [];
        }

        $decoded = json_decode($payload, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return (int|string|string[])[]
     *
     * @psalm-return array{sub: string, iss: 'vilnacrm-user-service', aud: 'vilnacrm-api', exp: int<901, max>, iat: int<1, max>, nbf: int<1, max>, jti: string, sid: string, roles: list{'ROLE_USER'}}
     */
    private function createDefaultJwtPayload(): array
    {
        $email = sprintf(
            'jwt-%s@example.test',
            strtolower($this->faker->lexify('????'))
        );
        $user = $this->resolveAuthenticationUser($email, null);
        $now = time();

        return [
            'sub' => $user->getId(),
            'iss' => self::JWT_ISSUER,
            'aud' => self::JWT_AUDIENCE,
            'exp' => $now + self::JWT_ACCESS_TTL_SECONDS,
            'iat' => $now,
            'nbf' => $now,
            'jti' => (string) $this->uuidFactory->create(),
            'sid' => strtoupper($this->faker->bothify('??????????????????????????')),
            'roles' => ['ROLE_USER'],
        ];
    }

    /**
     * @param array<string, int|string|array<string>> $payload
     */
    private function createSignedJwt(array $payload): string
    {
        return $this->accessTokenGenerator->generate($payload);
    }

    /**
     * @return string[]
     *
     * @psalm-return non-empty-list<string>
     */
    private function decodeJsonArray(string $json): array
    {
        $decoded = json_decode($json, true);
        if (!is_array($decoded) || $decoded === []) {
            throw new \RuntimeException(
                sprintf('Invalid JSON array value: %s', $json)
            );
        }

        $result = [];
        foreach ($decoded as $value) {
            if (!is_string($value)) {
                throw new \RuntimeException(
                    sprintf('JSON array must contain only strings: %s', $json)
                );
            }

            $result[] = $value;
        }

        return $result;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $encoded): string
    {
        $remainder = strlen($encoded) % 4;
        if ($remainder !== 0) {
            $encoded .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($encoded, '-_', '+/'), true);

        return is_string($decoded) ? $decoded : '';
    }
}
