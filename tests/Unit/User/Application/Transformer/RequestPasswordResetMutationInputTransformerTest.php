<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Transformer;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\MutationInput\RequestPasswordResetMutationInput;
use App\User\Application\Transformer\RequestPasswordResetMutationInputTransformer;

final class RequestPasswordResetMutationInputTransformerTest extends UnitTestCase
{
    private RequestPasswordResetMutationInputTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transformer = new RequestPasswordResetMutationInputTransformer();
    }

    public function testTransformWithEmail(): void
    {
        $email = $this->faker->email();
        $args = ['email' => $email];
        
        $result = $this->transformer->transform($args);
        
        $this->assertInstanceOf(RequestPasswordResetMutationInput::class, $result);
        $this->assertEquals($email, $result->email);
    }

    public function testTransformWithoutEmail(): void
    {
        $args = [];
        
        $result = $this->transformer->transform($args);
        
        $this->assertInstanceOf(RequestPasswordResetMutationInput::class, $result);
        $this->assertNull($result->email);
    }

    public function testTransformWithNullEmail(): void
    {
        $args = ['email' => null];
        
        $result = $this->transformer->transform($args);
        
        $this->assertInstanceOf(RequestPasswordResetMutationInput::class, $result);
        $this->assertNull($result->email);
    }

    public function testTransformWithEmptyEmail(): void
    {
        $args = ['email' => ''];
        
        $result = $this->transformer->transform($args);
        
        $this->assertInstanceOf(RequestPasswordResetMutationInput::class, $result);
        $this->assertEquals('', $result->email);
    }

    public function testTransformWithExtraFields(): void
    {
        $email = $this->faker->email();
        $args = [
            'email' => $email,
            'extraField' => 'value',
            'anotherField' => 123
        ];
        
        $result = $this->transformer->transform($args);
        
        $this->assertInstanceOf(RequestPasswordResetMutationInput::class, $result);
        $this->assertEquals($email, $result->email);
    }

    public function testTransformWithValidEmailFormats(): void
    {
        $emails = [
            'simple@example.com',
            'user.name@domain.co.uk',
            'first+last@subdomain.example.org',
            'test123@test-domain.info'
        ];

        foreach ($emails as $email) {
            $args = ['email' => $email];
            $result = $this->transformer->transform($args);
            
            $this->assertEquals($email, $result->email);
        }
    }

    public function testTransformReturnType(): void
    {
        $args = ['email' => $this->faker->email()];
        
        $result = $this->transformer->transform($args);
        
        $this->assertInstanceOf(RequestPasswordResetMutationInput::class, $result);
    }
}