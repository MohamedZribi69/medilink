<?php

namespace App\Controller\Front;

use App\Repository\DonsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'front_home')]
    public function index(DonsRepository $donRepository): Response
    {
        $donsRecents = $donRepository->findBy(
            ['statut' => 'valide'],
            ['dateSoumission' => 'DESC'],
            6
        );

        $stats = [
            'total_valides' => $donRepository->count(['statut' => 'valide']),
            'en_attente' => $donRepository->count(['statut' => 'en_attente']),
        ];

        return $this->render('front/home/index.html.twig', [
            'dons_recents' => $donsRecents,
            'stats' => $stats
        ]);
    }
}