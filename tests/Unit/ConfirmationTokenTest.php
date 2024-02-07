<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\User\Domain\Entity\ConfirmationToken;
use App\User\Domain\Exception\UserTimedOutException;
use DG\BypassFinals;
use Faker\Factory;
use Faker\Generator;
use PHPUnit\Framework\TestCase;

class ConfirmationTokenTest extends TestCase
{
    private ConfirmationToken $confirmationToken;
    private Generator $faker;

    protected function setUp(): void
    {
        BypassFinals::enable(bypassReadOnly: false);
        parent::setUp();

        // Initialize Faker
        $this->faker = Factory::create();

        // Create a confirmation token instance
        $this->confirmationToken = new ConfirmationToken(
            $this->faker->uuid(),
            $this->faker->uuid()
        );
    }

    public function testSend(): void
    {
        $this->confirmationToken->send();
        $this->confirmationToken->send();
        $this->expectException(UserTimedOutException::class);
        $this->confirmationToken->send();
    }
}
