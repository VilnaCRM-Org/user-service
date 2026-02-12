<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\Shared\Auth\Factory\TestAccessTokenFactory;
use Faker\Factory;
use Faker\Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/** @SuppressWarnings(PHPMD.NumberOfChildren) */
abstract class IntegrationTestCase extends KernelTestCase
{
    use MailerAssertionsTrait;

    protected Generator $faker;
    protected ContainerInterface $container;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->container = $this->getContainer();

        $this->faker = Factory::create();
    }

    protected function createBearerTokenForUser(
        string $userId
    ): string {
        return $this->testAccessTokenFactory()->createUserToken($userId);
    }

    protected function createBearerTokenForRole(string $role): string
    {
        if ($role === 'ROLE_SERVICE') {
            return $this->testAccessTokenFactory()->createServiceToken();
        }

        return $this->testAccessTokenFactory()->createToken(
            sprintf('subject-%s', strtolower($this->faker->lexify('????'))),
            [$role]
        );
    }

    /**
     * @param list<string> $roles
     *
     * @return string[]
     *
     * @psalm-return array{HTTP_AUTHORIZATION: string, HTTP_ACCEPT: 'application/json'}
     */
    protected function createAuthenticatedHeaders(
        string $subject,
        array $roles = ['ROLE_USER']
    ): array {
        $token = $this->testAccessTokenFactory()->createToken(
            $subject,
            $roles
        );

        return [
            'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token),
            'HTTP_ACCEPT' => 'application/json',
        ];
    }

    private function testAccessTokenFactory(): TestAccessTokenFactory
    {
        return $this->container->get(TestAccessTokenFactory::class);
    }
}
