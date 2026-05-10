<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    // =========================
    // ADMIN DASHBOARD
    // =========================
    #[Route('/dashboard', name: 'admin_dashboard')]
    public function dashboard(EntityManagerInterface $em): Response
    {
        $djs = $em->getRepository(User::class)->findByRole('ROLE_DJ');
        $events = $em->getRepository(Event::class)->findAll();

        return $this->render('admin/dashboard.html.twig', [
            'djs' => $djs,
            'events' => $events
        ]);
    }

    // =========================
    // VIEW DJs
    // =========================
    #[Route('/djs', name: 'admin_djs')]
    public function djs(EntityManagerInterface $em): Response
    {
        $djs = $em->getRepository(User::class)->findAll();

        return $this->render('admin/djs.html.twig', [
            'djs' => $djs
        ]);
    }

    // =========================
    // DELETE DJ
    // =========================
    #[Route('/dj/delete/{id}', name: 'admin_dj_delete')]
    public function deleteDj(
        User $user,
        EntityManagerInterface $em
    ): Response {

        $em->remove($user);
        $em->flush();

        return $this->redirectToRoute('admin_djs');
    }

    // =========================
    // EVENTS
    // =========================
    #[Route('/events', name: 'admin_events')]
    public function events(EntityManagerInterface $em): Response
    {
        $events = $em->getRepository(Event::class)->findAll();

        return $this->render('admin/events.html.twig', [
            'events' => $events
        ]);
    }

    // =========================
    // CREATE EVENT
    // =========================
    #[Route('/event/create', name: 'admin_event_create')]
    public function createEvent(
        Request $request,
        EntityManagerInterface $em
    ): Response {

        if ($request->isMethod('POST')) {

            $event = new Event();

            $event->setTitle($request->request->get('title'));
            $event->setLocation($request->request->get('location'));
            $event->setDescription($request->request->get('description'));

            $em->persist($event);
            $em->flush();

            return $this->redirectToRoute('admin_events');
        }

        return $this->render('admin/create_event.html.twig');
    }

    // =========================
    // DELETE EVENT
    // =========================
    #[Route('/event/delete/{id}', name: 'admin_event_delete')]
    public function deleteEvent(
        Event $event,
        EntityManagerInterface $em
    ): Response {

        $em->remove($event);
        $em->flush();

        return $this->redirectToRoute('admin_events');
    }
}