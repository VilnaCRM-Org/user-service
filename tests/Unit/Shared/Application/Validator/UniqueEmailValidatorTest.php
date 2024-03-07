<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator;

use App\Shared\Application\Transformer\UuidTransformer;
use App\Shared\Application\Validator\UniqueEmail;
use App\Shared\Application\Validator\UniqueEmailValidator;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Contracts\Translation\TranslatorInterface;

class UniqueEmailValidatorTest extends UnitTestCase
{
    private UserFactoryInterface $userFactory;
    private UuidTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = new UserFactory();
        $this->transformer = new UuidTransformer();
    }

    public function testValidate(): void
    {
        $email = $this->faker->email();
        $errorMessage = $this->faker->word();
        $user = $this->userFactory->create(
            $this->faker->email(),
            $this->faker->name(),
            $this->faker->password(),
            $this->transformer->transformFromString($this->faker->uuid),
        );

        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $translator = $this->createMock(TranslatorInterface::class);

        $translator->expects($this->once())
            ->method('trans')
            ->willReturn($errorMessage);

        $context = $this->createMock(ExecutionContext::class);

        $context->expects($this->once())
            ->method('buildViolation')
            ->with($errorMessage);

        $validator = new UniqueEmailValidator($userRepository, $translator);

        $validator->initialize($context);

        $constraint = new UniqueEmail();

        $validator->validate($email, $constraint);
    }
}
