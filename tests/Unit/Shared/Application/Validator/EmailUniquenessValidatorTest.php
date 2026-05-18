<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator;

use App\Shared\Application\Provider\Http\RouteIdentifierProvider;
use App\Shared\Application\Validator\EmailUniquenessValidator;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Collection\UserCollection;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use function mb_strtolower;
use function sprintf;
use function strtolower;
use function strtoupper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use function trim;
use function ucfirst;

final class EmailUniquenessValidatorTest extends UnitTestCase
{
    private UserRepositoryInterface $repository;
    private RequestStack $requestStack;
    private EmailUniquenessValidator $checker;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->requestStack = new RequestStack();
        $this->checker = new EmailUniquenessValidator(
            $this->repository,
            new RouteIdentifierProvider($this->requestStack)
        );
    }

    public function testReturnsTrueWhenUserIsNotFound(): void
    {
        $this->repository->expects($this->once())
            ->method('findByEmails')
            ->with(['unique@example.com'])
            ->willReturn(new UserCollection());

        $this->assertTrue($this->checker->isUnique('unique@example.com'));
    }

    public function testReturnsFalseWhenIdentifierIsMissing(): void
    {
        $existing = $this->createMock(UserInterface::class);
        $existing->method('getId')->willReturn('identifier');

        $this->repository->expects($this->once())
            ->method('findByEmails')
            ->with(['duplicate@example.com'])
            ->willReturn(new UserCollection([$existing]));

        $this->requestStack->push(new Request());

        $this->assertFalse($this->checker->isUnique('duplicate@example.com'));
    }

    public function testNormalizesEmailBeforeLookup(): void
    {
        $email = $this->faker->unique()->safeEmail();
        $submittedEmail = sprintf('  %s  ', strtoupper($email));
        $trimmedEmail = trim($submittedEmail);
        $normalizedEmail = mb_strtolower($trimmedEmail, 'UTF-8');

        $existing = $this->createMock(UserInterface::class);
        $existing->method('getId')->willReturn($this->faker->uuid());

        $this->repository->expects($this->once())
            ->method('findByEmails')
            ->with([$normalizedEmail, $trimmedEmail])
            ->willReturn(new UserCollection([$existing]));

        $this->requestStack->push(new Request());

        $this->assertFalse($this->checker->isUnique($submittedEmail));
    }

    public function testNormalizesMultibyteEmailBeforeLookup(): void
    {
        $submittedEmail = sprintf(
            '  Д%s@%s  ',
            strtolower($this->faker->unique()->lexify('??????')),
            strtolower($this->faker->safeEmailDomain())
        );
        $trimmedEmail = trim($submittedEmail);
        $normalizedEmail = mb_strtolower($trimmedEmail, 'UTF-8');

        $existing = $this->createMock(UserInterface::class);
        $existing->method('getId')->willReturn($this->faker->uuid());

        $this->repository->expects($this->once())
            ->method('findByEmails')
            ->with([$normalizedEmail, $trimmedEmail])
            ->willReturn(new UserCollection([$existing]));

        $this->requestStack->push(new Request());

        $this->assertFalse($this->checker->isUnique($submittedEmail));
    }

    public function testChecksTrimmedOriginalEmailCandidate(): void
    {
        $trimmedEmail = $this->mixedCaseEmail();
        $submittedEmail = sprintf('  %s  ', $trimmedEmail);
        $expectedLookups = [
            mb_strtolower($trimmedEmail, 'UTF-8'),
            $trimmedEmail,
        ];

        $existing = $this->createMock(UserInterface::class);
        $existing->method('getId')->willReturn($this->faker->uuid());

        $this->repository->expects($this->once())
            ->method('findByEmails')
            ->with($expectedLookups)
            ->willReturn(new UserCollection([$existing]));
        $this->requestStack->push(new Request());

        $this->assertFalse($this->checker->isUnique($submittedEmail));
    }

    public function testLowercaseSubmissionCanMatchLegacyMixedCaseUser(): void
    {
        $existing = $this->createMock(UserInterface::class);
        $existing->method('getId')->willReturn($this->faker->uuid());

        $this->repository->expects($this->once())
            ->method('findByEmails')
            ->with(['legacy@example.com'])
            ->willReturn(new UserCollection([$existing]));

        $this->requestStack->push(new Request());

        $this->assertFalse($this->checker->isUnique('legacy@example.com'));
    }

    public function testReturnsTrueWhenIdentifiersMatch(): void
    {
        $existing = $this->createMock(UserInterface::class);
        $existing->method('getId')->willReturn('0199ddf7b47b72359bc423b847dbde1e');

        $this->repository->expects($this->once())
            ->method('findByEmails')
            ->with(['same@example.com'])
            ->willReturn(new UserCollection([$existing]));

        $request = new Request();
        $request->attributes->set(
            'id',
            '0199ddf7-b47b-7235-9bc4-23b847dbde1e'
        );
        $this->requestStack->push($request);

        $this->assertTrue($this->checker->isUnique('same@example.com'));
    }

    private function mixedCaseEmail(): string
    {
        return sprintf(
            '%s@%s',
            ucfirst(
                strtolower($this->faker->unique()->lexify('????????'))
            ),
            strtolower($this->faker->safeEmailDomain())
        );
    }
}
