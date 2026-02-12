<?php

namespace App\Controller\Front;

use App\Entity\Dons;
use App\Form\DonFrontType;
use App\Repository\DonsRepository;
use App\Repository\CategoriesDonsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/don')]
class DonController extends AbstractController
{
    #[Route('/nouveau', name: 'front_don_nouveau', methods: ['GET', 'POST'])]
    public function nouveau(
        Request $request,
        EntityManagerInterface $entityManager,
        CategoriesDonsRepository $categorieRepo
    ): Response
    {
        $don = new Dons();
        $form = $this->createForm(DonFrontType::class, $don);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($don);
            $entityManager->flush();

            $this->addFlash(
                'success',
                '✅ Votre don a été soumis avec succès ! ' .
                'Il sera examiné par notre équipe et apparaîtra sur le site une fois validé.'
            );

            return $this->redirectToRoute('front_don_mes_dons');
        }

        $categories = $categorieRepo->findAll();

        return $this->render('front/don/nouveau.html.twig', [
            'form' => $form->createView(),
            'categories' => $categories
        ]);
    }

    #[Route('/mes-dons', name: 'front_don_mes_dons', methods: ['GET'])]
    public function mesDons(
        Request $request,
        DonsRepository $donRepository,
        CategoriesDonsRepository $categorieRepo
    ): Response {
        $categorieId = $request->query->getInt('categorie') ?: null;
        $urgence = $request->query->get('urgence') ?: null;
        $search = $request->query->get('q') ?: null;
        $sort = $request->query->get('sort', 'date');
        $direction = $request->query->get('direction', 'DESC');

        $dons = $donRepository->searchForAdmin(
            'tous',
            $categorieId,
            $urgence,
            $search,
            $sort,
            $direction
        );

        $categories = $categorieRepo->findAll();

        return $this->render('front/don/mes_dons.html.twig', [
            'dons' => $dons,
            'categories' => $categories,
            'currentFilters' => [
                'categorie' => $categorieId,
                'urgence' => $urgence,
                'q' => $search,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    /**
     * API de rafraîchissement : retourne le bloc stats + liste en HTML
     * pour mise à jour automatique sans rechargement complet.
     */
    #[Route('/mes-dons/refresh', name: 'front_don_mes_dons_refresh', methods: ['GET'])]
    public function mesDonsRefresh(
        Request $request,
        DonsRepository $donRepository,
        CategoriesDonsRepository $categorieRepo
    ): Response {
        $categorieId = $request->query->getInt('categorie') ?: null;
        $urgence = $request->query->get('urgence') ?: null;
        $search = $request->query->get('q') ?: null;
        $sort = $request->query->get('sort', 'date');
        $direction = $request->query->get('direction', 'DESC');

        $dons = $donRepository->searchForAdmin(
            'tous',
            $categorieId,
            $urgence,
            $search,
            $sort,
            $direction
        );

        return $this->render('front/don/_mes_dons_content.html.twig', [
            'dons' => $dons,
        ]);
    }

    #[Route('/liste', name: 'front_don_liste', methods: ['GET'])]
    public function liste(
        Request $request,
        DonsRepository $donRepository,
        CategoriesDonsRepository $categorieRepo
    ): Response {
        $categorieId = $request->query->getInt('categorie') ?: null;
        $urgence = $request->query->get('urgence') ?: null;
        $search = $request->query->get('q') ?: null;
        $sort = $request->query->get('sort', 'date');
        $direction = $request->query->get('direction', 'DESC');

        $dons = $donRepository->searchForFront(
            $categorieId,
            $urgence,
            $search,
            $sort,
            $direction
        );

        $categories = $categorieRepo->findAll();

        return $this->render('front/don/liste.html.twig', [
            'dons' => $dons,
            'categories' => $categories,
            'currentFilters' => [
                'categorie' => $categorieId,
                'urgence' => $urgence,
                'q' => $search,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    #[Route('/{id}/modifier', name: 'front_don_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Dons $don,
        EntityManagerInterface $entityManager,
        CategoriesDonsRepository $categorieRepo
    ): Response
    {
        $form = $this->createForm(DonFrontType::class, $don);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Si le don était validé, il repasse en attente pour une nouvelle confirmation admin
            if ($don->getStatut() === Dons::STATUT_VALIDE) {
                $don->setStatut(Dons::STATUT_EN_ATTENTE);
            }
            $entityManager->flush();

            if ($don->getStatut() === Dons::STATUT_EN_ATTENTE) {
                $this->addFlash('success', 'Votre don a été modifié. Il sera à nouveau examiné par notre équipe avant de réapparaître sur le site.');
            } else {
                $this->addFlash('success', 'Votre don a été modifié avec succès !');
            }
            return $this->redirectToRoute('front_don_mes_dons');
        }

        $categories = $categorieRepo->findAll();

        return $this->render('front/don/edit.html.twig', [
            'don' => $don,
            'form' => $form->createView(),
            'categories' => $categories,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'front_don_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Dons $don,
        EntityManagerInterface $entityManager
    ): Response
    {
        if ($this->isCsrfTokenValid('delete'.$don->getId(), $request->request->get('_token'))) {
            $entityManager->remove($don);
            $entityManager->flush();
            
            $this->addFlash('success', 'Votre don a été supprimé avec succès.');
        }

        return $this->redirectToRoute('front_don_mes_dons');
    }

    #[Route('/{id}/annuler', name: 'front_don_cancel', methods: ['POST'])]
    public function cancel(
        Request $request,
        Dons $don,
        EntityManagerInterface $entityManager
    ): Response
    {
        // Annuler un don (changer le statut à "annulé" par le donateur)
        if ($don->getStatut() === Dons::STATUT_EN_ATTENTE) {
            if ($this->isCsrfTokenValid('cancel'.$don->getId(), $request->request->get('_token'))) {
                $don->setStatut(Dons::STATUT_ANNULE);
                $entityManager->flush();
                
                $this->addFlash('success', 'Votre don a bien été annulé. Il ne sera pas traité par l\'administration.');
            }
        }

        return $this->redirectToRoute('front_don_mes_dons');
    }
}