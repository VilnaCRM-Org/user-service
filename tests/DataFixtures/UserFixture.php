<?php

namespace App\Tests\DataFixtures;

use App\User\Domain\Entity\User\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixture extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager)
    {
        $faker = Factory::create();

        $user = new User(
            getenv('TEST_USER_ID'),
            $faker->email,
            $faker->name,
            getenv('TEST_USER_PASSWORD')
        );

        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $user->getPassword()
        );
        $user->setPassword($hashedPassword);
        $user->setRoles(['ROLE_USER']);
        $user->setConfirmed(false);

        $manager->persist($user);

        $manager->flush();
    }
}