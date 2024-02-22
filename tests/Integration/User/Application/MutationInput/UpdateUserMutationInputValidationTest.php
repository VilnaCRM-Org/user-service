<?php

declare(strict_types=1);

namespace App\Tests\Integration\User\Application\MutationInput;

use App\Tests\Integration\IntegrationTestCase;
use App\Tests\Unit\TestValidationUtils;
use App\User\Application\MutationInput\UpdateUserMutationInput;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UpdateUserMutationInputValidationTest extends IntegrationTestCase
{
    private ValidatorInterface $validator;
    private TestValidationUtils $validationUtils;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = $this->container->get('validator');
        $this->validationUtils = new TestValidationUtils();
    }

    public function testMaxEmailLength(): void
    {
        $email = $this->validationUtils->addCharToBeginning($this->faker->email(), 256, 'a');
        $dto = new UpdateUserMutationInput(
            $this->validationUtils->getValidPassword(),
            $this->validationUtils->getValidInitials(),
            $email,
            $this->validationUtils->getValidPassword(),
        );

        $errors = $this->validator->validate($dto);

        $this->assertCount(1, $errors);
        $this->assertSame('email', $errors[0]->getPropertyPath());
        $this->assertSame('This value is too long. It should have 255 characters or less.', $errors[0]->getMessage());
    }

    public function testMaxInitialsLength(): void
    {
        $dto = new UpdateUserMutationInput(
            $this->validationUtils->getValidPassword(),
            $this->validationUtils->addCharToBeginning(
                $this->validationUtils->getValidInitials(),
                256,
                'a'
            ),
            $this->faker->email(),
            $this->validationUtils->getValidPassword(),
        );

        $errors = $this->validator->validate($dto);

        $this->assertCount(1, $errors);
        $this->assertSame('initials', $errors[0]->getPropertyPath());
        $this->assertSame('This value is too long. It should have 255 characters or less.', $errors[0]->getMessage());
    }
}
