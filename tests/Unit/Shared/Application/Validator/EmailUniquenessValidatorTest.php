<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator;

use App\Shared\Application\Provider\Http\RouteIdentifierProvider;
use App\Shared\Application\Validator\EmailUniquenessValidator;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Service\EmailNormalizer;
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
            new RouteIdentifierProvider($this->requestStack),
            new EmailNormalizer()
        );
    }

    public function testReturnsTrueWhenUserIsNotFound(): void
    {
        $email = $this->faker->unique()->safeEmail();

        $this->expectExactLookups([$email], [null]);
        $this->expectCaseInsensitiveLookup($email, []);

        $this->assertTrue($this->checker->isUnique(sprintf('  %s  ', $email)));
    }

    public function testReturnsFalseWhenIdentifierIsMissing(): void
    {
        $email = $this->faker->unique()->safeEmail();
        $existing = $this->createMock(UserInterface::class);
        $existing->method('getId')->willReturn('identifier');

        $this->expectExactLookups([$email], [$existing]);
        $this->expectNoCaseInsensitiveLookup();

        $this->requestStack->push(new Request());

        $this->assertFalse($this->checker->isUnique($email));
    }

    public function testNormalizesEmailBeforeLookup(): void
    {
        $email = $this->faker->unique()->safeEmail();
        $submittedEmail = sprintf('  %s  ', strtoupper($email));
        $trimmedEmail = trim($submittedEmail);
        $normalizedEmail = mb_strtolower($trimmedEmail, 'UTF-8');

        $existing = $this->createMock(UserInterface::class);
        $existing->method('getId')->willReturn($this->faker->uuid());

        $this->expectExactLookups(
            [$normalizedEmail, $trimmedEmail],
            [$existing, null]
        );
        $this->expectNoCaseInsensitiveLookup();

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

        $this->expectExactLookups(
            [$normalizedEmail, $trimmedEmail],
            [$existing, null]
        );
        $this->expectNoCaseInsensitiveLookup();

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

        $this->expectExactLookups($expectedLookups, [null, $existing]);
        $this->expectNoCaseInsensitiveLookup();
        $this->requestStack->push(new Request());

        $this->assertFalse($this->checker->isUnique($submittedEmail));
    }

    public function testLowercaseSubmissionCanMatchLegacyMixedCaseUser(): void
    {
        $email = mb_strtolower($this->faker->unique()->safeEmail(), 'UTF-8');
        $existing = $this->createMock(UserInterface::class);
        $existing->method('getId')->willReturn($this->faker->uuid());

        $this->expectExactLookups([$email], [null]);
        $this->expectCaseInsensitiveLookup($email, [$existing]);

        $this->requestStack->push(new Request());

        $this->assertFalse($this->checker->isUnique($email));
    }

    public function testReturnsTrueWhenIdentifiersMatch(): void
    {
        $email = $this->faker->unique()->safeEmail();
        $existing = $this->createMock(UserInterface::class);
        $existing->method('getId')->willReturn('0199ddf7b47b72359bc423b847dbde1e');

        $this->expectExactLookups([$email], [$existing]);
        $this->expectCaseInsensitiveLookup($email, [$existing]);

        $request = new Request();
        $request->attributes->set(
            'id',
            '0199ddf7-b47b-7235-9bc4-23b847dbde1e'
        );
        $this->requestStack->push($request);

        $this->assertTrue($this->checker->isUnique($email));
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

    /**
     * @param list<string> $emails
     * @param list<UserInterface|null> $results
     */
    private function expectExactLookups(array $emails, array $results): void
    {
        if (count($emails) !== 1) {
            $this->expectBatchExactLookup($emails, $results);

            return;
        }

        $this->expectSingleExactLookup($emails, $results);
    }

    /**
     * @param list<string> $emails
     * @param list<UserInterface|null> $results
     */
    private function expectBatchExactLookup(array $emails, array $results): void
    {
        $this->repository->expects($this->once())
            ->method('findByEmails')
            ->with($emails)
            ->willReturn(new UserCollection($this->usersFromResults($results)));
    }

    /**
     * @param list<string> $emails
     * @param list<UserInterface|null> $results
     */
    private function expectSingleExactLookup(array $emails, array $results): void
    {
        $lookups = [];

        $this->repository->expects($this->exactly(count($emails)))
            ->method('findByEmail')
            ->willReturnCallback(
                static function (string $email) use (
                    $emails,
                    $results,
                    &$lookups
                ): ?UserInterface {
                    $index = count($lookups);
                    self::assertSame($emails[$index], $email);
                    $lookups[] = $email;

                    return $results[$index];
                }
            );
    }

    /**
     * @param list<UserInterface|null> $results
     *
     * @return list<UserInterface>
     */
    private function usersFromResults(array $results): array
    {
        $users = [];

        foreach ($results as $result) {
            if ($result !== null) {
                $users[] = $result;
            }
        }

        return $users;
    }

    /**
     * @param list<UserInterface> $users
     */
    private function expectCaseInsensitiveLookup(string $email, array $users): void
    {
        $this->repository->expects($this->once())
            ->method('findByEmailCaseInsensitive')
            ->with($email)
            ->willReturn(new UserCollection($users));
    }

    private function expectNoCaseInsensitiveLookup(): void
    {
        $this->repository->expects($this->never())
            ->method('findByEmailCaseInsensitive');
    }
}
