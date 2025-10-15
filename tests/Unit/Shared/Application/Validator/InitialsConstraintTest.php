<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator;

use App\Shared\Application\Validator\Initials;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Type;

final class InitialsConstraintTest extends UnitTestCase
{
    public function testGetConstraintsReturnsCorrectMaxLength(): void
    {
        $initialsConstraint = new Initials();
        $lengthConstraint = $this->findLengthConstraint(
            $this->getConstraintsFromInitials($initialsConstraint)
        );
        $this->assertInstanceOf(Length::class, $lengthConstraint);
        $this->assertSame(255, $lengthConstraint->max);
    }

    public function testGetConstraintsReturnsNoSpacesRegexPattern(): void
    {
        $initialsConstraint = new Initials();
        $regexConstraint = $this->findRegexConstraint(
            $this->getConstraintsFromInitials($initialsConstraint)
        );
        $this->assertInstanceOf(Regex::class, $regexConstraint);
        $this->assertSame('/^\S+$/', $regexConstraint->pattern);
        $this->assertSame('initials.spaces', $regexConstraint->message);
    }

    public function testMaxLengthBoundaryExactly255Characters(): void
    {
        $initialsConstraint = new Initials();
        $lengthConstraint = $this->findLengthConstraint(
            $this->getConstraintsFromInitials($initialsConstraint)
        );

        // Testing that max is exactly 255, not 254 or 256
        $this->assertSame(255, $lengthConstraint->max);
        $this->assertNotSame(254, $lengthConstraint->max);
        $this->assertNotSame(256, $lengthConstraint->max);
    }

    public function testLengthConstraintMessage(): void
    {
        $initialsConstraint = new Initials();
        $lengthConstraint = $this->findLengthConstraint(
            $this->getConstraintsFromInitials($initialsConstraint)
        );
        $this->assertSame('initials.invalid.length', $lengthConstraint->maxMessage);
    }

    public function testTypeConstraintRequiresString(): void
    {
        $initialsConstraint = new Initials();
        $typeConstraint = $this->findTypeConstraint(
            $this->getConstraintsFromInitials($initialsConstraint)
        );

        $this->assertInstanceOf(Type::class, $typeConstraint);
        $this->assertSame('string', $typeConstraint->type);
        $this->assertSame('initials.invalid.type', $typeConstraint->message);
    }

    /**
     * @return array<int, Constraint>
     */
    private function getConstraintsFromInitials(Initials $initials): array
    {
        $reflection = new \ReflectionClass($initials);
        $method = $reflection->getMethod('getConstraints');
        $method->setAccessible(true);

        return $method->invoke($initials, []);
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
    private function findRegexConstraint(iterable $constraints): ?Regex
    {
        return $this->findConstraint(
            $constraints,
            static fn (Constraint $constraint) => $constraint instanceof Regex
        );
    }

    /**
     * @param iterable<Constraint> $constraints
     */
    private function findTypeConstraint(iterable $constraints): ?Type
    {
        return $this->findConstraint(
            $constraints,
            static fn (Constraint $constraint) => $constraint instanceof Type
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
