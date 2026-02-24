<?php

namespace App\Controller\Admin;

use App\Repository\EvenementRepository;
use App\Repository\ParticipationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class DashboardController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard')]
    public function index(EvenementRepository $evenementRepository, ParticipationRepository $participationRepository): Response
    {
        $evenements = $evenementRepository->findAllOrderByDate();
        $aVenir = $evenementRepository->findUpcoming();

        $stats = [
            'total_events' => count($evenements),
            'a_venir' => count($aVenir),
        ];

        $participationsEnAttente = [];
        foreach ($evenements as $ev) {
            foreach ($ev->getParticipations() as $p) {
                if ($p->getStatut() === 'en_attente') {
                    $participationsEnAttente[] = $p;
                }
            }
        }

        return $this->render('admin/dashboard/index.html.twig', [
            'evenements' => $evenements,
            'a_venir' => $aVenir,
            'stats' => $stats,
            'participations_en_attente' => $participationsEnAttente,
            'events_count' => count($evenements),
        ]);
    }
}