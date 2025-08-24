<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\MutationInput;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\MutationInput\ConfirmPasswordResetMutationInput;

final class ConfirmPasswordResetMutationInputTest extends UnitTestCase
{
    public function testConstructWithAllParameters(): void
    {
        $token = $this->faker->sha256();
        $newPassword = $this->faker->password();

        $input = new ConfirmPasswordResetMutationInput($token, $newPassword);

        $this->assertInstanceOf(ConfirmPasswordResetMutationInput::class, $input);
        $this->assertSame($token, $input->token);
        $this->assertSame($newPassword, $input->newPassword);
    }

    public function testConstructWithDefaults(): void
    {
        $input = new ConfirmPasswordResetMutationInput();

        $this->assertInstanceOf(ConfirmPasswordResetMutationInput::class, $input);
        $this->assertNull($input->token);
        $this->assertNull($input->newPassword);
    }

    public function testConstructWithPartialParameters(): void
    {
        $token = $this->faker->sha256();

        $input = new ConfirmPasswordResetMutationInput($token);

        $this->assertInstanceOf(ConfirmPasswordResetMutationInput::class, $input);
        $this->assertSame($token, $input->token);
        $this->assertNull($input->newPassword);
    }
}