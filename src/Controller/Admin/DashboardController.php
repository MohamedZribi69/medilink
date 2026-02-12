<?php
// src/Controller/Admin/DashboardController.php
namespace App\Controller\Admin;

use App\Repository\DonsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class DashboardController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard')]
    public function index(DonsRepository $donRepository): Response
    {
        $donsEnAttente = $donRepository->findBy(
            ['statut' => 'en_attente'],
            ['dateSoumission' => 'ASC']
        );

        $donsRecentsValides = $donRepository->findBy(
            ['statut' => 'valide'],
            ['dateSoumission' => 'DESC'],
            5
        );

        $stats = [
            'total' => $donRepository->count([]),
            'en_attente' => $donRepository->count(['statut' => 'en_attente']),
            'valides' => $donRepository->count(['statut' => 'valide']),
            'rejetes' => $donRepository->count(['statut' => 'rejete']),
            'urgents' => $donRepository->count(['niveauUrgence' => 'Élevé']),
        ];

        return $this->render('admin/dashboard/index.html.twig', [
            'dons_en_attente' => $donsEnAttente,
            'dons_recents_valides' => $donsRecentsValides,
            'stats' => $stats,
        ]);
    }
}