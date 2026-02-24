<?php

namespace App\Controller\Admin;

use App\Entity\Disponibilite;
use App\Repository\DisponibiliteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * L'admin peut uniquement CONSULTER les disponibilités.
 * Seul le médecin peut créer, modifier et supprimer ses disponibilités.
 */
#[Route('/admin/disponibilites')]
final class DisponibiliteAdminController extends AbstractController
{
    #[Route('', name: 'admin_disponibilites_index')]
    public function index(DisponibiliteRepository $repo): Response
    {
        $disponibilites = $repo->createQueryBuilder('d')
            ->leftJoin('d.medecin', 'm')->addSelect('m')
            ->orderBy('d.date', 'ASC')
            ->addOrderBy('d.heureDebut', 'ASC')
            ->addOrderBy('d.id', 'ASC')
            ->getQuery()
            ->getResult();
        $statutsLabels = array_flip(Disponibilite::getStatuts());

        return $this->render('admin/disponibilite/index.html.twig', [
            'disponibilites' => $disponibilites,
            'statutsLabels' => $statutsLabels,
        ]);
    }
}
