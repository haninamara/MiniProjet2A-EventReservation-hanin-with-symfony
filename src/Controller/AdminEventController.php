<?php

namespace App\Controller;

use App\Entity\Event;
use App\Form\EventType;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/admin/events')]
class AdminEventController extends AbstractController
{
    #[Route('/', name: 'admin_event_index')]
    public function index(EventRepository $eventRepository): Response
    {
        return $this->render('admin/event/index.html.twig', [
            'events' => $eventRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_event_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $event = new Event();
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // 📌 Upload image
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $newFilename = uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('uploads_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                }

                $event->setImage($newFilename);
            }

            $em->persist($event);
            $em->flush();

            $this->addFlash('success', 'Événement créé avec succès!');
            return $this->redirectToRoute('admin_event_index');
        }

        return $this->render('admin/event/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_event_edit')]
    public function edit(Event $event, Request $request, EntityManagerInterface $em): Response
    {
        $oldImage = $event->getImage();

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // 📌 Upload nouvelle image
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $newFilename = uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('uploads_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                }

                // 🔥 Supprimer ancienne image
                if ($oldImage) {
                    $oldPath = $this->getParameter('uploads_directory').'/'.$oldImage;
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }

                $event->setImage($newFilename);
            }

            $em->flush();

            $this->addFlash('success', 'Événement mis à jour!');
            return $this->redirectToRoute('admin_event_index');
        }

        return $this->render('admin/event/edit.html.twig', [
            'form' => $form->createView(),
            'event' => $event,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_event_delete', methods: ['POST'])]
    public function delete(Event $event, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$event->getId(), $request->request->get('_token'))) {

            // 🔥 Supprimer image du serveur
            if ($event->getImage()) {
                $imagePath = $this->getParameter('uploads_directory').'/'.$event->getImage();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            $em->remove($event);
            $em->flush();

            $this->addFlash('success', 'Événement supprimé!');
        }

        return $this->redirectToRoute('admin_event_index');
    }

    #[Route('/{id}/reservations', name: 'admin_event_reservations')]
    public function reservations(Event $event): Response
    {
        return $this->render('admin/event/reservations.html.twig', [
            'event' => $event,
            'reservations' => $event->getReservations(),
        ]);
    }
}