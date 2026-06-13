<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Entity;

final class UserTwoFactorTest extends UserTestCase
{
    public function testTwoFactorIsDisabledByDefault(): void
    {
        $user = $this->userFactory->create(
            $this->faker->email(),
            $this->faker->name(),
            $this->faker->password(),
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );

        $this->assertFalse($user->isTwoFactorEnabled());
        $this->assertNull($user->getTwoFactorSecret());
    }

    public function testSetTwoFactorData(): void
    {
        $secret = $this->faker->sha256();

        $this->user->setTwoFactorEnabled(true);
        $this->user->setTwoFactorSecret($secret);

        $this->assertTrue($this->user->isTwoFactorEnabled());
        $this->assertSame($secret, $this->user->getTwoFactorSecret());
    }

    public function testEnableTwoFactor(): void
    {
        $this->assertFalse($this->user->isTwoFactorEnabled());

        $this->user->enableTwoFactor();

        $this->assertTrue($this->user->isTwoFactorEnabled());
    }

    public function testDisableTwoFactor(): void
    {
        $this->user->setTwoFactorEnabled(true);
        $this->user->setTwoFactorSecret($this->faker->sha256());
        $this->user->recordAcceptedTotpTimestep(57_000_000);

        $this->user->disableTwoFactor();

        $this->assertFalse($this->user->isTwoFactorEnabled());
        $this->assertNull($this->user->getTwoFactorSecret());
        $this->assertNull($this->user->getLastAcceptedTotpTimestep());
    }

    public function testTotpTimestepIsNotReplayWhenNoneAccepted(): void
    {
        $this->assertNull($this->user->getLastAcceptedTotpTimestep());
        $this->assertFalse($this->user->isTotpTimestepReplay(57_000_000));
    }

    public function testRecordAcceptedTotpTimestepStoresLatestCounter(): void
    {
        $this->user->recordAcceptedTotpTimestep(57_000_000);

        $this->assertSame(57_000_000, $this->user->getLastAcceptedTotpTimestep());
    }

    public function testIsTotpTimestepReplayRejectsCurrentAndOlderTimesteps(): void
    {
        $this->user->recordAcceptedTotpTimestep(57_000_000);

        $this->assertTrue($this->user->isTotpTimestepReplay(57_000_000));
        $this->assertTrue($this->user->isTotpTimestepReplay(56_999_999));
        $this->assertFalse($this->user->isTotpTimestepReplay(57_000_001));
    }

    public function testSetLastAcceptedTotpTimestepSupportsHydration(): void
    {
        $this->user->setLastAcceptedTotpTimestep(57_000_000);
        $this->assertSame(57_000_000, $this->user->getLastAcceptedTotpTimestep());

        $this->user->setLastAcceptedTotpTimestep(null);
        $this->assertNull($this->user->getLastAcceptedTotpTimestep());
    }
}
