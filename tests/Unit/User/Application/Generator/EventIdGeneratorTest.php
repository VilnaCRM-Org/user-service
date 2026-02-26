<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Generator;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Generator\EventIdGenerator;
use Symfony\Component\Uid\Factory\UuidFactory;

final class EventIdGeneratorTest extends UnitTestCase
{
    private EventIdGenerator $generator;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new EventIdGenerator(new UuidFactory());
    }

    public function testGenerateReturnsNonEmptyString(): void
    {
        $id = $this->generator->generate();

        $this->assertIsString($id);
        $this->assertNotEmpty($id);
    }

    public function testGenerateReturnsUniqueIdOnEachCall(): void
    {
        $id1 = $this->generator->generate();
        $id2 = $this->generator->generate();

        $this->assertNotSame($id1, $id2);
    }
}
