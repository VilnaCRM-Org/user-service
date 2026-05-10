<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use Behat\Behat\Context\Context;

final class JwtTokenContext implements Context
{
    private const JWT_ISSUER = 'vilnacrm-user-service';
    private const JWT_AUDIENCE = 'vilnacrm-api';
    private const JWT_ACCESS_TTL_SECONDS = 900;

    public function __construct(
        private UserOperationsState $state,
        private readonly UserContextAuthServices $auth,
        private readonly UserContextUserManagementServices $userManagement,
    ) {
    }

    /**
     * @Given I have a JWT with issuer :issuer
     */
    public function iHaveAJwtWithIssuer(string $issuer): void
    {
        $payload = $this->createDefaultJwtPayload();
        $payload['iss'] = $issuer;

        $this->state->accessToken = $this->auth->accessTokenFactory->create($payload);
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

        $this->state->accessToken = $this->auth->accessTokenFactory->create($payload);
        $this->state->useAuthCookie = false;
        $this->state->authCookieToken = '';
    }

    /**
     * @Given I have a JWT signed with algorithm :algorithm
     */
    public function iHaveAJwtSignedWithAlgorithm(
        string $algorithm
    ): void {
        $header = ['typ' => 'JWT', 'alg' => $algorithm];
        $payload = $this->createDefaultJwtPayload();
        $encode = static fn (string $v): string => rtrim(strtr(base64_encode($v), '+/', '-_'), '=');

        $this->state->accessToken = sprintf(
            '%s.%s.%s',
            $encode(json_encode($header, JSON_THROW_ON_ERROR)),
            $encode(
                json_encode($payload, JSON_THROW_ON_ERROR)
            ),
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

        $this->state->accessToken = $this->auth->accessTokenFactory->create($payload);
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

        $this->state->accessToken = $this->auth->accessTokenFactory->create($payload);
        $this->state->useAuthCookie = false;
        $this->state->authCookieToken = '';
    }

    /**
     * @Given I have a JWT with issuer as array :issuerJson
     */
    public function iHaveAJwtWithIssuerAsArray(
        string $issuerJson
    ): void {
        $header = ['typ' => 'JWT', 'alg' => 'RS256'];
        $payload = $this->createDefaultJwtPayload();
        $payload['iss'] = $this->decodeJsonArray($issuerJson);
        $encode = static fn (string $v): string => rtrim(strtr(base64_encode($v), '+/', '-_'), '=');

        $this->state->accessToken = sprintf(
            '%s.%s.%s',
            $encode(json_encode($header, JSON_THROW_ON_ERROR)),
            $encode(
                json_encode($payload, JSON_THROW_ON_ERROR)
            ),
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
        $this->iHaveAJwtWithIssuerAsArray(
            '["vilnacrm-user-service"]'
        );
    }

    /**
     * @Given I have a JWT without the :claim claim
     */
    public function iHaveAJwtWithoutTheClaim(string $claim): void
    {
        $normalizedClaim = trim($claim, "\"'");
        $payload = $this->createDefaultJwtPayload();
        unset($payload[$normalizedClaim]);

        $this->state->accessToken = $this->auth->accessTokenFactory->create($payload);
        $this->state->useAuthCookie = false;
        $this->state->authCookieToken = '';
    }

    /**
     * @Given I have a JWT with tampered payload
     */
    public function iHaveAJwtWithTamperedPayload(): void
    {
        $payload = $this->createDefaultJwtPayload();
        $signedToken = $this->auth
            ->accessTokenFactory->create($payload);
        $parts = explode('.', $signedToken);
        if (count($parts) !== 3) {
            throw new \RuntimeException('Could not tamper JWT payload.');
        }
        $tamperedPayload = ['sub' => 'tampered'];
        $json = json_encode($tamperedPayload, JSON_THROW_ON_ERROR);
        $encodedPayload = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
        $this->state->accessToken = sprintf('%s.%s.%s', $parts[0], $encodedPayload, $parts[2]);
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
        $this->state->accessToken =
            ' ' . $this->auth->accessTokenFactory->create(
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

        $this->state->accessToken =
            $this->auth->accessTokenFactory->create($payload);
        $this->state->useAuthCookie = false;
        $this->state->authCookieToken = '';
    }

    /**
     * @Given I have a JWT with audience as array :audienceJson
     */
    public function iHaveAJwtWithAudienceAsArray(
        string $audienceJson
    ): void {
        $header = ['typ' => 'JWT', 'alg' => 'RS256'];
        $payload = $this->createDefaultJwtPayload();
        $payload['aud'] = $this->decodeJsonArray($audienceJson);
        $encode = static fn (string $v): string => rtrim(strtr(base64_encode($v), '+/', '-_'), '=');

        $this->state->accessToken = sprintf(
            '%s.%s.%s',
            $encode(json_encode($header, JSON_THROW_ON_ERROR)),
            $encode(
                json_encode($payload, JSON_THROW_ON_ERROR)
            ),
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

        $this->state->accessToken = $this->auth->accessTokenFactory->create($payload);
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

        $this->state->accessToken = $this->auth->accessTokenFactory->create($payload);
        $this->state->useAuthCookie = false;
        $this->state->authCookieToken = '';
    }

    /**
     * @Given I have a JWT with only header and payload :jwt
     */
    public function iHaveAJwtWithOnlyHeaderAndPayload(
        string $jwt
    ): void {
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
     * @return array<int|string|array<string>>
     *
     * @psalm-return array{sub: string, iss: 'vilnacrm-user-service', aud: 'vilnacrm-api', exp: int<901, max>, iat: int<1, max>, nbf: int<1, max>, jti: string, sid: string, roles: list{'ROLE_USER'}}
     */
    private function createDefaultJwtPayload(): array
    {
        $faker = \Faker\Factory::create();
        $email = sprintf(
            'jwt-%s@example.test',
            strtolower($faker->lexify('????'))
        );
        $user = $this->resolveUser($email);
        $now = time();
        $sessionId = $this->createActiveSession($user->getId());

        return [
            'sub' => $user->getId(),
            'iss' => self::JWT_ISSUER,
            'aud' => self::JWT_AUDIENCE,
            'exp' => $now + self::JWT_ACCESS_TTL_SECONDS,
            'iat' => $now,
            'nbf' => $now,
            'jti' => (string) $this->userManagement
                ->uuidFactory->create(),
            'sid' => $sessionId,
            'roles' => ['ROLE_USER'],
        ];
    }

    /**
     * @return array<string>
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
                    sprintf(
                        'JSON array must contain only strings: %s',
                        $json
                    )
                );
            }

            $result[] = $value;
        }

        return $result;
    }

    private function resolveUser(string $email): \App\User\Domain\Entity\User
    {
        $existing = $this->userManagement->userRepository->findByEmail($email);
        if ($existing !== null) {
            UserContext::registerUserIdByEmail($email, $existing->getId());
            return $existing;
        }
        $faker = \Faker\Factory::create();
        $password = $faker->password;
        $uuid = $this->userManagement->uuidFactory->create();
        $userId = $this->userManagement->transformer->transformFromSymfonyUuid($uuid);
        $user = $this->userManagement->userFactory
            ->create($email, $faker->name, $password, $userId);
        $hasher = $this->userManagement->hasherFactory->getPasswordHasher($user::class);
        $user->setPassword($hasher->hash($password, null));
        $this->userManagement->userRepository->save($user);
        UserContext::registerUserIdByEmail($email, (string) $userId);
        return $user;
    }

    private function createActiveSession(string $userId): string
    {
        $sessionId = (string) $this->auth->ulidFactory->create();
        $createdAt = new \DateTimeImmutable('-1 minute');
        $faker = \Faker\Factory::create();

        $this->auth->authSessionRepository->save(
            new \App\User\Domain\Entity\AuthSession(
                $sessionId,
                $userId,
                $faker->ipv4(),
                'BehatJwtTokenContext',
                $createdAt,
                $createdAt->modify('+15 minutes'),
                false
            )
        );

        return $sessionId;
    }
}
