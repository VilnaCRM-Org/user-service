<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator;

use App\Shared\Application\Validator\Initials;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;

final class InitialsConstraintTest extends UnitTestCase
{
    public function testGetConstraintsReturnsCorrectMaxLength(): void
    {
        $initialsConstraint = new Initials();
        $constraints = $this->getConstraintsFromInitials($initialsConstraint);

        $lengthConstraint = $this->findLengthConstraint($constraints);
        $this->assertInstanceOf(Length::class, $lengthConstraint);
        $this->assertSame(255, $lengthConstraint->max);
    }

    public function testGetConstraintsReturnsNoSpacesRegexPattern(): void
    {
        $initialsConstraint = new Initials();
        $constraints = $this->getConstraintsFromInitials($initialsConstraint);

        $regexConstraint = $this->findRegexConstraint($constraints);
        $this->assertInstanceOf(Regex::class, $regexConstraint);
        $this->assertSame('/^\S+$/', $regexConstraint->pattern);
        $this->assertSame('initials.spaces', $regexConstraint->message);
    }

    public function testMaxLengthBoundaryExactly255Characters(): void
    {
        $initialsConstraint = new Initials();
        $constraints = $this->getConstraintsFromInitials($initialsConstraint);

        $lengthConstraint = $this->findLengthConstraint($constraints);

        // Testing that max is exactly 255, not 254 or 256
        $this->assertSame(255, $lengthConstraint->max);
        $this->assertNotSame(254, $lengthConstraint->max);
        $this->assertNotSame(256, $lengthConstraint->max);
    }

    public function testLengthConstraintMessage(): void
    {
        $initialsConstraint = new Initials();
        $constraints = $this->getConstraintsFromInitials($initialsConstraint);

        $lengthConstraint = $this->findLengthConstraint($constraints);
        $this->assertSame('initials.invalid.length', $lengthConstraint->maxMessage);
    }

    private function getConstraintsFromInitials(Initials $initials): array
    {
        $reflection = new \ReflectionClass($initials);
        $method = $reflection->getMethod('getConstraints');
        $method->setAccessible(true);

        return $method->invoke($initials, []);
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

    private function findRegexConstraint(array $constraints): ?Regex
    {
        foreach ($constraints as $constraint) {
            if ($constraint instanceof Regex) {
                return $constraint;
            }
        }

        return null;
    }
}
