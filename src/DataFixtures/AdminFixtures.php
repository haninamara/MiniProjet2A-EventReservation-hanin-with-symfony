<?php

namespace App\DataFixtures;

use App\Entity\Admin;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $hasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        $admin = new Admin();
        $admin->setUsername('admin');

        // hash password
        $hashedPassword = $this->hasher->hashPassword(
            new class implements \Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface {
                public function getPassword(): ?string { return null; }
            },
            'admin123'
        );

        
        $admin->setPasswordHash(
            password_hash('admin123', PASSWORD_BCRYPT)
        );

        $manager->persist($admin);
        $manager->flush();
    }
}