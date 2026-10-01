<?php

declare(strict_types=1);

namespace App\Tests\Integration\User\Infrastructure\Adapter;

use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Integration\IntegrationTestCase;
use App\User\Application\Validator\TwoFactorCodeValidatorInterface;
use App\User\Domain\Contract\TwoFactorSecretEncryptorInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Aws\Kms\Exception\KmsException;
use Aws\Kms\KmsClient;
use InvalidArgumentException;
use OTPHP\TOTP;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * S5.12: 2FA TOTP secrets are encrypted with KMS under the encryption
 * context {user_id: <id>}. Runs against the LocalStack KMS of the local stack.
 */
final class KmsTwoFactorSecretEncryptorIntegrationTest extends IntegrationTestCase
{
    private TwoFactorSecretEncryptorInterface $encryptor;
    private KmsClient $kmsClient;
    private UserRepositoryInterface $userRepository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->encryptor = $this->container->get(TwoFactorSecretEncryptorInterface::class);
        $this->kmsClient = $this->container->get(KmsClient::class);
        $this->userRepository = $this->container->get(UserRepositoryInterface::class);
    }

    public function testEnrolmentEncryptsWithKmsAndVerificationDecrypts(): void
    {
        $user = $this->createUser();
        $headers = [
            'HTTP_AUTHORIZATION' => sprintf(
                'Bearer %s',
                $this->createBearerTokenForUser($user->getId())
            ),
        ];

        $setup = $this->postJson('/api/2fa/setup', $headers);
        $this->assertSame(Response::HTTP_OK, $setup->getStatusCode());
        $secret = $this->decode($setup)['secret'];

        $stored = $this->reload($user)->getTwoFactorSecret();
        $this->assertIsString($stored);
        $this->assertStringNotContainsString($secret, $stored);
        $this->assertSame($secret, $this->kmsDecrypt($stored, ['user_id' => $user->getId()]));

        $this->assertSame(Response::HTTP_OK, $this->confirm($secret, $headers));
        $this->assertTrue($this->reload($user)->isTwoFactorEnabled());
    }

    public function testCiphertextIsBoundToTheUserIdEncryptionContext(): void
    {
        $owner = $this->faker->uuid();
        $payload = $this->encryptor->encrypt('JBSWY3DPEHPK3PXP', $owner);

        $this->assertSame('JBSWY3DPEHPK3PXP', $this->encryptor->decrypt($payload, $owner));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to decrypt two-factor secret.');

        $this->encryptor->decrypt($payload, $this->faker->uuid());
    }

    public function testKmsRejectsTheCiphertextWithoutTheEncryptionContext(): void
    {
        $payload = $this->encryptor->encrypt('JBSWY3DPEHPK3PXP', $this->faker->uuid());

        $this->expectException(KmsException::class);

        $this->kmsDecrypt($payload, []);
    }

    public function testVerificationFailsClosedForAnotherUsersCiphertext(): void
    {
        $secret = TOTP::generate()->getSecret();
        $victim = $this->createUser();
        $attacker = $this->createUser();
        $attacker->setTwoFactorSecret($this->encryptor->encrypt($secret, $victim->getId()));
        $this->userRepository->save($attacker);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to decrypt two-factor secret.');

        $this->container->get(TwoFactorCodeValidatorInterface::class)
            ->verifyTotpForSetupOrFail(
                $this->reload($attacker),
                TOTP::createFromSecret($secret)->now()
            );
    }

    public function testLocalKmsAcceptsTheSizeBound(): void
    {
        $userId = $this->faker->uuid();
        $secret = str_repeat('A', 4096);

        $payload = $this->encryptor->encrypt($secret, $userId);

        $this->assertSame($secret, $this->encryptor->decrypt($payload, $userId));
    }

    public function testOversizedSecretIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Two-factor secret must be between 1 and 4096 bytes.');

        $this->encryptor->encrypt(str_repeat('A', 4097), $this->faker->uuid());
    }

    /**
     * @param array<string, string> $context
     */
    private function kmsDecrypt(string $payload, array $context): string
    {
        $request = ['CiphertextBlob' => base64_decode($payload, true)];
        if ($context !== []) {
            $request['EncryptionContext'] = $context;
        }

        return (string) $this->kmsClient->decrypt($request)->get('Plaintext');
    }

    /**
     * @param array<string, string> $headers
     */
    private function confirm(string $secret, array $headers): int
    {
        $totp = TOTP::createFromSecret($secret);
        $statuses = [];
        foreach ([0, -1, 1] as $window) {
            $code = $totp->at(time() + ($window * $totp->getPeriod()));
            $statuses[] = $this->postJson(
                '/api/2fa/confirm',
                $headers,
                ['twoFactorCode' => $code]
            )->getStatusCode();
            if (end($statuses) === Response::HTTP_OK) {
                break;
            }
        }

        return (int) end($statuses);
    }

    /**
     * @param array<string, string> $headers
     * @param array<string, string> $payload
     */
    private function postJson(string $uri, array $headers, array $payload = []): Response
    {
        $kernel = $this->container->get('kernel');
        $this->assertInstanceOf(HttpKernelInterface::class, $kernel);

        return $kernel->handle(Request::create(
            $uri,
            Request::METHOD_POST,
            [],
            [],
            [],
            array_merge([
                'REMOTE_ADDR' => $this->faker->ipv4(),
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
            ], $headers),
            $payload === [] ? '{}' : json_encode($payload, JSON_THROW_ON_ERROR)
        ));
    }

    /**
     * @return array{secret: string}
     */
    private function decode(Response $response): array
    {
        $body = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($body);
        $this->assertIsString($body['secret'] ?? null);

        return ['secret' => $body['secret']];
    }

    private function createUser(): User
    {
        $user = $this->container->get(UserFactoryInterface::class)->create(
            strtolower($this->faker->unique()->safeEmail()),
            $this->faker->lexify('??'),
            sprintf('A1%s', strtolower($this->faker->lexify('????????????'))),
            $this->container->get(UuidTransformer::class)
                ->transformFromString($this->faker->uuid())
        );
        $this->assertInstanceOf(User::class, $user);
        $this->userRepository->save($user);

        return $user;
    }

    private function reload(User $user): User
    {
        $reloaded = $this->userRepository->findById($user->getId());
        $this->assertInstanceOf(User::class, $reloaded);

        return $reloaded;
    }
}
