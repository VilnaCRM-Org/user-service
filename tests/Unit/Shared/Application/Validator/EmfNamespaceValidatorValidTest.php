<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Validator;

use App\Shared\Application\Validator\EmfNamespace;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class EmfNamespaceValidatorValidTest extends UnitTestCase
{
    private ValidatorInterface $validator;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = Validation::createValidator();
    }

    public function testAcceptsValidNamespaceWithSlashes(): void
    {
        $violations = $this->validator->validate('MyApp/BusinessMetrics', new EmfNamespace());

        self::assertCount(0, $violations);
    }

    public function testAcceptsValidNamespaceWithDashes(): void
    {
        $violations = $this->validator->validate('My-App-Metrics', new EmfNamespace());

        self::assertCount(0, $violations);
    }

    public function testAcceptsValidNamespaceWithUnderscores(): void
    {
        $violations = $this->validator->validate('My_App_Metrics', new EmfNamespace());

        self::assertCount(0, $violations);
    }

    public function testAcceptsValidNamespaceWithDots(): void
    {
        $violations = $this->validator->validate('com.myapp.metrics', new EmfNamespace());

        self::assertCount(0, $violations);
    }

    public function testAcceptsValidNamespaceWithHash(): void
    {
        $violations = $this->validator->validate('MyApp#Metrics', new EmfNamespace());

        self::assertCount(0, $violations);
    }

    public function testAcceptsValidNamespaceWithColon(): void
    {
        $violations = $this->validator->validate('MyApp:Metrics', new EmfNamespace());

        self::assertCount(0, $violations);
    }

    public function testAcceptsValidNamespaceWithAllAllowedCharacters(): void
    {
        $violations = $this->validator->validate(
            'ABC-123.abc_xyz/test#v1:prod',
            new EmfNamespace()
        );

        self::assertCount(0, $violations);
    }

    public function testAcceptsMaxLengthNamespace(): void
    {
        $namespace = str_repeat('a', 256);
        $violations = $this->validator->validate($namespace, new EmfNamespace());

        self::assertCount(0, $violations);
    }

    public function testAcceptsSingleCharacterNamespace(): void
    {
        $violations = $this->validator->validate('A', new EmfNamespace());

        self::assertCount(0, $violations);
    }
}
