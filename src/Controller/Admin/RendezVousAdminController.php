<?php

namespace App\Controller\Admin;

use App\Entity\RendezVous;
use App\Form\Admin\RendezVousType;
use App\Repository\DisponibiliteRepository;
use App\Repository\RendezVousRepository;
use App\Service\RendezVousService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/rendez-vous')]
final class RendezVousAdminController extends AbstractController
{
    #[Route('', name: 'admin_rendezvous_index')]
    public function index(Request $request, RendezVousRepository $repo, PaginatorInterface $paginator): Response
    {
        $q = (string) $request->query->get('q', '');
        $statut = $request->query->get('statut');
        $ordre = $request->query->get('ordre', 'desc');

        $queryBuilder = $repo->getSearchQueryBuilder($q, $statut, $ordre);
        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            15
        );

        $statutsLabels = array_flip(RendezVous::getStatuts());

        return $this->render('admin/rendezvous/index.html.twig', [
            'pagination' => $pagination,
            'filters' => ['q' => $q, 'statut' => $statut, 'ordre' => $ordre],
            'statuts' => RendezVous::getStatuts(),
            'statutsLabels' => $statutsLabels,
        ]);
    }

    #[Route('/new', name: 'admin_rendezvous_new', methods: ['GET', 'POST'])]
    public function new(Request $request, DisponibiliteRepository $dispoRepo, RendezVousService $rdvService): Response
    {
        $creneauxLibres = $dispoRepo->findLibresAvenirAvecMedecin();

        $rendezVous = new RendezVous();
        $form = $this->createForm(RendezVousType::class, $rendezVous, [
            'edit_mode' => false,
            'creneaux_disponibles' => $creneauxLibres,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
            $errors = [];
            foreach ($form->getErrors(true) as $e) {
                $errors[] = $e->getMessage();
            }
            $this->addFlash('error', 'Corrigez les erreurs : ' . implode(' | ', $errors));
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $dispo = $rendezVous->getDisponibilite();
            if (!$dispo) {
                $this->addFlash('error', 'Veuillez sélectionner une disponibilité dans la liste.');
            } else {
                try {
                    $rdvService->creerRendezVous(
                        $dispo,
                        $rendezVous->getPatient(),
                        $rendezVous->getStatut(),
                        $rendezVous->getMotif()
                    );
                    $this->addFlash('success', 'Rendez-vous créé.');
                    return $this->redirectToRoute('admin_rendezvous_index');
                } catch (\DomainException $e) {
                    $this->addFlash('error', $e->getMessage());
                } catch (\Throwable $e) {
                    $this->addFlash('error', 'Erreur : ' . $e->getMessage());
                }
            }
        }

        return $this->render('admin/rendezvous/new.html.twig', [
            'form' => $form->createView(),
            'creneauxDisponibles' => $creneauxLibres,
        ]);
    }

    #[Route('/{id}', name: 'admin_rendezvous_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(RendezVous $rendezVous): Response
    {
        return $this->render('admin/rendezvous/show.html.twig', [
            'rendezVous' => $rendezVous,
            'statutsLabels' => array_flip(RendezVous::getStatuts()),
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_rendezvous_edit', requirements: ['id' => '\d+'])]
    public function edit(RendezVous $rendezVous, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(RendezVousType::class, $rendezVous, [
            'edit_mode' => true,
            'current_disponibilite' => $rendezVous->getDisponibilite(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Rendez-vous modifié.');
            return $this->redirectToRoute('admin_rendezvous_index');
        }

        return $this->render('admin/rendezvous/edit.html.twig', [
            'form' => $form->createView(),
            'rendezVous' => $rendezVous,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_rendezvous_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(RendezVous $rendezVous, RendezVousService $rdvService, Request $request): Response
    {
        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_rendezvous_' . $rendezVous->getId(), $token)) {
            throw $this->createAccessDeniedException();
        }
        $rdvService->annulerRendezVous($rendezVous);
        $this->addFlash('success', 'Rendez-vous supprimé.');
        return $this->redirectToRoute('admin_rendezvous_index');
    }
}
