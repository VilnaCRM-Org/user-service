<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use Faker\Factory;
use Faker\Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class IntegrationTestCase extends KernelTestCase
{
    use MailerAssertionsTrait;

    protected Generator $faker;
    protected ContainerInterface $container;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->container = $this->getContainer();

        $this->faker = Factory::create();
    }
}
