<?php

namespace App\Tests\DataFixtures;

use App\User\Domain\Entity\User\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class UserFixture extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $faker = Factory::create();

        $user = new User(
            $faker->uuid,
            $faker->email,
            $faker->name,
            $faker->password,
        );

        $user->setRoles(['ROLE_USER']);
        $user->setConfirmed(false);

        $manager->persist($user);

        $manager->flush();
    }
}
