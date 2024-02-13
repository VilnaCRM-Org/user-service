<?php

namespace App\Tests\Functional;

use Faker\Factory;
use Faker\Generator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FunctionalTestCase extends KernelTestCase
{
    protected Generator $faker;
    protected ContainerInterface $container;

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();

        $this->container = $kernel->getContainer();

        $this->faker = Factory::create();
    }
}
