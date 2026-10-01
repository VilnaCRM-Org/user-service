<?php

declare(strict_types=1);

namespace App\Tests\Unit\DataFixtures\Command;

use App\Shared\Application\Provider\CurrentTimestampProviderInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Factory\AccessTokenFactoryInterface;
use App\User\Infrastructure\Fixture\Command\IssueLoadTestServiceTokenCommand;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Uid\Factory\UlidFactory;
use Symfony\Component\Uid\Factory\UuidFactory;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

final class IssueLoadTestServiceTokenCommandTest extends UnitTestCase
{
    private const NOW = 1_700_000_000;

    private AccessTokenFactoryInterface&MockObject $accessTokenFactory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->accessTokenFactory = $this->createMock(AccessTokenFactoryInterface::class);
    }

    public function testPrintsAKmsSignedServiceToken(): void
    {
        $this->accessTokenFactory->expects($this->once())
            ->method('create')
            ->with($this->identicalTo([
                'sub' => 'load-test-service',
                'iss' => 'vilnacrm-user-service',
                'aud' => 'vilnacrm-api',
                'exp' => self::NOW + 900,
                'iat' => self::NOW,
                'nbf' => self::NOW,
                'jti' => '0189b9d6-7a2c-7b3a-9f00-000000000001',
                'sid' => '01H8XGJWBWBAQ4Z4Z4Z4Z4Z4Z4',
                'roles' => ['ROLE_SERVICE'],
            ]))
            ->willReturn('kms.signed.jwt');
        $tester = new CommandTester($this->command('load_test'));

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertSame('kms.signed.jwt', $tester->getDisplay());
    }

    public function testRefusesToMintTokensInProduction(): void
    {
        $this->accessTokenFactory->expects($this->never())->method('create');
        $tester = new CommandTester($this->command('prod'));

        self::assertSame(Command::FAILURE, $tester->execute([]));
        self::assertStringContainsString(
            'Load-test tokens are never issued in production.',
            $tester->getDisplay()
        );
    }

    private function command(string $environment): IssueLoadTestServiceTokenCommand
    {
        $clock = $this->createMock(CurrentTimestampProviderInterface::class);
        $clock->method('currentTimestamp')->willReturn(self::NOW);
        $uuidFactory = $this->createMock(UuidFactory::class);
        $uuidFactory->method('create')
            ->willReturn(Uuid::fromString('0189b9d6-7a2c-7b3a-9f00-000000000001'));
        $ulidFactory = $this->createMock(UlidFactory::class);
        $ulidFactory->method('create')->willReturn(new Ulid('01H8XGJWBWBAQ4Z4Z4Z4Z4Z4Z4'));

        return new IssueLoadTestServiceTokenCommand(
            $this->accessTokenFactory,
            $clock,
            $uuidFactory,
            $ulidFactory,
            $environment
        );
    }
}
