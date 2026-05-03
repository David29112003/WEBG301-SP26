<?php

namespace App\Controller;

use App\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_DJ')]
class DjController extends AbstractController
{
   
   
    #[Route('/dj/dashboard', name: 'dj_dashboard')]
    public function dashboard(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        $events = $em->getRepository(Event::class)->findBy([
            'dj' => $user
        ]);

        return $this->render('dj/dashboard.html.twig', [
            'events' => $events
        ]);
    }

    
    #[Route('/dj/events', name: 'dj_events')]
    public function events(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        $events = $em->getRepository(Event::class)->findBy([
            'dj' => $user
        ]);

        return $this->render('dj/events.html.twig', [
            'events' => $events
        ]);
    }

    
    #[Route('/dj/event/{id}/accept', name: 'dj_event_accept')]
    public function accept(Event $event, EntityManagerInterface $em): Response
    {

        $em->flush();

        return $this->redirectToRoute('app_home');
    }


    #[Route('/dj/event/{id}/reject', name: 'dj_event_reject')]
    public function reject(Event $event, EntityManagerInterface $em): Response
    {

        $em->flush();

        return $this->redirectToRoute('app_home');
    }
}