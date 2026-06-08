<?php

declare(strict_types=1);

namespace App\Tests\Integration\User\Application\Processor;

use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Integration\User\UserIntegrationTestCase;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\Collection;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class RegisterUserRestIntegrationTest extends UserIntegrationTestCase
{
    private HttpKernelInterface $httpKernel;
    private DocumentManager $documentManager;
    private UserRepositoryInterface $userRepository;
    private UserFactoryInterface $userFactory;
    private UuidTransformer $uuidTransformer;
    private CacheItemPoolInterface $userCache;
    /** @var list<string> */
    private array $emailsForCleanup = [];

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $kernel = $this->container->get('kernel');
        $this->assertInstanceOf(HttpKernelInterface::class, $kernel);
        $this->httpKernel = $kernel;
        $this->documentManager = $this->container->get(DocumentManager::class);
        $this->userRepository = $this->container->get(UserRepositoryInterface::class);
        $this->userFactory = $this->container->get(UserFactoryInterface::class);
        $this->uuidTransformer = $this->container->get(UuidTransformer::class);
        $this->userCache = $this->container->get('cache.user');
        $this->userCache->clear();
    }

    #[\Override]
    protected function tearDown(): void
    {
        if ($this->emailsForCleanup !== []) {
            $this->usersCollection()->deleteMany([
                'email' => ['$in' => $this->emailsForCleanup],
            ]);
            $this->documentManager->clear();
            $this->userCache->clear();
        }

        parent::tearDown();
    }

    public function testPostUsersRejectsCaseInsensitiveDuplicateEmail(): void
    {
        $email = $this->mixedCaseEmail();
        $this->createUser($email);
        $duplicateEmail = mb_strtoupper($email, 'UTF-8');
        $this->emailsForCleanup[] = $duplicateEmail;

        $response = $this->requestJson('/api/users', [
            'email' => $duplicateEmail,
            'initials' => $this->faker->name(),
            'password' => $this->validPassword(),
        ]);

        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $this->assertStringContainsString(
            'This email address is already registered',
            (string) $response->getContent()
        );
    }

    private function createUser(string $email): void
    {
        $this->emailsForCleanup[] = $email;
        $user = $this->userFactory->create(
            $email,
            $this->faker->name(),
            $this->faker->sha256(),
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );
        $this->assertInstanceOf(User::class, $user);
        $this->userRepository->save($user);
        $this->documentManager->clear();
        $this->userCache->clear();
    }

    /**
     * @param array<string, string> $payload
     */
    private function requestJson(string $uri, array $payload): Response
    {
        return $this->httpKernel->handle(
            Request::create(
                $uri,
                Request::METHOD_POST,
                [],
                [],
                [],
                [
                    'REMOTE_ADDR' => $this->faker->ipv4(),
                    'HTTP_ACCEPT' => 'application/json',
                    'CONTENT_TYPE' => 'application/json',
                ],
                json_encode($payload, JSON_THROW_ON_ERROR)
            )
        );
    }

    private function usersCollection(): Collection
    {
        return $this->documentManager->getDocumentCollection(User::class);
    }

    private function mixedCaseEmail(): string
    {
        return sprintf(
            '%s%s@%s',
            ucfirst($this->faker->unique()->userName()),
            str_replace('-', '', $this->faker->uuid()),
            $this->faker->safeEmailDomain()
        );
    }

    private function validPassword(): string
    {
        return sprintf(
            '%sA1!',
            $this->faker->regexify('[A-Za-z0-9]{12}')
        );
    }
}
