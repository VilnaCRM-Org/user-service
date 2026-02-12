<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth;

use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Integration\IntegrationTestCase;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

/**
 * @covers Bcrypt cost upgrade
 */
final class BcryptCostUpgradeTest extends IntegrationTestCase
{
    /**
     * @test
     * AC: NFR-32 - New registrations use cost 12
     */
    public function new_user_passwords_use_cost_12(): void
    {
        $userFactory = $this->container->get(UserFactoryInterface::class);
        $userRepository = $this->container->get(UserRepositoryInterface::class);
        $hasherFactory = $this->container->get(PasswordHasherFactoryInterface::class);
        $transformer = $this->container->get(UuidTransformer::class);

        $email = sprintf('cost12-test-%s@example.com', $this->faker->uuid());
        $password = 'TestPassword123!';
        $userId = $transformer->transformFromString($this->faker->uuid());

        $user = $userFactory->create($email, 'Test User', $password, $userId);
        $hasher = $hasherFactory->getPasswordHasher($user::class);
        $hash = $hasher->hash($password);
        $user->setPassword($hash);

        $userRepository->save($user);

        // Verify cost 12 is used (bcrypt hash format: $2y$12$...)
        $this->assertMatchesRegularExpression(
            '/^\$2y\$12\$/',
            $user->getPassword(),
            'New user passwords must use bcrypt cost 12 (AC: NFR-32)'
        );
    }

    /**
     * @test
     * AC: NFR-32 - Existing cost-4 hashes are verified and transparently upgraded on login
     */
    public function cost_4_hashes_are_upgraded_on_verification(): void
    {
        $userFactory = $this->container->get(UserFactoryInterface::class);
        $userRepository = $this->container->get(UserRepositoryInterface::class);
        $hasherFactory = $this->container->get(PasswordHasherFactoryInterface::class);
        $transformer = $this->container->get(UuidTransformer::class);

        $email = sprintf('cost4-upgrade-test-%s@example.com', $this->faker->uuid());
        $password = 'TestPassword123!';
        $userId = $transformer->transformFromString($this->faker->uuid());

        // Create user with cost-4 hash (simulate old hash)
        $user = $userFactory->create($email, 'Test User', $password, $userId);

        // Manually create a cost-4 hash
        $cost4Hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 4]);
        $user->setPassword($cost4Hash);
        $userRepository->save($user);

        $this->assertMatchesRegularExpression(
            '/^\$2y\$04\$/',
            $user->getPassword(),
            'Test setup: user should have cost-4 hash'
        );

        // Verify the password (should work)
        $hasher = $hasherFactory->getPasswordHasher($user::class);
        $isValid = $hasher->verify($user->getPassword(), $password);
        $this->assertTrue($isValid, 'Cost-4 password should still verify correctly');

        // Check if rehashing is needed (should be true for cost-4)
        $needsRehash = $hasher->needsRehash($user->getPassword());
        $this->assertTrue(
            $needsRehash,
            'Cost-4 hash must be flagged for rehashing (AC: NFR-32)'
        );

        // Simulate rehashing (what happens during login)
        if ($needsRehash) {
            $newHash = $hasher->hash($password);
            $user->setPassword($newHash);
            $userRepository->save($user);
        }

        // Verify the hash was upgraded to cost 12
        $this->assertMatchesRegularExpression(
            '/^\$2y\$12\$/',
            $user->getPassword(),
            'Cost-4 hash must be upgraded to cost 12 after verification (AC: NFR-32)'
        );
    }
}
