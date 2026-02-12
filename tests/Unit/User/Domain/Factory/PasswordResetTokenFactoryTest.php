<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Factory;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\PasswordResetToken;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Factory\PasswordResetTokenFactory;
use App\User\Domain\Factory\PasswordResetTokenFactoryInterface;

final class PasswordResetTokenFactoryTest extends UnitTestCase
{
    /**
     * @dataProvider expirationProvider
     */
    public function testCreateBuildsTokenWithExpectedAttributes(
        int $tokenLength,
        int $expirationHours
    ): void {
        $factory = $this->createFactory($tokenLength, $expirationHours);
        $userId = $this->faker->uuid();

        $token = $factory->create($userId);

        $this->assertInstanceOf(PasswordResetToken::class, $token);
        $this->assertSame($userId, $token->getUserID());
        $this->assertSame($tokenLength * 2, strlen($token->getTokenValue()));
        $this->assertFalse($token->isUsed());
        $this->assertSame($expirationHours, $this->calculateHoursBetween($token));

        if ($expirationHours > 0) {
            $this->assertFalse($token->isExpired());
        }
    }

    public function testZeroHourExpirationIsImmediate(): void
    {
        $factory = $this->createFactory(16, 0);

        $token = $factory->create($this->faker->uuid());

        $this->assertSame(
            $token->getCreatedAt()->getTimestamp(),
            $token->getExpiresAt()->getTimestamp()
        );

        $this->assertTrue(
            $token->isExpired($token->getCreatedAt()->modify('+1 second'))
        );
    }

    public function testTokensAreUniquePerCreation(): void
    {
        $factory = $this->createFactory(16, 1);

        $firstToken = $factory->create($this->faker->uuid());
        $secondToken = $factory->create($this->faker->uuid());

        $this->assertNotSame(
            $firstToken->getTokenValue(),
            $secondToken->getTokenValue()
        );
    }

    public function testOneAndTwoHourExpirationsRemainOneHourApart(): void
    {
        $userId = $this->faker->uuid();

        $oneHourFactory = $this->createFactory(16, 1);
        $twoHourFactory = $this->createFactory(16, 2);

        $oneHourToken = $oneHourFactory->create($userId);
        $twoHourToken = $twoHourFactory->create($userId);

        $this->assertSame(1, $this->calculateHoursBetween($oneHourToken));
        $this->assertSame(2, $this->calculateHoursBetween($twoHourToken));

        $twoHourTimestamp = $twoHourToken->getExpiresAt()->getTimestamp();
        $oneHourTimestamp = $oneHourToken->getExpiresAt()->getTimestamp();
        $difference = $twoHourTimestamp - $oneHourTimestamp;

        $this->assertBetween($difference, 3600, 2);
    }

    public function testHourUnitUsesSingularOnlyForOneHour(): void
    {
        $reflection = new \ReflectionClass(PasswordResetTokenFactory::class);
        $method = $reflection->getMethod('getHourUnit');
        $this->makeAccessible($method);

        $oneHourUnit = $method->invoke($this->createFactory(16, 1));
        $zeroHourUnit = $method->invoke($this->createFactory(16, 0));
        $twoHourUnit = $method->invoke($this->createFactory(16, 2));

        $this->assertSame('hour', $oneHourUnit);
        $this->assertSame('hours', $zeroHourUnit);
        $this->assertSame('hours', $twoHourUnit);
    }

    /**
     * @psalm-return \Generator<'full day'|'one hour'|'two hours', list{16|32, 1|2|24}, mixed, void>
     */
    public static function expirationProvider(): \Generator
    {
        yield 'one hour' => [16, 1];
        yield 'two hours' => [16, 2];
        yield 'full day' => [32, 24];
    }

    private function createFactory(int $length, int $hours): PasswordResetTokenFactory
    {
        return new PasswordResetTokenFactory($length, $hours);
    }

    private function calculateHoursBetween(PasswordResetTokenInterface $token): int
    {
        $expiresAt = $token->getExpiresAt()->getTimestamp();
        $createdAt = $token->getCreatedAt()->getTimestamp();
        $diffInSeconds = $expiresAt - $createdAt;

        return (int) round($diffInSeconds / 3600);
    }

    private function assertBetween(int $actual, int $expected, int $tolerance): void
    {
        $this->assertGreaterThanOrEqual($expected - $tolerance, $actual);
        $this->assertLessThanOrEqual($expected + $tolerance, $actual);
    }
}
