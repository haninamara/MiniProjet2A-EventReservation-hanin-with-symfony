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
#[Route('/events/{id}/reserve', name: 'app_event_reserve')]
public function reserve(Event $event, Request $request, EntityManagerInterface $em): Response
{
    $user = $this->getUser();

    if (!$user) {
        return $this->redirectToRoute('app_login');
    }

    // Vérifier places
    if ($event->getSeats() <= $event->getReservations()->count()) {
        $this->addFlash('danger', 'Plus de places disponibles.');
        return $this->redirectToRoute('app_home');
    }

    $reservation = new Reservation();
    $reservation->setCreatedAt(new \DateTime());
    $reservation->setEvent($event);

    $form = $this->createForm(\App\Form\ReservationFormType::class, $reservation);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $em->persist($reservation);
        $em->flush();

        $this->addFlash('success', 'Réservation confirmée avec succès 🎉');
        return $this->redirectToRoute('app_home');
    }

    return $this->render('reservation/form.html.twig', [
        'form' => $form->createView(),
        'event' => $event,
    ]);
}
}