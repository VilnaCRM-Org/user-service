<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Factory;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Factory\EventIdFactory;
use Symfony\Component\Uid\Factory\UuidFactory;

final class EventIdFactoryTest extends UnitTestCase
{
    private EventIdFactory $generator;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new EventIdFactory(new UuidFactory());
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
