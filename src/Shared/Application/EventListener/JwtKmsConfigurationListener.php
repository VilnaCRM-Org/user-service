<?php

declare(strict_types=1);

namespace App\Shared\Application\EventListener;

use App\Shared\Application\Provider\KmsEndpointProviderInterface;
use RuntimeException;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Production guard for KMS JWT signing (S5.11, FR-06): refuses a local or dev
 * signer (any KMS endpoint other than the regional AWS one), every AWS
 * credential source other than the ECS task role, and JWT key ids that are
 * not KMS key or alias ARNs.
 */
final readonly class JwtKmsConfigurationListener
{
    private const KEY_ARN_PATTERN
        = '#^arn:aws[a-z-]*:kms:[a-z0-9-]+:[0-9]{12}:(key|alias)/[A-Za-z0-9/_-]+$#D';
    private const KMS_ENDPOINT_PATTERN
        = '#^https://kms(-fips)?\.[a-z0-9-]+\.amazonaws\.com$#D';

    /**
     * Each of these, when set, makes the AWS SDK default chain use credentials
     * other than the ECS task role.
     */
    private const REFUSED_CREDENTIAL_VARIABLES = [
        'AWS_ACCESS_KEY_ID',
        'AWS_SECRET_ACCESS_KEY',
        'AWS_SESSION_TOKEN',
        'AWS_PROFILE',
        'AWS_SHARED_CREDENTIALS_FILE',
        'AWS_CONTAINER_CREDENTIALS_FULL_URI',
    ];

    /**
     * Together, these select web-identity credentials.
     */
    private const WEB_IDENTITY_VARIABLES = ['AWS_WEB_IDENTITY_TOKEN_FILE', 'AWS_ROLE_ARN'];

    /**
     * @param array<string, string> $credentialEnvironment
     */
    public function __construct(
        private string $appEnv,
        private KmsEndpointProviderInterface $kmsEndpointProvider,
        private string $currentKeyId,
        private string $previousKeyId,
        private array $credentialEnvironment,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->assertConfigurationIsValid();
    }

    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        if ($event->getCommand() === null) {
            return;
        }

        $this->assertConfigurationIsValid();
    }

    private function assertConfigurationIsValid(): void
    {
        if ($this->appEnv !== 'prod') {
            return;
        }

        $this->assertKeyIds();
        $this->assertTaskRoleCredentialsOnly();
        $this->assertRegionalKmsEndpoint();
    }

    private function assertKeyIds(): void
    {
        if (!$this->isKmsArn($this->currentKeyId)) {
            throw new RuntimeException(
                'Set JWT_KMS_KEY_ID to the KMS key ARN or alias ARN in production.'
            );
        }

        if ($this->previousKeyId !== '' && !$this->isKmsArn($this->previousKeyId)) {
            throw new RuntimeException(
                'JWT_KMS_PREVIOUS_KEY_ID must be empty or a KMS key ARN or alias ARN in production.'
            );
        }
    }

    private function assertTaskRoleCredentialsOnly(): void
    {
        $set = array_filter(
            $this->credentialEnvironment,
            static fn (string $value): bool => $value !== ''
        );
        $refused = array_intersect_key($set, array_flip(self::REFUSED_CREDENTIAL_VARIABLES));
        $webIdentity = array_intersect_key($set, array_flip(self::WEB_IDENTITY_VARIABLES));

        if ($refused !== [] || count($webIdentity) === count(self::WEB_IDENTITY_VARIABLES)) {
            throw new RuntimeException(
                'Only the ECS task role may sign JWTs in production; unset the static,'
                . ' profile, web-identity and full-URI AWS credential variables.'
            );
        }
    }

    private function assertRegionalKmsEndpoint(): void
    {
        $endpoint = $this->kmsEndpointProvider->endpoint();
        if (preg_match(self::KMS_ENDPOINT_PATTERN, $endpoint) !== 1) {
            throw new RuntimeException(
                'JWT signing must use the regional AWS KMS endpoint in production.'
            );
        }
    }

    private function isKmsArn(string $keyId): bool
    {
        return preg_match(self::KEY_ARN_PATTERN, $keyId) === 1;
    }
}
