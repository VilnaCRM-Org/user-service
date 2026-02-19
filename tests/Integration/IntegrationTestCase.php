<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\Shared\Auth\Factory\TestAccessTokenFactory;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Repository\AuthSessionRepositoryInterface;
use DateTimeImmutable;
use Faker\Factory;
use Faker\Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Uid\Factory\UlidFactory;

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
        $sessionId = $this->createActiveSession($userId);

        return $this->testAccessTokenFactory()->createUserToken(
            $userId,
            $sessionId
        );
    }

    protected function createBearerTokenForRole(string $role): string
    {
        if ($role === 'ROLE_SERVICE') {
            return $this->testAccessTokenFactory()->createServiceToken();
        }

        $subject = sprintf('subject-%s', strtolower($this->faker->lexify('????')));
        $sessionId = $this->createActiveSession($subject);

        return $this->testAccessTokenFactory()->createToken(
            $subject,
            [$role],
            $sessionId
        );
    }

    /**
     * @param list<string> $roles
     *
     * @return array<string>
     *
     * @psalm-return array{HTTP_AUTHORIZATION: string, HTTP_ACCEPT: 'application/json'}
     */
    protected function createAuthenticatedHeaders(
        string $subject,
        array $roles = ['ROLE_USER']
    ): array {
        $sessionId = in_array('ROLE_SERVICE', $roles, true)
            ? null
            : $this->createActiveSession($subject);

        $token = $this->testAccessTokenFactory()
            ->createToken($subject, $roles, $sessionId);

        return [
            'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token),
            'HTTP_ACCEPT' => 'application/json',
        ];
    }

    private function testAccessTokenFactory(): TestAccessTokenFactory
    {
        return $this->container->get(TestAccessTokenFactory::class);
    }

    private function createActiveSession(string $userId): string
    {
        $sessionId = (string) $this->container
            ->get(UlidFactory::class)
            ->create();
        $createdAt = new DateTimeImmutable('-1 minute');

        $this->container
            ->get(AuthSessionRepositoryInterface::class)
            ->save(
                new AuthSession(
                    $sessionId,
                    $userId,
                    $this->faker->ipv4(),
                    'IntegrationTestCase',
                    $createdAt,
                    $createdAt->modify('+15 minutes'),
                    false
                )
            );

        return $sessionId;
    }
}
