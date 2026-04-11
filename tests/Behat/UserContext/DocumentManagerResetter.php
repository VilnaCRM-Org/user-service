<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use Doctrine\ODM\MongoDB\DocumentManager;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

final readonly class DocumentManagerResetter
{
    public function __construct(private KernelInterface $kernel)
    {
    }

    public function clear(): void
    {
        $this->documentManager()->clear();
    }

    private function documentManager(): DocumentManager
    {
        $documentManager = $this->container()->get(DocumentManager::class);
        if (!$documentManager instanceof DocumentManager) {
            throw new RuntimeException('Document manager is not available');
        }

        return $documentManager;
    }

    private function container(): ContainerInterface
    {
        $container = $this->kernel->getContainer()->get('test.service_container');
        if (!$container instanceof ContainerInterface) {
            throw new RuntimeException('Test container is not available');
        }

        return $container;
    }
}
