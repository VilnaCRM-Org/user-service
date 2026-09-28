<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Application\Factory;

use App\OAuth\Application\Factory\OAuthProviderCollectionFactory;
use App\OAuth\Application\Factory\OAuthProviderCollectionFactoryInterface;
use App\OAuth\Application\Provider\OAuthProviderInterface;
use App\OAuth\Domain\ValueObject\OAuthProvider;
use App\Tests\Unit\UnitTestCase;

final class OAuthProviderCollectionFactoryTest extends UnitTestCase
{
    private OAuthProviderCollectionFactoryInterface $factory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new OAuthProviderCollectionFactory(true);
    }

    public function testCreateBuildsCollectionFromIterable(): void
    {
        $github = $this->createProviderMock('github');
        $google = $this->createProviderMock('google');

        $collection = $this->factory->create(new \ArrayIterator([$github, $google]));

        $this->assertCount(2, $collection);
        $this->assertSame($github, $collection->get('github'));
        $this->assertSame($google, $collection->get('google'));
    }

    public function testCreatePreservesAllItemsFromDuplicateKeyIterables(): void
    {
        $github = $this->createProviderMock('github');
        $google = $this->createProviderMock('google');

        $generator = (static function () use ($github, $google): \Generator {
            yield 0 => $github;
            yield 0 => $google;
        })();

        $collection = $this->factory->create($generator);

        $this->assertCount(2, $collection);
        $this->assertSame($github, $collection->get('github'));
        $this->assertSame($google, $collection->get('google'));
    }

    public function testDisabledFactoryDoesNotInstantiateTaggedProviders(): void
    {
        $providers = (static function (): \Generator {
            yield throw new \LogicException('Disabled providers must not be resolved');
        })();

        $collection = (new OAuthProviderCollectionFactory(false))->create($providers);

        self::assertCount(0, $collection);
    }

    public function testExplicitOptInPreservesEnabledProviders(): void
    {
        $provider = $this->createProviderMock('github');
        $collection = (new OAuthProviderCollectionFactory(true))->create([$provider]);

        self::assertSame($provider, $collection->get('github'));
    }

    private function createProviderMock(string $name): OAuthProviderInterface
    {
        $mock = $this->createMock(OAuthProviderInterface::class);
        $mock->method('getProvider')
            ->willReturn(OAuthProvider::fromString($name));

        return $mock;
    }
}
