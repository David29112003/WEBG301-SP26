<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegisterController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {

        $session = $request->getSession();

        $errors = $session->get('errors', []);
        $old = $session->get('old', []);

        $session->remove('errors');
        $session->remove('old');

        if ($request->isMethod('POST')) {

            $data = $request->request;
            $old = $data->all();
            $errors = [];

            if (!$this->isCsrfTokenValid('register', $data->get('_token'))) {
                throw $this->createAccessDeniedException();
            }

            // GET DATA
            $email = trim($data->get('email'));
            $name = trim($data->get('name'));
            $password = $data->get('password');
            $confirmPassword = $data->get('confirm_password');
            $phone = trim($data->get('phone'));
            $stageName = trim($data->get('stage_name'));
            $price = $data->get('price');
            $role = $data->get('role') ?: 'ROLE_DJ';
            $adminCode = $data->get('admin_code');

            $adminCodeEnv = $this->getParameter('admin_code');

            // VALIDATION
            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email';
            }

            if (!$name) {
                $errors['name'] = 'Name is required';
            }

            if (!$password) {
                $errors['password'] = 'Password is required';
            }

            if ($password !== $confirmPassword) {
                $errors['confirm_password'] = 'Passwords do not match';
            }

            // PHONE REQUIRED
            if (!$phone) {
                $errors['phone'] = 'Phone is required';
            } else {
                if (!ctype_digit($phone)) {
                    $errors['phone'] = 'Phone must be numbers only';
                } elseif (strlen($phone) < 10 || strlen($phone) > 11) {
                    $errors['phone'] = 'Phone must be 10-11 digits';
                }
            }

            // STAGE NAME REQUIRED
            if (!$stageName) {
                $errors['stage_name'] = 'DJ name is required';
            }

            // PRICE
            if ($price !== null && $price !== '') {
                if (!ctype_digit($price) || (int)$price < 0) {
                    $errors['price'] = 'Invalid price';
                }
            }

            // ADMIN CHECK
            if ($role === 'ROLE_ADMIN') {
                if (!$adminCode) {
                    $errors['admin_code'] = 'Admin code is required';
                } elseif ($adminCode !== $adminCodeEnv) {
                    $errors['admin_code'] = 'Invalid admin code';
                }
            }

            // EMAIL EXISTS
            if ($em->getRepository(User::class)->findOneBy(['email' => $email])) {
                $errors['email'] = 'Email already exists';
            }

            if (!empty($errors)) {
                $session->set('errors', $errors);
                $session->set('old', $old);
                return $this->redirectToRoute('app_register');
            }

            // CREATE USER
            $user = new User();
            $user->setEmail($email);
            $user->setName($name);
            $user->setRoles([$role]);
            $user->setPassword($hasher->hashPassword($user, $password));
            $user->setStageName($stageName);
            $user->setPhone($phone);
            $user->setGenre($data->get('genre'));
            $user->setBio($data->get('bio'));
            $user->setPrice($price ? (int)$price : null);

            // UPLOAD
            $file = $request->files->get('avatar');
            if ($file && $file->isValid()) {
                $filename = uniqid().'.'.$file->guessExtension();
                $file->move(
                    $this->getParameter('kernel.project_dir').'/public/uploads',
                    $filename
                );
                $user->setAvatar($filename);
            }

            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig', [
            'errors' => $errors,
            'old' => $old
        ]);
    }
}