<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
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
            return $this->redirectToRoute('app_login');
        }

        if ($event->getSeats() <= $event->getReservations()->count()) {
            $this->addFlash('danger', 'Plus de places disponibles.');
            return $this->redirectToRoute('app_home');
        }

        $reservation = new Reservation();
        $reservation->setCreatedAt(new \DateTime());
        $reservation->setEvent($event);

        if (method_exists($reservation, 'setUser')) {
            $reservation->setUser($user);
        }

        $form = $this->createForm(\App\Form\ReservationFormType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($reservation);
            $em->flush();

            $recipientEmail = $reservation->getEmail();

            // Validate email before sending
            if (!$recipientEmail || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('warning', 'Réservation confirmée mais email invalide.');
                $logger->warning('Reservation email invalid', [
                    'reservation_id' => $reservation->getId(),
                    'email' => $recipientEmail,
                ]);
            } else {
                $email = (new Email())
                    ->from('haninamara08@gmail.com') // your Gmail account
                    ->to($recipientEmail)            
                    ->subject('Confirmation de réservation 🎟️')
                    ->html(
                        $this->renderView('emails/reservation.html.twig', [
                            'name' => $reservation->getName(),
                            'event' => $event,
                        ])
                    );

                try {
                    $mailer->send($email);
                    $this->addFlash('success', 'Réservation confirmée 🎉 Email envoyé !');
                } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
                    $this->addFlash('warning', 'Réservation confirmée mais email non envoyé.');
                    if ($this->getParameter('kernel.environment') === 'dev') {
                        $this->addFlash('danger', 'Erreur email: ' . $e->getMessage());
                    }
                }

            }

            return $this->redirectToRoute('app_home');
        }

        return $this->render('reservation/form.html.twig', [
            'form' => $form->createView(),
            'event' => $event,
        ]);
    }

   #[Route('/test-smtp')]
public function testSmtp(): Response
{
    $host = 'smtp.gmail.com';
    $port = 587;

    $fp = @fsockopen($host, $port, $errno, $errstr, 10);
    if (!$fp) {
        return new Response("Cannot connect to $host:$port. Error $errno - $errstr");
    }
    fclose($fp);
    return new Response("Connection to $host:$port successful!");
}

    // Test email route for browser PHP
    #[Route('/test-email-web')]
    public function testEmailWeb(MailerInterface $mailer, LoggerInterface $logger): Response
    {
        $email = (new Email())
            ->from('haninamara08@gmail.com')
            ->to('hanin8100@gmail.com') // test email
            ->subject('Test email from web')
            ->text('This is a test email sent from browser PHP.');

        try {
            $mailer->send($email);
            $logger->info('Test email sent successfully', ['to' => 'hanin8100@gmail.com']);
            return new Response('Email sent successfully!');
        } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
            $logger->error('Test email failed', ['error' => $e->getMessage()]);
            return new Response('Email failed: ' . $e->getMessage());
        }
    }
}