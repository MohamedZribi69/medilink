<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Service\MailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        MailService $mailService
    ): Response {
        $user = new User();

        // valeurs par défaut
        if (method_exists($user, 'setRoles')) {
            $user->setRoles(['ROLE_USER']);
        }

        if (method_exists($user, 'setStatus') && defined(User::class.'::STATUS_ACTIVE')) {
            $user->setStatus(User::STATUS_ACTIVE);
        }

        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $hashedPassword = $hasher->hashPassword(
                $user,
                (string) $form->get('plainPassword')->getData()
            );

            $user->setPassword($hashedPassword);
            $em->persist($user);
            $em->flush();

            $verificationCode = (string) random_int(100000, 999999);
            $fullName = $user->getFullName() ?? $user->getEmail() ?? '';
            $mailService->sendRegistrationCode($user->getEmail() ?? '', $fullName, $verificationCode);

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
