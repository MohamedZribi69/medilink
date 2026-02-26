<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\MailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: '/forgot-password', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        MailService $mailService
    ): Response {
        $email = (string) $request->request->get('email', '');
        $email = trim(mb_strtolower($email));

        if ($request->isMethod('POST') && $email !== '') {
            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

            if ($user instanceof User) {
                $newPassword = bin2hex(random_bytes(4));
                $hashedPassword = $hasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);

                if (method_exists($user, 'touch')) {
                    $user->touch();
                }

                $em->flush();

                $fullName = $user->getFullName() ?? $user->getEmail() ?? '';
                $mailService->sendPasswordReset($user->getEmail() ?? '', $fullName, $newPassword);

                $this->addFlash('success', 'Un nouveau mot de passe vous a été envoyé par email.');

                return $this->redirectToRoute('app_login');
            }

            $this->addFlash('error', 'Aucun compte trouvé pour cet email.');
        }

        return $this->render('security/forgot_password.html.twig', [
            'last_email' => $email,
        ]);
    }
}
