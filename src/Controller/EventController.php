<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EventController extends AbstractController
{
    // Route to reserve an event
    #[Route('/events/{id}/reserve', name: 'app_event_reserve')]
    public function reserve(Event $event, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('danger', 'Vous devez être connecté pour réserver.');
            return $this->redirectToRoute('app_login');
        }

        // Check if seats are available
        if ($event->getSeats() <= $event->getReservations()->count()) {
            $this->addFlash('danger', 'Plus de places disponibles pour cet événement.');
            return $this->redirectToRoute('app_home');
        }

        // Create reservation
        $reservation = new Reservation();
        $reservation->setEvent($event)
                    ->setName($user->getUsername())
                    ->setEmail($user->getUsername().'@example.com')
                    ->setPhone('')
                    ->setCreatedAt(new \DateTime());

        $em->persist($reservation);
        $em->flush();

        $this->addFlash('success', 'Réservation effectuée avec succès !');

        return $this->redirectToRoute('app_home');
    }
}