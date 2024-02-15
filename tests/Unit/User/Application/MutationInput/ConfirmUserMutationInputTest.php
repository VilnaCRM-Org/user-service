<?php

namespace App\Tests\Unit\User\Application\MutationInput;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\MutationInput\ConfirmUserMutationInput;

class ConfirmUserMutationInputTest extends UnitTestCase
{
    public function testConstructWithToken(): void
    {
        $token = $this->faker->uuid();

        $input = new ConfirmUserMutationInput($token);

        $this->assertEquals($token, $input->token);
    }

    public function testConstructWithoutToken(): void
    {
        $input = new ConfirmUserMutationInput();

        $this->assertNull($input->token);
    }

    public function testGetValidationGroups()
    {
        $input = new ConfirmUserMutationInput();

        self::assertEquals([], $input->getValidationGroups());
    }
}
