<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth;

use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

final class BcryptCostUpgradeTest extends AuthIntegrationTestCase
{
    private UserFactoryInterface $userFactory;
    private UserRepositoryInterface $userRepository;
    private PasswordHasherFactoryInterface $hasherFactory;
    private UuidTransformer $transformer;
    private HttpKernelInterface $httpKernel;
    private DocumentManager $documentManager;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->userFactory = $this->container->get(UserFactoryInterface::class);
        $this->userRepository = $this->container->get(UserRepositoryInterface::class);
        $this->hasherFactory = $this->container->get(PasswordHasherFactoryInterface::class);
        $this->transformer = $this->container->get(UuidTransformer::class);
        $this->httpKernel = $this->container->get('kernel');
        $this->documentManager = $this->container->get('doctrine_mongodb.odm.document_manager');
    }

    /**
     * AC: NFR-32 - New registrations use cost 12
     */
    public function testNewUserPasswordsUseCost12(): void
    {
        $email = sprintf('cost12-test-%s@example.com', $this->faker->uuid());
        $password = 'TestPassword123!';
        $userId = $this->transformer->transformFromString($this->faker->uuid());
        $user = $this->userFactory->create($email, 'Test User', $password, $userId);
        $hasher = $this->hasherFactory->getPasswordHasher($user::class);
        $user->setPassword($hasher->hash($password));
        $this->userRepository->save($user);
        $this->assertMatchesRegularExpression(
            '/^\$2y\$12\$/',
            $user->getPassword(),
            'New user passwords must use bcrypt cost 12 (AC: NFR-32)'
        );
    }

    /**
     * AC: NFR-32 - Existing cost-4 hashes are verified and transparently upgraded on login
     */
    public function testCost4HashesAreUpgradedOnLogin(): void
    {
        $password = 'TestPassword123!';
        $user = $this->createUserWithCost4Hash($password);
        $this->assertCost4Hash($user);

        $this->signIn($user->getEmail(), $password);

        $this->documentManager->clear();
        $reloadedUser = $this->userRepository->findByEmail($user->getEmail());
        $this->assertInstanceOf(User::class, $reloadedUser);
        $this->assertMatchesRegularExpression(
            '/^\$2y\$12\$/',
            $reloadedUser->getPassword(),
            'Cost-4 hash must be upgraded to cost 12 after login (AC: NFR-32)'
        );
    }

    private function createUserWithCost4Hash(string $password): User
    {
        $email = sprintf('cost4-upgrade-test-%s@example.com', $this->faker->uuid());
        $userId = $this->transformer->transformFromString($this->faker->uuid());
        $user = $this->userFactory->create($email, 'Test User', $password, $userId);
        $cost4Hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 4]);
        $user->setPassword($cost4Hash);
        $this->userRepository->save($user);

        return $user;
    }

    private function assertCost4Hash(User $user): void
    {
        $this->assertMatchesRegularExpression(
            '/^\$2y\$04\$/',
            $user->getPassword(),
            'Test setup: user should have cost-4 hash'
        );
    }

    private function signIn(string $email, string $password): void
    {
        $content = json_encode([
            'email' => $email,
            'password' => $password,
        ], JSON_THROW_ON_ERROR);
        $response = $this->httpKernel->handle(
            Request::create(
                '/api/signin',
                Request::METHOD_POST,
                [],
                [],
                [],
                [
                    'REMOTE_ADDR' => '127.0.0.1',
                    'HTTP_ACCEPT' => 'application/json',
                    'CONTENT_TYPE' => 'application/json',
                ],
                $content
            )
        );
        $this->assertSame(200, $response->getStatusCode());
    }
}
