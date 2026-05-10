<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/dashboard', name: 'admin_dashboard')]
    public function dashboard(EntityManagerInterface $em): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'userCount' => $em->getRepository(User::class)->count([]),
            'eventCount' => $em->getRepository(Event::class)->count([]),
            'djs' => $this->getDjUsers($em)
        ]);
    }

    #[Route('/users', name: 'admin_users')]
    public function users(EntityManagerInterface $em): Response
    {
        return $this->render('admin/users.html.twig', [
            'users' => $em->getRepository(User::class)->findAll()
        ]);
    }

    #[Route('/users/edit/{id}', name: 'admin_user_edit')]
    public function editUser(User $user, Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $email = $request->request->get('email');
            $stageName = $request->request->get('stageName');
            $genre = $request->request->get('genre');
            $price = $request->request->get('price');
            $role = $request->request->get('role');

            if (!$name || !$email) {
                $this->addFlash('error', 'Name and Email are required');
                return $this->redirectToRoute('admin_user_edit', ['id' => $user->getId()]);
            }

            $user->setName($name);
            $user->setEmail($email);
            $user->setStageName($stageName);
            $user->setGenre($genre);
            $user->setPrice((int)$price);

            if ($role) {
                $user->setRoles([$role]);
            }

            $file = $request->files->get('avatar');

            if ($file) {
                if (!$file->isValid()) {
                    $this->addFlash('error', 'Upload failed. File is invalid or too large');
                    return $this->redirectToRoute('admin_user_edit', ['id' => $user->getId()]);
                }

                try {
                    $filename = uniqid() . '.' . $file->guessExtension();
                    $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads';

                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    $file->move($uploadDir, $filename);
                    $user->setAvatar($filename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Upload error: ' . $e->getMessage());
                    return $this->redirectToRoute('admin_user_edit', ['id' => $user->getId()]);
                }
            }

            $em->flush();
            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/user_form.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/user/delete/{id}', name: 'admin_user_delete')]
    public function deleteUsers(User $user, EntityManagerInterface $em): Response
    {
        $em->remove($user);
        $em->flush();

        return $this->redirectToRoute('admin_users');
    }

    #[Route('/events', name: 'admin_events')]
    public function events(EntityManagerInterface $em): Response
    {
        return $this->render('admin/events.html.twig', [
            'events' => $em->getRepository(Event::class)->findAll(),
            'djs' => $this->getDjUsers($em)
        ]);
    }

    #[Route('/events/new', name: 'admin_event_new')]
    public function newEvent(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $event = new Event();
            $event->setTitle($request->request->get('title'));
            $event->setLocation($request->request->get('location'));
            $event->setEventDate(new \DateTime($request->request->get('date')));
            $event->setPrice((int)$request->request->get('price'));
            $event->setStatus('pending');

            $dj = $em->getRepository(User::class)->find($request->request->get('dj_id'));
            $event->setDj($dj);

            $em->persist($event);
            $em->flush();

            return $this->redirectToRoute('admin_events');
        }

        return $this->render('admin/event_form.html.twig', [
            'djs' => $this->getDjUsers($em)
        ]);
    }

    #[Route('/events/edit/{id}', name: 'admin_event_edit')]
    public function editEvent(Event $event, Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $event->setTitle($request->request->get('title'));
            $event->setLocation($request->request->get('location'));
            $event->setEventDate(new \DateTime($request->request->get('date')));
            $event->setPrice((int)$request->request->get('price'));

            $dj = $em->getRepository(User::class)->find($request->request->get('dj_id'));
            if ($dj) {
                $event->setDj($dj);
            }

            $em->flush();

            return $this->redirectToRoute('admin_events');
        }

        return $this->render('admin/event_form.html.twig', [
            'event' => $event,
            'djs' => $this->getDjUsers($em)
        ]);
    }

    #[Route('/events/delete/{id}', name: 'admin_event_delete')]
    public function deleteEvent(Event $event, EntityManagerInterface $em): Response
    {
        $em->remove($event);
        $em->flush();

        return $this->redirectToRoute('admin_events');
    }

    private function getDjUsers(EntityManagerInterface $em): array
    {
        return array_filter(
            $em->getRepository(User::class)->findAll(),
            fn($u) => in_array('ROLE_DJ', $u->getRoles())
        );
    }
}