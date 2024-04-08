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

final class UniqueEmailValidatorTest extends UnitTestCase
{
    private UserFactoryInterface $userFactory;
    private UuidTransformer $transformer;
    private UserRepositoryInterface $userRepository;
    private ExecutionContext $context;
    private TranslatorInterface $translator;
    private UniqueEmailValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = new UserFactory();
        $this->transformer = new UuidTransformer();
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->context = $this->createMock(ExecutionContext::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->validator = new UniqueEmailValidator(
            $this->userRepository,
            $this->translator
        );
        $this->validator->initialize($this->context);
    }

    public function testValidate(): void
    {
        $email = $this->faker->email();
        $errorMessage = $this->faker->word();
        $user = $this->userFactory->create(
            $this->faker->email(),
            $this->faker->name(),
            $this->faker->password(),
            $this->transformer->transformFromString($this->faker->uuid()),
        );

        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $this->translator->expects($this->once())
            ->method('trans')
            ->willReturn($errorMessage);

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($errorMessage);

        $this->validator->validate($email, new UniqueEmail());
    }

    public function testNull(): void
    {
        $this->context->expects($this->never())->method('buildViolation');
        $this->validator->validate(null, new UniqueEmail());
    }
}
