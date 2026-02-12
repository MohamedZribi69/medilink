<?php

namespace App\Controller\Admin;

use App\Entity\Dons;
use App\Form\DonAdminType;
use App\Repository\DonsRepository;
use App\Repository\CategoriesDonsRepository;
use App\Service\DonScoringService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/dons')]
class DonController extends AbstractController
{
    #[Route('/', name: 'admin_don_index', methods: ['GET'])]
    public function index(
        Request $request,
        DonsRepository $donRepository,
        CategoriesDonsRepository $categoriesDonsRepository,
        DonScoringService $donScoringService,
        EntityManagerInterface $entityManager
    ): Response {
        $statut = $request->query->get('statut', 'en_attente');
        $categorieId = $request->query->getInt('categorie') ?: null;
        $urgence = $request->query->get('urgence') ?: null;
        $search = $request->query->get('q') ?: null;
        $sort = $request->query->get('sort', 'date');
        $direction = $request->query->get('direction', 'DESC');
        $autoDone = $request->query->getBoolean('auto_done', false);

        // Auto-analyse et auto-décision pour les dons en attente (sans filtres)
        if ($statut === 'en_attente' && !$autoDone && !$categorieId && !$urgence && !$search) {
            $donsEnAttente = $donRepository->findBy(['statut' => Dons::STATUT_EN_ATTENTE]);
            $changed = false;

            foreach ($donsEnAttente as $don) {
                $resultat = $donScoringService->analyser($don);

                if ($resultat['decision'] === 'valider') {
                    $don->setStatut(Dons::STATUT_VALIDE);
                    $changed = true;
                } elseif ($resultat['decision'] === 'rejeter') {
                    $don->setStatut(Dons::STATUT_REJETE);
                    $changed = true;
                }
            }

            if ($changed) {
                $entityManager->flush();
            }

            // Évite de ré-appliquer en boucle : on se redirige avec auto_done=1
            $params = array_merge($request->query->all(), ['auto_done' => 1]);
            return $this->redirectToRoute('admin_don_index', $params);
        }

        $dons = $donRepository->searchForAdmin(
            $statut,
            $categorieId,
            $urgence,
            $search,
            $sort,
            $direction
        );

        $stats = [
            'total' => $donRepository->count([]),
            'en_attente' => $donRepository->count(['statut' => Dons::STATUT_EN_ATTENTE]),
            'valides' => $donRepository->count(['statut' => Dons::STATUT_VALIDE]),
            'rejetes' => $donRepository->count(['statut' => Dons::STATUT_REJETE]),
        ];

        $categories = $categoriesDonsRepository->findAll();
        $categoriesStats = $donRepository->getValidCountsByCategory();

        return $this->render('admin/don/index.html.twig', [
            'dons' => $dons,
            'statut' => $statut,
            'stats' => $stats,
            'categories' => $categories,
            'categoriesStats' => $categoriesStats,
            'currentFilters' => [
                'categorie' => $categorieId,
                'urgence' => $urgence,
                'q' => $search,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    #[Route('/{id}', name: 'admin_don_show', methods: ['GET'])]
    public function show(Dons $don): Response
    {
        return $this->render('admin/don/show.html.twig', [
            'don' => $don,
        ]);
    }

    #[Route('/{id}/valider', name: 'admin_don_valider', methods: ['POST'])]
    public function valider(Request $request, Dons $don, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('valider'.$don->getId(), $request->request->get('_token'))) {
            $don->setStatut('valide');
            $entityManager->flush();
            
            $this->addFlash('success', 'Le don a été validé avec succès.');
        }

        return $this->redirectToRoute('admin_don_index', ['statut' => 'en_attente']);
    }

    #[Route('/{id}/rejeter', name: 'admin_don_rejeter', methods: ['POST'])]
    public function rejeter(Request $request, Dons $don, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('rejeter'.$don->getId(), $request->request->get('_token'))) {
            $don->setStatut('rejete');
            $entityManager->flush();
            
            $this->addFlash('success', 'Le don a été rejeté.');
        }

        return $this->redirectToRoute('admin_don_index', ['statut' => 'en_attente']);
    }

    #[Route('/{id}/analyser', name: 'admin_don_analyser', methods: ['POST'])]
    public function analyser(
        Request $request,
        Dons $don,
        DonScoringService $donScoringService,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid('analyser'.$don->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin_don_show', ['id' => $don->getId()]);
        }

        $resultat = $donScoringService->analyser($don);

        $don->setScoreAuto($resultat['score']);
        $don->setDecisionAuto($resultat['decision']);

        // Appliquer la décision automatique sur le statut du don
        if ($resultat['decision'] === 'valider') {
            $don->setStatut(Dons::STATUT_VALIDE);
        } elseif ($resultat['decision'] === 'rejeter') {
            $don->setStatut(Dons::STATUT_REJETE);
        }

        $entityManager->flush();

        $this->addFlash(
            'success',
            sprintf(
                'Analyse automatique effectuée (score %d/100) : %s.',
                $resultat['score'],
                $resultat['decisionLabel']
            )
        );

        return $this->redirectToRoute('admin_don_show', ['id' => $don->getId()]);
    }

    #[Route('/{id}/edit', name: 'admin_don_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Dons $don, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DonAdminType::class, $don);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Le don a été modifié avec succès.');

            return $this->redirectToRoute('admin_don_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/don/edit.html.twig', [
            'don' => $don,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_don_delete', methods: ['POST'])]
    public function delete(Request $request, Dons $don, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$don->getId(), $request->request->get('_token'))) {
            $entityManager->remove($don);
            $entityManager->flush();
            
            $this->addFlash('success', 'Le don a été supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_don_index', [], Response::HTTP_SEE_OTHER);
    }
}