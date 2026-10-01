<?php

declare(strict_types=1);

namespace App\Tests\Behat\Support;

use App\Shared\Infrastructure\DependencyInjection\KmsJwtSigningCompilerPass;
use App\Shared\Infrastructure\Factory\KmsJwtFactory;
use App\Shared\Infrastructure\Provider\KmsJwtKeyProvider;
use Aws\Kms\KmsClient;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

final class EnvironmentKernel extends BaseKernel implements CompilerPassInterface
{
    use MicroKernelTrait;

    public const LOCAL_KMS_CLIENT = 'behat.local_kms_client';

    public function __construct(
        string $environment,
        bool $debug,
        private readonly string $projectDir,
        private readonly string $testMongoServer,
    ) {
        parent::__construct($environment, $debug);
    }

    #[\Override]
    public function getProjectDir(): string
    {
        return $this->projectDir;
    }

    #[\Override]
    public function getCacheDir(): string
    {
        return sprintf(
            '%s/var/cache/behat-environment/%s',
            $this->projectDir,
            $this->environment
        );
    }

    #[\Override]
    public function getBuildDir(): string
    {
        return $this->getCacheDir();
    }

    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        $definition = $container->getDefinition('doctrine_mongodb.odm.default_connection');
        $options = $definition->getArgument(1);

        if (!is_array($options)) {
            throw new LogicException('The MongoDB connection options must be an array.');
        }

        // Keep Symfony's configured env reference accounted for in the dumped container.
        $container->resolveEnvPlaceholders($definition->getArgument(0));
        $definition->replaceArgument(0, $this->testMongoServer);
        $options['tls'] = false;
        unset($options['tlsCAFile']);
        $definition->replaceArgument(1, $options);

        $this->signJwtsWithLocalStackKms($container);
    }

    /**
     * JWT signing and verification in a Behat-booted non-test kernel use the
     * LocalStack KMS key, like the test kernel that issued the tokens. The
     * production KmsClient service, which the production guard inspects, stays
     * unchanged.
     */
    private function signJwtsWithLocalStackKms(ContainerBuilder $container): void
    {
        $container->setDefinition(self::LOCAL_KMS_CLIENT, new Definition(KmsClient::class, [[
            'version' => 'latest',
            'region' => '%env(AWS_KMS_LOCAL_REGION)%',
            'endpoint' => '%env(AWS_KMS_LOCAL_ENDPOINT)%',
            'credentials' => [
                'key' => '%env(AWS_KMS_LOCAL_KEY)%',
                'secret' => '%env(AWS_KMS_LOCAL_SECRET)%',
            ],
        ]]));

        foreach ([KmsJwtKeyProvider::class, KmsJwtFactory::class] as $serviceId) {
            $container->getDefinition($serviceId)
                ->setArgument('$kmsClient', new Reference(self::LOCAL_KMS_CLIENT));
        }
    }

    #[\Override]
    protected function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new KmsJwtSigningCompilerPass());
        $container->addCompilerPass($this);
    }
}
