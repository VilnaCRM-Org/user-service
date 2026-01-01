<?php

declare(strict_types=1);

namespace App\Tests\Integration\Shared\Application\Validator\Constraint;

use App\Shared\Application\Validator\Constraint\Initials;
use App\Tests\Integration\IntegrationTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class InitialsConstraintIntegrationTest extends IntegrationTestCase
{
    private ValidatorInterface $validator;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = $this->container->get(ValidatorInterface::class);
    }

    public function testValidInitials(): void
    {
        $violations = $this->validator->validate('ValidInitials', [new Initials()]);

        $this->assertCount(0, $violations);
    }

    public function testInitialsWithSpaces(): void
    {
        $violations = $this->validator->validate('name surname', [new Initials()]);

        $this->assertCount(1, $violations);
        $this->assertEquals(
            'Initials can not consist only of spaces',
            $violations[0]->getMessage()
        );
    }

    public function testInitialsTooLong(): void
    {
        $longInitials = str_repeat('a', 256);
        $violations = $this->validator->validate($longInitials, [new Initials()]);

        $this->assertCount(1, $violations);
        $this->assertStringContainsString('255 characters or less', $violations[0]->getMessage());
    }

    public function testInitialsExactlyMaxLength(): void
    {
        $maxLengthInitials = str_repeat('a', 255);
        $violations = $this->validator->validate($maxLengthInitials, [new Initials()]);

        $this->assertCount(0, $violations);
    }

    public function testInitialsOneCharacterBelowMaxLength(): void
    {
        $nearMaxLengthInitials = str_repeat('a', 254);
        $violations = $this->validator->validate($nearMaxLengthInitials, [new Initials()]);

        $this->assertCount(0, $violations);
    }

    public function testInitialsOneCharacterAboveMaxLength(): void
    {
        $aboveMaxLengthInitials = str_repeat('a', 256);
        $violations = $this->validator->validate($aboveMaxLengthInitials, [new Initials()]);

        $this->assertCount(1, $violations);
        $this->assertStringContainsString('255 characters or less', $violations[0]->getMessage());
    }
}
