<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator;

use App\Shared\Application\Checker\EmailUniquenessChecker;
use App\Shared\Application\Provider\Http\RouteIdentifierProvider;
use App\Shared\Application\Validator\Constraint\UniqueEmail;
use App\Shared\Application\Validator\UniqueEmailValidator;
use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UniqueEmailValidatorTest extends UnitTestCase
{
    private UserFactoryInterface $userFactory;
    private UuidTransformer $transformer;
    private UserRepositoryInterface $userRepository;
    private EmailUniquenessChecker $emailUniquenessChecker;
    private ExecutionContext $context;
    private TranslatorInterface $translator;
    private RequestStack $requestStack;
    private Request $request;
    private UniqueEmailValidator $validator;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = new UserFactory();
        $this->transformer = new UuidTransformer(new UuidFactory());
        $this->userRepository =
            $this->createMock(UserRepositoryInterface::class);
        $this->context = $this->createMock(ExecutionContext::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->requestStack = new RequestStack();
        $this->request = new Request();
        $this->requestStack->push($this->request);
        $this->emailUniquenessChecker = new EmailUniquenessChecker(
            $this->userRepository,
            new RouteIdentifierProvider($this->requestStack)
        );
        $this->validator = new UniqueEmailValidator(
            $this->emailUniquenessChecker,
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

        $this->request->attributes->set('id', $this->faker->uuid());

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

    public function testAllowsSameUserEmailOnUpdate(): void
    {
        $userId = $this->faker->uuid();
        $email = $this->faker->email();
        $user = $this->userFactory->create(
            $email,
            $this->faker->name(),
            $this->faker->password(),
            $this->transformer->transformFromString($userId),
        );

        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $this->context->expects($this->never())->method('buildViolation');
        $this->translator->expects($this->never())->method('trans');

        $this->request->attributes->set('id', $userId);

        $this->validator->validate($email, new UniqueEmail());
    }

    public function testBlankStringSkipsValidation(): void
    {
        $this->translator->expects($this->never())->method('trans');
        $this->context->expects($this->never())->method('buildViolation');
        $this->userRepository->expects($this->never())->method('findByEmail');

        $this->validator->validate('   ', new UniqueEmail());
    }

    public function testDuplicateEmailWithoutRequestStackTriggersViolation(): void
    {
        $email = $this->faker->email();
        $user = $this->createUserWithEmail($email);

        $this->setupUserFoundExpectation($email, $user);

        // Remove the current request so isSameUser() cannot short-circuit.
        $this->requestStack->pop();

        $this->setupViolationExpectations();

        $this->validator->validate($email, new UniqueEmail());
    }

    public function testDuplicateEmailWithNonStringRouteIdTriggersViolation(): void
    {
        $email = $this->faker->email();
        $user = $this->createUserWithEmail($email);

        $this->setupUserFoundExpectation($email, $user);
        $this->request->attributes->set('id', ['not-a-string']);
        $this->setupViolationExpectations();

        $this->validator->validate($email, new UniqueEmail());
    }

    public function testSameUserComparisonNormalizesIdentifier(): void
    {
        $userId = strtoupper(str_replace('-', '', $this->faker->uuid()));
        $email = $this->faker->email();

        $user = $this->userFactory->create(
            $email,
            $this->faker->name(),
            $this->faker->password(),
            $this->transformer->transformFromString($userId)
        );

        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);

        $this->request->attributes->set(
            'id',
            strtolower(sprintf('%s-%s', substr($userId, 0, 8), substr($userId, 8)))
        );

        $this->translator->expects($this->never())->method('trans');
        $this->context->expects($this->never())->method('buildViolation');

        $this->validator->validate($email, new UniqueEmail());
    }

    private function createUserWithEmail(string $email): UserInterface
    {
        return $this->userFactory->create(
            $email,
            $this->faker->name(),
            $this->faker->password(),
            $this->transformer->transformFromString($this->faker->uuid()),
        );
    }

    private function setupUserFoundExpectation(string $email, UserInterface $user): void
    {
        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn($user);
    }

    private function setupViolationExpectations(): void
    {
        $message = $this->faker->sentence();

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('email.not.unique')
            ->willReturn($message);

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($message);
    }
}
