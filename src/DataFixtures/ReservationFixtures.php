<?php

namespace App\DataFixtures;

use App\Entity\Reservation;
use App\Entity\Event;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class ReservationFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        for ($eventIndex = 0; $eventIndex < 5; $eventIndex++) {

            for ($i = 0; $i < 3; $i++) {

                $reservation = new Reservation();
                $reservation->setName($faker->name());
                $reservation->setEmail($faker->email());
                $reservation->setPhone($faker->phoneNumber());
                $reservation->setCreatedAt(new \DateTime());

                // ✅ CORRECT reference usage
                $event = $this->getReference(
                    'event_' . $eventIndex,
                    Event::class
                );

                $reservation->setEvent($event);

                $manager->persist($reservation);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            EventFixtures::class,
        ];
    }
}