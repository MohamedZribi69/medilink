<?php

namespace App\Controller\Admin;

use App\Repository\OrdonnanceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/ordonnances')]
final class OrdonnanceAdminController extends AbstractController
{
    #[Route('', name: 'admin_ordonnances_index', methods: ['GET'])]
    public function index(OrdonnanceRepository $repo): Response
    {
        $ordonnances = $repo->findAllOrderByDate();
        return $this->render('admin/ordonnance/index.html.twig', [
            'ordonnances' => $ordonnances,
        ]);
    }

    #[Route('/{id}', name: 'admin_ordonnances_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id, OrdonnanceRepository $repo): Response
    {
        $ordonnance = $repo->find($id);
        if (!$ordonnance) {
            throw $this->createNotFoundException('Ordonnance introuvable.');
        }
        return $this->render('admin/ordonnance/show.html.twig', [
            'ordonnance' => $ordonnance,
        ]);
    }
}
