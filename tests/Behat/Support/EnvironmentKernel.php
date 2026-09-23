<?php

declare(strict_types=1);

namespace App\Tests\Behat\Support;

use LogicException;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

final class EnvironmentKernel extends BaseKernel implements CompilerPassInterface
{
    use MicroKernelTrait;

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
    protected function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass($this);
    }

    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        $definition = $container->getDefinition('doctrine_mongodb.odm.default_connection');
        $options = $definition->getArgument(1);

        if (!is_array($options)) {
            throw new LogicException('The MongoDB connection options must be an array.');
        }

        $definition->replaceArgument(0, $this->testMongoServer);
        $options['tls'] = false;
        unset($options['tlsCAFile']);
        $definition->replaceArgument(1, $options);
    }
}
