<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth;

use App\OAuth\Infrastructure\Security\KmsCryptKey;
use App\Shared\Infrastructure\Adapter\KmsJwsProvider;
use App\Shared\Infrastructure\Converter\Base64UrlConverter;
use App\Shared\Infrastructure\Converter\KmsPublicKeyConverter;
use App\Shared\Infrastructure\Factory\KmsJwtFactory;
use App\Shared\Infrastructure\Provider\KmsJwtKeyProvider;
use App\Shared\Infrastructure\Provider\SystemCurrentTimestampProvider;
use App\Shared\Infrastructure\Validator\JwtSignatureVerifier;
use App\Tests\Integration\IntegrationTestCase;
use App\User\Application\Factory\AccessTokenFactoryInterface;
use Aws\Kms\Exception\KmsException;
use Aws\Kms\KmsClient;
use League\Bundle\OAuth2ServerBundle\Entity\Client as ClientEntity;
use League\OAuth2\Server\AuthorizationServer;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\LcobucciJWTEncoder;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * S5.11 (FR-06) against LocalStack KMS: tokens are signed with kms:Sign
 * (RSASSA_PKCS1_V1_5_SHA_256) and verified locally with GetPublicKey keys.
 */
final class KmsJwtSigningIntegrationTest extends IntegrationTestCase
{
    private KmsClient $kms;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->kms = $this->container->get(KmsClient::class);
    }

    public function testKmsSignedAccessTokenVerifies(): void
    {
        $token = $this->container->get(AccessTokenFactoryInterface::class)
            ->create($this->claims());

        $payload = $this->container->get('lexik_jwt_authentication.encoder')->decode($token);

        self::assertSame('kms-user', $payload['sub']);
        self::assertSame($this->currentJwks()['keys'][0]['kid'], $this->header($token)['kid']);
    }

    public function testTamperedTokenIsRejected(): void
    {
        $encoder = $this->container->get('lexik_jwt_authentication.encoder');
        [$header, , $signature] = explode('.', $encoder->encode($this->claims()));
        $forged = $this->base64Url()->encode(
            (string) json_encode([...$this->claims(), 'roles' => ['ROLE_SERVICE']])
        );

        $this->expectException(JWTDecodeFailureException::class);

        $encoder->decode("{$header}.{$forged}.{$signature}");
    }

    public function testTokenWithFlippedSignatureIsRejected(): void
    {
        $encoder = $this->container->get('lexik_jwt_authentication.encoder');
        [$header, $payload, $signature] = explode('.', $encoder->encode($this->claims()));
        $signature[10] = $signature[10] === 'A' ? 'B' : 'A';

        $this->expectException(JWTDecodeFailureException::class);

        $encoder->decode("{$header}.{$payload}.{$signature}");
    }

    public function testDualKeyWindowAcceptsThePreviousKeyOnlyWhileConfigured(): void
    {
        $oldKey = $this->createSigningKey();
        $newKey = $this->createSigningKey();
        $oldToken = $this->encoder($oldKey, '')->encode($this->claims());

        $window = $this->encoder($newKey, $oldKey);
        self::assertSame('kms-user', $window->decode($oldToken)['sub']);
        $newToken = $window->encode($this->claims());
        self::assertNotSame($this->header($oldToken)['kid'], $this->header($newToken)['kid']);
        self::assertSame('kms-user', $this->encoder($newKey, '')->decode($newToken)['sub']);

        $this->expectException(JWTDecodeFailureException::class);
        $this->encoder($newKey, '')->decode($oldToken);
    }

    public function testTokenOfAnUnconfiguredKeyIsRejected(): void
    {
        $foreignToken = $this->encoder($this->createSigningKey(), '')->encode($this->claims());

        $this->expectException(JWTDecodeFailureException::class);

        $this->container->get('lexik_jwt_authentication.encoder')->decode($foreignToken);
    }

    public function testKmsErrorFailsClosedForSigning(): void
    {
        $this->expectException(KmsException::class);

        $this->encoder('alias/user-service-jwt-missing', '')->encode($this->claims());
    }

    public function testKmsErrorFailsClosedForVerification(): void
    {
        $token = $this->container->get('lexik_jwt_authentication.encoder')
            ->encode($this->claims());

        $this->expectException(JWTDecodeFailureException::class);

        $this->encoder('alias/user-service-jwt-missing', '')->decode($token);
    }

    public function testOauthServerSignsAccessTokensWithKms(): void
    {
        $repository = $this->container->get('league.oauth2_server.repository.access_token');
        $client = new ClientEntity();
        $client->setIdentifier('kms-client');
        $accessToken = $repository->getNewToken($client, [], null);
        $accessToken->setIdentifier('kms-token-id');
        $accessToken->setExpiryDateTime(new \DateTimeImmutable('+5 minutes'));

        $verified = $this->container->get(JwtSignatureVerifier::class)
            ->verify($accessToken->toString());

        self::assertSame('kms-client', $verified['payload']['aud'] ?? null);
        self::assertSame('kms-token-id', $verified['payload']['jti'] ?? null);
    }

    public function testOauthServersHoldNoLocalKeyMaterial(): void
    {
        $server = $this->container->get(AuthorizationServer::class);

        self::assertInstanceOf(
            KmsCryptKey::class,
            (new ReflectionProperty(AuthorizationServer::class, 'privateKey'))->getValue($server)
        );
    }

    public function testJwksPublishesTheCurrentKmsKey(): void
    {
        $publicKey = $this->kms->getPublicKey(['KeyId' => 'alias/user-service-jwt']);
        $jwk = $this->currentJwks()['keys'][0];

        self::assertSame(['kty', 'use', 'alg', 'kid', 'n', 'e'], array_keys($jwk));
        self::assertSame('RS256', $jwk['alg']);
        self::assertSame(
            $this->publicKeyModulus((string) $publicKey['PublicKey']),
            $jwk['n']
        );
    }

    private function encoder(string $currentKeyId, string $previousKeyId): JWTEncoderInterface
    {
        $serializer = $this->container->get(SerializerInterface::class);
        $clock = new SystemCurrentTimestampProvider();
        $keyProvider = new KmsJwtKeyProvider(
            $this->kms,
            new KmsPublicKeyConverter($this->base64Url()),
            $clock,
            $currentKeyId,
            $previousKeyId,
            300
        );

        return new LcobucciJWTEncoder(new KmsJwsProvider(
            new KmsJwtFactory($this->kms, $keyProvider, $serializer, $this->base64Url()),
            new JwtSignatureVerifier($keyProvider, $serializer, $this->base64Url()),
            $clock,
            900
        ));
    }

    private function createSigningKey(): string
    {
        return (string) $this->kms->createKey([
            'Description' => 'S5.11 integration test key',
            'KeySpec' => 'RSA_4096',
            'KeyUsage' => 'SIGN_VERIFY',
        ])['KeyMetadata']['Arn'];
    }

    /**
     * @return array{keys: list<array<string, string>>}
     */
    private function currentJwks(): array
    {
        $response = self::$kernel->handle(Request::create('/api/.well-known/jwks.json'));
        self::assertSame(200, $response->getStatusCode());

        return json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, string>
     */
    private function header(string $token): array
    {
        $json = $this->base64Url()->decode(explode('.', $token)[0]);

        return json_decode((string) $json, true, 512, JSON_THROW_ON_ERROR);
    }

    private function publicKeyModulus(string $der): string
    {
        $pem = "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split(base64_encode($der), 64, "\n")
            . "-----END PUBLIC KEY-----\n";
        $details = openssl_pkey_get_details(openssl_pkey_get_public($pem));

        return $this->base64Url()->encode($details['rsa']['n']);
    }

    /**
     * @return array<string, int|string|list<string>>
     */
    private function claims(): array
    {
        $now = time();

        return [
            'sub' => 'kms-user',
            'iss' => 'vilnacrm-user-service',
            'aud' => 'vilnacrm-api',
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + 600,
            'sid' => 'kms-session',
            'roles' => ['ROLE_USER'],
        ];
    }

    private function base64Url(): Base64UrlConverter
    {
        return new Base64UrlConverter();
    }
}
