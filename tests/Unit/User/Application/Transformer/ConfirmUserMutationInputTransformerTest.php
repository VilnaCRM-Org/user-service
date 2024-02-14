<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Transformer;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\MutationInput\ConfirmUserMutationInput;
use App\User\Application\Transformer\ConfirmUserMutationInputTransformer;

class ConfirmUserMutationInputTransformerTest extends UnitTestCase
{
    public function testTransform(): void
    {
        $transformer = new ConfirmUserMutationInputTransformer();
        $token = $this->faker->uuid();
        $args = ['token' => $token];

        $input = $transformer->transform($args);

        $this->assertInstanceOf(ConfirmUserMutationInput::class, $input);
        $this->assertSame($token, $input->token);
    }
}
