<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Reservation;
use App\Form\ReservationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Psr\Log\LoggerInterface;

class EventController extends AbstractController
{
    #[Route('/events/{id}/reserve', name: 'app_event_reserve')]
    public function reserve(
        Event $event,
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        LoggerInterface $logger
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('warning', 'Vous devez être connecté pour réserver.');
            return $this->redirectToRoute('app_login');
        }

        // 1. Check Availability
        if ($event->getSeats() <= $event->getReservations()->count()) {
            $this->addFlash('danger', 'Plus de places disponibles.');
            return $this->redirectToRoute('app_home');
        }

        $reservation = new Reservation();
        $reservation->setCreatedAt(new \DateTime());
        $reservation->setEvent($event);

        // Pre-fill user if possible
        if (method_exists($reservation, 'setUser')) {
            $reservation->setUser($user);
        }

        $form = $this->createForm(ReservationFormType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($reservation);
            $em->flush();

            $recipientEmail = $reservation->getEmail();

            if (empty($recipientEmail) || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('warning', 'Réservation confirmée, mais impossible d\'envoyer l\'email (adresse invalide).');
                return $this->redirectToRoute('app_home');
            }

            $email = (new Email())
                ->from(new Address('haninamara08@gmail.com', 'Event Reservation Team'))
                ->to($recipientEmail)
                ->subject('Confirmation de réservation : ' . $event->getTitle() . ' 🎟️')
                ->html(
                    $this->renderView('emails/reservation.html.twig', [
                        'name'  => $reservation->getName(),
                        'event' => $event,
                    ])
                );

            try {
                $mailer->send($email);
                $this->addFlash('success', 'Réservation enregistrée ! Un email de confirmation a été envoyé à ' . $recipientEmail);
            } catch (\Exception $e) {
                $logger->error('Mailer Error: ' . $e->getMessage());
                
                if ($this->getParameter('kernel.environment') === 'dev') {
                    $this->addFlash('danger', 'Erreur technique (Email) : ' . $e->getMessage());
                } else {
                    $this->addFlash('warning', 'Réservation confirmée, mais l\'envoi de l\'email a échoué.');
                }
            }

            return $this->redirectToRoute('app_home');
        }

        return $this->render('reservation/form.html.twig', [
            'form' => $form->createView(),
            'event' => $event,
        ]);
    }
}