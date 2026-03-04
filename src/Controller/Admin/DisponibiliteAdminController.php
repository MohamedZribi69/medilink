<?php

namespace App\Controller\Admin;

use App\Entity\Disponibilite;
use App\Repository\DisponibiliteRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
    public function index(Request $request, DisponibiliteRepository $repo, PaginatorInterface $paginator): Response
    {
        $queryBuilder = $repo->createQueryBuilder('d')
            ->leftJoin('d.medecin', 'm')->addSelect('m')
            ->orderBy('d.date', 'ASC')
            ->addOrderBy('d.heureDebut', 'ASC')
            ->addOrderBy('d.id', 'ASC');

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            15
        );

        $statutsLabels = array_flip(Disponibilite::getStatuts());

        return $this->render('admin/disponibilite/index.html.twig', [
            'pagination' => $pagination,
            'statutsLabels' => $statutsLabels,
        ]);
    }
}
