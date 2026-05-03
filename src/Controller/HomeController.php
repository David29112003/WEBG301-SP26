<?php

namespace App\Controller;

use App\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $events = [];

        if (!$user) {
            return $this->render('home/index.html.twig', [
                'events' => []
            ]);
        }

        //  ADMIN  can see all event
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $events = $em->getRepository(Event::class)->findAll();
        } 

        else {
            $events = $em->getRepository(Event::class)->findBy([
                'dj' => $user
            ]);
        }

        return $this->render('home/index.html.twig', [
            'events' => $events
        ]);
    }
}