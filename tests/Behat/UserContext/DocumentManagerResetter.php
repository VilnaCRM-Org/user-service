<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

final readonly class DocumentManagerResetter
{
    public function __construct(private KernelInterface $kernel)
    {
    }

    public function clear(): void
    {
        $this->userCachePool()->clear();
        $this->documentManager()->clear();
    }

    private function documentManager(): DocumentManager
    {
        $documentManager = $this->container()->get(DocumentManager::class);
        if (!$documentManager instanceof DocumentManager) {
            throw DocumentManagerResetterException::documentManagerUnavailable();
        }

        return $documentManager;
    }

    private function container(): ContainerInterface
    {
        $container = $this->kernel->getContainer()->get('test.service_container');
        if (!$container instanceof ContainerInterface) {
            throw DocumentManagerResetterException::testContainerUnavailable();
        }

        return $container;
    }

    private function userCachePool(): CacheItemPoolInterface
    {
        $cachePool = $this->container()->get('cache.user');
        if (!$cachePool instanceof CacheItemPoolInterface) {
            throw DocumentManagerResetterException::userCachePoolUnavailable();
        }

        return $cachePool;
    }
}
