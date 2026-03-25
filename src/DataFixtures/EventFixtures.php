<?php

namespace App\DataFixtures;

use App\Entity\Event;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class EventFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        $eventsData = [
            [
                'title'       => 'Conférence Intelligence Artificielle',
                'description' => 'Une journée dédiée aux dernières avancées en IA, machine learning et deep learning.',
                'location'    => 'Tunis, Hôtel Africa',
                'seats'       => 150,
                'daysFromNow' => 30,
                'image'       => 'ia-conference.jpg',
            ],
            [
                'title'       => 'Hackathon Web & Mobile',
                'description' => 'Concours de développement de 48h ouvert à tous les étudiants.',
                'location'    => 'ESPRIT, Ariana',
                'seats'       => 80,
                'daysFromNow' => 45,
                'image'       => 'hackathon.jpg',
            ],
            [
                'title'       => 'Workshop Symfony 7',
                'description' => 'Formation pratique sur Symfony 7, API Platform et bonnes pratiques.',
                'location'    => 'En ligne (Zoom)',
                'seats'       => 50,
                'daysFromNow' => 15,
                'image'       => 'symfony-workshop.jpg',
            ],
            [
                'title'       => 'Forum Emploi Tech 2025',
                'description' => 'Rencontre entre entreprises recruteurs et étudiants en informatique.',
                'location'    => 'Campus ISET, Sfax',
                'seats'       => 300,
                'daysFromNow' => 60,
                'image'       => 'forum-emploi.jpg',
            ],
            [
                'title'       => 'Atelier Cybersécurité',
                'description' => 'Découvrez les bases du pentesting et de la protection des données.',
                'location'    => 'Tunis, Lac 1',
                'seats'       => 40,
                'daysFromNow' => 20,
                'image'       => 'cybersec.jpg',
            ],
        ];

        foreach ($eventsData as $index => $data) {
            $event = new Event();
            $event->setTitle($data['title']);
            $event->setDescription($data['description']);
            $event->setLocation($data['location']);
            $event->setSeats($data['seats']);
            $event->setImage($data['image']);
            $event->setDate(
                (new \DateTime())->modify('+' . $data['daysFromNow'] . ' days')
            );

            $manager->persist($event);

            // Référence pour ReservationFixtures
            $this->addReference('event_' . $index, $event);
        }

        $manager->flush();
    }
}