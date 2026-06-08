<?php

declare(strict_types=1);

namespace App\Tests\Integration\User;

use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Integration\IntegrationTestCase;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\Collection;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

abstract class UserIntegrationTestCase extends IntegrationTestCase
{
    /** @var list<string> */
    private array $trackedUserEmailsForCleanup = [];

    #[\Override]
    protected function tearDown(): void
    {
        if ($this->trackedUserEmailsForCleanup !== []) {
            $this->usersDocumentCollection()->deleteMany([
                'email' => ['$in' => $this->trackedUserEmailsForCleanup],
            ]);
            $this->clearUserDocumentState();
        }

        parent::tearDown();
    }

    protected function createPersistedUserWithEmail(string $email): User
    {
        $this->trackUserEmailForCleanup($email);
        $user = $this->userFactory()->create(
            $email,
            $this->faker->name(),
            $this->faker->sha256(),
            $this->uuidTransformer()->transformFromString($this->faker->uuid())
        );
        $this->assertInstanceOf(User::class, $user);
        $this->userRepository()->save($user);
        $this->clearUserDocumentState();

        return $user;
    }

    protected function insertLegacyUserDocumentWithEmail(string $email): void
    {
        $this->trackUserEmailForCleanup($email);
        $this->usersDocumentCollection()->insertOne([
            '_id' => $this->faker->uuid(),
            'email' => $email,
            'initials' => $this->faker->name(),
            'password' => $this->faker->sha256(),
            'confirmed' => false,
            'twoFactorEnabled' => false,
            'twoFactorSecret' => null,
        ]);
        $this->clearUserDocumentState();
    }

    /**
     * @param array<string, bool|string> $payload
     * @param array<string, string> $extraHeaders
     */
    protected function requestJsonPost(
        string $uri,
        array $payload,
        array $extraHeaders = []
    ): Response {
        $content = $payload === [] ? '{}' : json_encode($payload, JSON_THROW_ON_ERROR);
        $server = array_merge([
            'REMOTE_ADDR' => $this->faker->ipv4(),
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ], $extraHeaders);

        return $this->httpKernel()->handle(
            Request::create(
                $uri,
                Request::METHOD_POST,
                [],
                [],
                [],
                $server,
                $content
            )
        );
    }

    protected function usersDocumentCollection(): Collection
    {
        return $this->documentManager()->getDocumentCollection(User::class);
    }

    protected function createMixedCaseEmail(): string
    {
        return sprintf(
            '%s%s@%s',
            ucfirst($this->faker->unique()->userName()),
            str_replace('-', '', $this->faker->uuid()),
            $this->faker->safeEmailDomain()
        );
    }

    protected function clearUserDocumentState(): void
    {
        $this->documentManager()->clear();
        $this->userCache()->clear();
    }

    protected function userRepository(): UserRepositoryInterface
    {
        return $this->container->get(UserRepositoryInterface::class);
    }

    private function trackUserEmailForCleanup(string $email): void
    {
        $this->trackedUserEmailsForCleanup[] = $email;
    }

    private function httpKernel(): HttpKernelInterface
    {
        $kernel = $this->container->get('kernel');
        $this->assertInstanceOf(HttpKernelInterface::class, $kernel);

        return $kernel;
    }

    private function documentManager(): DocumentManager
    {
        return $this->container->get(DocumentManager::class);
    }

    private function userFactory(): UserFactoryInterface
    {
        return $this->container->get(UserFactoryInterface::class);
    }

    private function uuidTransformer(): UuidTransformer
    {
        return $this->container->get(UuidTransformer::class);
    }

    private function userCache(): CacheItemPoolInterface
    {
        return $this->container->get('cache.user');
    }
}
