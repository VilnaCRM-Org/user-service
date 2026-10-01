<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Fixture\Command;

use App\Shared\Application\Provider\CurrentTimestampProviderInterface;
use App\User\Application\Factory\AccessTokenFactoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Uid\Factory\UlidFactory;
use Symfony\Component\Uid\Factory\UuidFactory;

/**
 * Prints a ROLE_SERVICE access token for the k6 load tests. The token is
 * signed by the KMS JWT key (LocalStack outside production), replacing the
 * old openssl script that read a local private key.
 *
 * @psalm-api
 */
#[AsCommand(
    name: 'app:load-test:issue-service-token',
    description: 'Print a KMS-signed ROLE_SERVICE access token for load tests.'
)]
final class IssueLoadTestServiceTokenCommand extends Command
{
    private const SUBJECT = 'load-test-service';
    private const JWT_ISSUER = 'vilnacrm-user-service';
    private const JWT_AUDIENCE = 'vilnacrm-api';
    private const ACCESS_TOKEN_TTL_SECONDS = 900;
    private const LOCAL_ENVIRONMENTS = ['dev', 'test', 'load_test', 'schemathesis'];

    public function __construct(
        private readonly AccessTokenFactoryInterface $accessTokenFactory,
        private readonly CurrentTimestampProviderInterface $timestampProvider,
        private readonly UuidFactory $uuidFactory,
        private readonly UlidFactory $ulidFactory,
        #[Autowire('%kernel.environment%')]
        private readonly string $environment,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!in_array($this->environment, self::LOCAL_ENVIRONMENTS, true)) {
            $output->writeln('Load-test tokens are issued only in local environments.');

            return Command::FAILURE;
        }

        $now = $this->timestampProvider->currentTimestamp();
        $output->write($this->accessTokenFactory->create([
            'sub' => self::SUBJECT,
            'iss' => self::JWT_ISSUER,
            'aud' => self::JWT_AUDIENCE,
            'exp' => $now + self::ACCESS_TOKEN_TTL_SECONDS,
            'iat' => $now,
            'nbf' => $now,
            'jti' => (string) $this->uuidFactory->create(),
            'sid' => (string) $this->ulidFactory->create(),
            'roles' => ['ROLE_SERVICE'],
        ]));

        return Command::SUCCESS;
    }
}
