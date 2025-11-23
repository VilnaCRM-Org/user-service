<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator;

use App\Shared\Application\Validator\Password;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;

final class PasswordConstraintTest extends UnitTestCase
{
    public function testGetConstraintsReturnsCorrectLengthBoundaries(): void
    {
        $passwordConstraint = new Password();
        $lengthConstraint = $this->findLengthConstraint(
            $this->getConstraintsFromPassword($passwordConstraint)
        );

        $this->assertInstanceOf(Length::class, $lengthConstraint);
        $this->assertSame(8, $lengthConstraint->min);
        $this->assertSame(64, $lengthConstraint->max);
    }

    public function testGetConstraintsReturnsNumberRegexPattern(): void
    {
        $passwordConstraint = new Password();
        $numberRegex = $this->findRegexConstraintByPattern(
            $this->getConstraintsFromPassword($passwordConstraint),
            '/[0-9]/'
        );
        $this->assertInstanceOf(Regex::class, $numberRegex);
        $this->assertSame('/[0-9]/', $numberRegex->pattern);
        $this->assertSame('password.missing.number', $numberRegex->message);
    }

    public function testGetConstraintsReturnsUppercaseRegexPattern(): void
    {
        $passwordConstraint = new Password();
        $uppercaseRegex = $this->findRegexConstraintByPattern(
            $this->getConstraintsFromPassword($passwordConstraint),
            '/[A-Z]/'
        );
        $this->assertInstanceOf(Regex::class, $uppercaseRegex);
        $this->assertSame('/[A-Z]/', $uppercaseRegex->pattern);
        $this->assertSame('password.missing.uppercase', $uppercaseRegex->message);
    }

    public function testMinLengthBoundaryExactly8Characters(): void
    {
        $passwordConstraint = new Password();
        $lengthConstraint = $this->findLengthConstraint(
            $this->getConstraintsFromPassword($passwordConstraint)
        );

        // Testing that min is exactly 8, not 7 or 9
        $this->assertSame(8, $lengthConstraint->min);
        $this->assertNotSame(7, $lengthConstraint->min);
        $this->assertNotSame(9, $lengthConstraint->min);
    }

    public function testMaxLengthBoundaryExactly64Characters(): void
    {
        $passwordConstraint = new Password();
        $lengthConstraint = $this->findLengthConstraint(
            $this->getConstraintsFromPassword($passwordConstraint)
        );

        // Testing that max is exactly 64, not 63 or 65
        $this->assertSame(64, $lengthConstraint->max);
        $this->assertNotSame(63, $lengthConstraint->max);
        $this->assertNotSame(65, $lengthConstraint->max);
    }

    /**
     * @return array<int, Constraint>
     */
    private function getConstraintsFromPassword(Password $password): array
    {
        $reflection = new \ReflectionClass($password);
        $method = $reflection->getMethod('getConstraints');
        $this->makeAccessible($method);

        return $method->invoke($password, []);
    }

    /**
     * @param iterable<Constraint> $constraints
     */
    private function findLengthConstraint(iterable $constraints): ?Length
    {
        return $this->findConstraint(
            $constraints,
            static fn (Constraint $constraint) => $constraint instanceof Length
        );
    }

    /**
     * @param iterable<Constraint> $constraints
     */
    private function findRegexConstraintByPattern(
        iterable $constraints,
        string $pattern
    ): ?Regex {
        return $this->findConstraint(
            $constraints,
            static fn (Constraint $constraint) => $constraint instanceof Regex
                && $constraint->pattern === $pattern
        );
    }

    /**
     * @param iterable<Constraint> $constraints
     */
    private function findConstraint(
        iterable $constraints,
        callable $matcher
    ): ?Constraint {
        foreach ($constraints as $constraint) {
            if ($matcher($constraint)) {
                return $constraint;
            }
        }

        return null;
    }
}
