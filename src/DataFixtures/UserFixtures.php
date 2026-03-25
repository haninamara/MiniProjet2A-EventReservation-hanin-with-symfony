<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $usersData = [
            ['username' => 'alice',   'password' => 'alice123'],
            ['username' => 'bob',     'password' => 'bob123'],
            ['username' => 'charlie', 'password' => 'charlie123'],
        ];

        foreach ($usersData as $index => $data) {
            $user = new User();
            $user->setUsername($data['username']);
            $user->setRoles(['ROLE_USER']);

            $user->setPasswordHash(
                password_hash($data['password'], PASSWORD_BCRYPT)
            );

            $manager->persist($user);

            $this->addReference('user_' . $index, $user);
        }

        $manager->flush();
    }
}