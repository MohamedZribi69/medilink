<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): RedirectResponse
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin_users_index');
        }
        return $this->redirectToRoute('app_events_index');
    }

    #[Route('/home', name: 'app_home_legacy')]
    public function homeLegacy(): RedirectResponse
    {
        return $this->redirectToRoute('app_home', [], 301);
    }
}
