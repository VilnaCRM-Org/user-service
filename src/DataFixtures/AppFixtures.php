<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Shared\Application\Transformer\UuidTransformer;
use App\User\Domain\Factory\UserFactoryInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserFactoryInterface $userFactory,
        private readonly UuidTransformer $transformer
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $userData = json_decode(
            file_get_contents('/srv/app/tests/Load/users.json'),
            true
        );

        foreach ($userData['users'] as $userArray) {
            $user = $this->userFactory->create(
                $userArray['email'],
                $userArray['initials'],
                $userArray['password'],
                $this->transformer->transformFromString($userArray['id'])
            );

            $manager->persist($user);
        }

        $manager->flush();
    }
}
