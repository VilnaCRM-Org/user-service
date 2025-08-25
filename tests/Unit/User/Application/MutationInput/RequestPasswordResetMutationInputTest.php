<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\MutationInput;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\MutationInput\RequestPasswordResetMutationInput;

final class RequestPasswordResetMutationInputTest extends UnitTestCase
{
    public function testConstructWithEmail(): void
    {
        $email = $this->faker->safeEmail();

        $input = new RequestPasswordResetMutationInput($email);

        $this->assertInstanceOf(RequestPasswordResetMutationInput::class, $input);
        $this->assertSame($email, $input->email);
    }

    public function testConstructWithDefaults(): void
    {
        $input = new RequestPasswordResetMutationInput();

        $this->assertInstanceOf(RequestPasswordResetMutationInput::class, $input);
        $this->assertNull($input->email);
    }
}