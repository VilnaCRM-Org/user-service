<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Transformer;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\MutationInput\ConfirmPasswordResetMutationInput;
use App\User\Application\Transformer\ConfirmPasswordResetMutationInputTransformer;

final class ConfirmPasswordResetMutationInputTransformerTest extends UnitTestCase
{
    private ConfirmPasswordResetMutationInputTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transformer = new ConfirmPasswordResetMutationInputTransformer();
    }

    public function testTransformWithAllFields(): void
    {
        $token = $this->faker->sha256();
        $newPassword = $this->faker->password();
        $args = [
            'token' => $token,
            'newPassword' => $newPassword
        ];
        
        $result = $this->transformer->transform($args);
        
        $this->assertInstanceOf(ConfirmPasswordResetMutationInput::class, $result);
        $this->assertEquals($token, $result->token);
        $this->assertEquals($newPassword, $result->newPassword);
    }

    public function testTransformWithoutFields(): void
    {
        $args = [];
        
        $result = $this->transformer->transform($args);
        
        $this->assertInstanceOf(ConfirmPasswordResetMutationInput::class, $result);
        $this->assertNull($result->token);
        $this->assertNull($result->newPassword);
    }

    public function testTransformWithTokenOnly(): void
    {
        $token = $this->faker->sha256();
        $args = ['token' => $token];
        
        $result = $this->transformer->transform($args);
        
        $this->assertInstanceOf(ConfirmPasswordResetMutationInput::class, $result);
        $this->assertEquals($token, $result->token);
        $this->assertNull($result->newPassword);
    }

    public function testTransformWithPasswordOnly(): void
    {
        $newPassword = $this->faker->password();
        $args = ['newPassword' => $newPassword];
        
        $result = $this->transformer->transform($args);
        
        $this->assertInstanceOf(ConfirmPasswordResetMutationInput::class, $result);
        $this->assertNull($result->token);
        $this->assertEquals($newPassword, $result->newPassword);
    }

    public function testTransformWithNullValues(): void
    {
        $args = [
            'token' => null,
            'newPassword' => null
        ];
        
        $result = $this->transformer->transform($args);
        
        $this->assertInstanceOf(ConfirmPasswordResetMutationInput::class, $result);
        $this->assertNull($result->token);
        $this->assertNull($result->newPassword);
    }

    public function testTransformWithEmptyStrings(): void
    {
        $args = [
            'token' => '',
            'newPassword' => ''
        ];
        
        $result = $this->transformer->transform($args);
        
        $this->assertInstanceOf(ConfirmPasswordResetMutationInput::class, $result);
        $this->assertEquals('', $result->token);
        $this->assertEquals('', $result->newPassword);
    }

    public function testTransformWithExtraFields(): void
    {
        $token = $this->faker->sha256();
        $newPassword = $this->faker->password();
        $args = [
            'token' => $token,
            'newPassword' => $newPassword,
            'extraField' => 'value',
            'anotherField' => 123
        ];
        
        $result = $this->transformer->transform($args);
        
        $this->assertInstanceOf(ConfirmPasswordResetMutationInput::class, $result);
        $this->assertEquals($token, $result->token);
        $this->assertEquals($newPassword, $result->newPassword);
    }

    public function testTransformWithComplexPassword(): void
    {
        $token = $this->faker->sha256();
        $complexPassword = 'Test123!@#$%^&*()_+-=[]{}|;:,.<>?';
        $args = [
            'token' => $token,
            'newPassword' => $complexPassword
        ];
        
        $result = $this->transformer->transform($args);
        
        $this->assertEquals($complexPassword, $result->newPassword);
    }

    public function testTransformReturnType(): void
    {
        $args = [
            'token' => $this->faker->sha256(),
            'newPassword' => $this->faker->password()
        ];
        
        $result = $this->transformer->transform($args);
        
        $this->assertInstanceOf(ConfirmPasswordResetMutationInput::class, $result);
    }
}