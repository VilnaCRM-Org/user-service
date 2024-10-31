<?php

declare(strict_types=1);

namespace App\Tests\Builders;

use App\User\Domain\Entity\ConfirmationToken;
use DateTimeImmutable;
use Faker\Factory;
use Webmozart\Assert\Assert;

final class ConfirmationTokenBuilder
{
    private string $tokenValue;
    private string $userID;

    private int $timesSent;
    private DateTimeImmutable $allowedToSendAfter;

    public function __construct()
    {
        $faker = Factory::create();

        $this->tokenValue = (string) $faker->randomNumber(6, true);
        $this->userID = $faker->uuid();
        $this->timesSent = 0;
        $this->allowedToSendAfter = new DateTimeImmutable();
    }

    public function withTokenValue(string $tokenValue): self
    {
        $clone = clone $this;
        $clone->tokenValue = $tokenValue;
        return $clone;
    }

    public function withUserID(string $userID): self
    {
        $clone = clone $this;
        $clone->userID = $userID;
        return $clone;
    }

    public function withTimesSent(int $timesSent): self
    {
        Assert::range($timesSent, 0, 4);

        $clone = clone $this;
        $clone->timesSent = $timesSent;
        return $clone;
    }

    public function withAllowedToSendAfter(DateTimeImmutable $date): self
    {
        $clone = clone $this;
        $clone->allowedToSendAfter = $date;
        return $clone;
    }

    public function build(): ConfirmationToken
    {
        $token = new ConfirmationToken(
            $this->tokenValue,
            $this->userID,
        );

        $token->setTimesSent($this->timesSent);
        $token->setAllowedToSendAfter($this->allowedToSendAfter);

        return $token;
    }
}
