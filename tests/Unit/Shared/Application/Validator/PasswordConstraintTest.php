<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator;

use App\Shared\Application\Validator\Password;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;

final class PasswordConstraintTest extends UnitTestCase
{
    public function testGetConstraintsReturnsCorrectLengthBoundaries(): void
    {
        $passwordConstraint = new Password();
        $constraints = $this->getConstraintsFromPassword($passwordConstraint);

        $lengthConstraint = $this->findLengthConstraint($constraints);
        $this->assertInstanceOf(Length::class, $lengthConstraint);
        $this->assertSame(8, $lengthConstraint->min);
        $this->assertSame(64, $lengthConstraint->max);
    }

    public function testGetConstraintsReturnsNumberRegexPattern(): void
    {
        $passwordConstraint = new Password();
        $constraints = $this->getConstraintsFromPassword($passwordConstraint);

        $numberRegex = $this->findRegexConstraintByPattern($constraints, '/[0-9]/');
        $this->assertInstanceOf(Regex::class, $numberRegex);
        $this->assertSame('/[0-9]/', $numberRegex->pattern);
        $this->assertSame('password.missing.number', $numberRegex->message);
    }

    public function testGetConstraintsReturnsUppercaseRegexPattern(): void
    {
        $passwordConstraint = new Password();
        $constraints = $this->getConstraintsFromPassword($passwordConstraint);

        $uppercaseRegex = $this->findRegexConstraintByPattern($constraints, '/[A-Z]/');
        $this->assertInstanceOf(Regex::class, $uppercaseRegex);
        $this->assertSame('/[A-Z]/', $uppercaseRegex->pattern);
        $this->assertSame('password.missing.uppercase', $uppercaseRegex->message);
    }

    public function testMinLengthBoundaryExactly8Characters(): void
    {
        $passwordConstraint = new Password();
        $constraints = $this->getConstraintsFromPassword($passwordConstraint);

        $lengthConstraint = $this->findLengthConstraint($constraints);

        // Testing that min is exactly 8, not 7 or 9
        $this->assertSame(8, $lengthConstraint->min);
        $this->assertNotSame(7, $lengthConstraint->min);
        $this->assertNotSame(9, $lengthConstraint->min);
    }

    public function testMaxLengthBoundaryExactly64Characters(): void
    {
        $passwordConstraint = new Password();
        $constraints = $this->getConstraintsFromPassword($passwordConstraint);

        $lengthConstraint = $this->findLengthConstraint($constraints);

        // Testing that max is exactly 64, not 63 or 65
        $this->assertSame(64, $lengthConstraint->max);
        $this->assertNotSame(63, $lengthConstraint->max);
        $this->assertNotSame(65, $lengthConstraint->max);
    }

    private function getConstraintsFromPassword(Password $password): array
    {
        $reflection = new \ReflectionClass($password);
        $method = $reflection->getMethod('getConstraints');
        $method->setAccessible(true);

        return $method->invoke($password, []);
    }

    private function findLengthConstraint(array $constraints): ?Length
    {
        foreach ($constraints as $constraint) {
            if ($constraint instanceof Length) {
                return $constraint;
            }
        }

        return null;
    }

    private function findRegexConstraintByPattern(array $constraints, string $pattern): ?Regex
    {
        foreach ($constraints as $constraint) {
            if ($constraint instanceof Regex && $constraint->pattern === $pattern) {
                return $constraint;
            }
        }

        return null;
    }
}
