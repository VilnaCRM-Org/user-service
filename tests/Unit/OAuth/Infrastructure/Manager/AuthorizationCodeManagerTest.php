<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Infrastructure\Manager;

use App\OAuth\Infrastructure\Manager\AuthorizationCodeManager;
use App\Tests\Unit\OAuth\Infrastructure\OAuthInfrastructureTestCase;
use DateTimeImmutable;
use Doctrine\ODM\MongoDB\DocumentManager;
use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCode;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;

final class AuthorizationCodeManagerTest extends OAuthInfrastructureTestCase
{
    public function testFindReturnsAuthorizationCodeWhenFound(): void
    {
        $identifier = $this->faker->lexify('code_????????');
        $authCode = $this->makeAuthorizationCode($identifier);

        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->expects($this->once())
            ->method('find')
            ->with(AuthorizationCode::class, $identifier)
            ->willReturn($authCode);

        $manager = new AuthorizationCodeManager($documentManager);

        $this->assertSame($authCode, $manager->find($identifier));
    }

    public function testSavePersistsAuthorizationCode(): void
    {
        $authCode = $this->makeAuthorizationCode($this->faker->lexify('code_????????'));

        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->expects($this->once())->method('persist')->with($authCode);
        $documentManager->expects($this->once())->method('flush');
        $documentManager->expects($this->once())->method('refresh')->with($authCode);

        $manager = new AuthorizationCodeManager($documentManager);

        $manager->save($authCode);
    }

    public function testClearExpiredRemovesAuthorizationCodes(): void
    {
        $captures = [];
        $result = new class(3) {
            public function __construct(private readonly int $count)
            {
            }

            public function getDeletedCount(): int
            {
                return $this->count;
            }
        };
        $builder = $this->makeBuilder($result, $captures);

        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->expects($this->once())
            ->method('createQueryBuilder')
            ->with(AuthorizationCode::class)
            ->willReturn($builder);

        $manager = new AuthorizationCodeManager($documentManager);

        $this->assertSame(3, $manager->clearExpired());
        $this->assertTrue($captures['remove'] ?? false);
        $this->assertNotEmpty($captures['lt']);
    }

    public function testClearExpiredReturnsIntDirectly(): void
    {
        $captures = [];
        $builder = $this->makeBuilder(5, $captures);

        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->expects($this->once())
            ->method('createQueryBuilder')
            ->with(AuthorizationCode::class)
            ->willReturn($builder);

        $manager = new AuthorizationCodeManager($documentManager);

        $this->assertSame(5, $manager->clearExpired());
    }

    public function testClearExpiredReturnsZeroForNullResult(): void
    {
        $captures = [];
        $builder = $this->makeBuilder(null, $captures);

        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->expects($this->once())
            ->method('createQueryBuilder')
            ->with(AuthorizationCode::class)
            ->willReturn($builder);

        $manager = new AuthorizationCodeManager($documentManager);

        $this->assertSame(0, $manager->clearExpired());
    }

    private function makeAuthorizationCode(string $identifier): AuthorizationCode
    {
        $client = new Client(
            $this->faker->company(),
            $this->faker->lexify('client_????????'),
            $this->faker->sha1()
        );

        return new AuthorizationCode(
            $identifier,
            DateTimeImmutable::createFromMutable(
                $this->faker->dateTimeBetween('+1 hour', '+2 hours')
            ),
            $client,
            $this->faker->optional()->userName(),
            [new Scope($this->faker->lexify('scope_????'))]
        );
    }
}
