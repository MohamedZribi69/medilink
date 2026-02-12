<?php

namespace App\Controller\Medecin;

use App\Entity\Disponibilite;
use App\Entity\RendezVous;
use App\Form\Medecin\DisponibiliteMedecinType;
use App\Repository\DisponibiliteRepository;
use App\Repository\RendezVousRepository;
use App\Service\DisponibiliteService;
use App\Service\RendezVousService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/medecin')]
#[IsGranted('ROLE_MEDECIN')]
final class MedecinController extends AbstractController
{
    #[Route('', name: 'medecin_index')]
    public function index(Request $request, DisponibiliteRepository $repo, RendezVousRepository $rdvRepo): Response
    {
        $medecin = $this->getUser();
        $q = (string) $request->query->get('q', '');
        $statutRdv = $request->query->get('statut_rdv');
        $ordreRdv = $request->query->get('ordre_rdv', 'desc');
        $statutDispo = $request->query->get('statut_dispo');
        $ordreDispo = $request->query->get('ordre_dispo', 'asc');

        $mesRendezVous = $rdvRepo->searchByMedecin($medecin, $q, $statutRdv, $ordreRdv);
        $disponibilites = $repo->searchByMedecin($medecin, $statutDispo, $ordreDispo);
        $statutsLabels = array_flip(Disponibilite::getStatuts());
        $statutsRdvLabels = array_flip(RendezVous::getStatuts());

        return $this->render('medecin/index.html.twig', [
            'disponibilites' => $disponibilites,
            'mesRendezVous' => $mesRendezVous,
            'statutsLabels' => $statutsLabels,
            'statutsRdvLabels' => $statutsRdvLabels,
            'filters' => [
                'q' => $q,
                'statut_rdv' => $statutRdv,
                'ordre_rdv' => $ordreRdv,
                'statut_dispo' => $statutDispo,
                'ordre_dispo' => $ordreDispo,
            ],
            'statutsRdv' => RendezVous::getStatuts(),
            'statutsDispo' => Disponibilite::getStatuts(),
        ]);
    }

    #[Route('/rendez-vous/{id}/confirmer', name: 'medecin_rendezvous_confirmer', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function confirmerRendezVous(RendezVous $rendezVous, RendezVousService $rdvService, Request $request): Response
    {
        if (!$rdvService->appartientAuMedecin($rendezVous, $this->getUser())) {
            throw $this->createAccessDeniedException();
        }
        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('confirmer_rdv_' . $rendezVous->getId(), $token)) {
            throw $this->createAccessDeniedException();
        }
        $rdvService->confirmerRendezVous($rendezVous);
        $this->addFlash('success', 'Rendez-vous confirmé.');
        return $this->redirectToRoute('medecin_index');
    }

    #[Route('/rendez-vous/{id}/terminer', name: 'medecin_rendezvous_terminer', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function terminerRendezVous(RendezVous $rendezVous, RendezVousService $rdvService, Request $request): Response
    {
        if (!$rdvService->appartientAuMedecin($rendezVous, $this->getUser())) {
            throw $this->createAccessDeniedException();
        }
        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('terminer_rdv_' . $rendezVous->getId(), $token)) {
            throw $this->createAccessDeniedException();
        }
        $rdvService->marquerTermine($rendezVous);
        $this->addFlash('success', 'Rendez-vous marqué comme terminé.');
        return $this->redirectToRoute('medecin_index');
    }

    #[Route('/rendez-vous/{id}/annuler', name: 'medecin_rendezvous_annuler', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function annulerRendezVous(RendezVous $rendezVous, RendezVousService $rdvService, Request $request): Response
    {
        if (!$rdvService->appartientAuMedecin($rendezVous, $this->getUser())) {
            throw $this->createAccessDeniedException();
        }
        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('annuler_rdv_' . $rendezVous->getId(), $token)) {
            throw $this->createAccessDeniedException();
        }
        $rdvService->annulerRendezVous($rendezVous);
        $this->addFlash('success', 'Rendez-vous annulé. La disponibilité est à nouveau libre.');
        return $this->redirectToRoute('medecin_index');
    }

    #[Route('/creneaux/new', name: 'medecin_creneaux_new')]
    public function newCreneau(Request $request, DisponibiliteService $dispoService): Response
    {
        $disponibilite = new Disponibilite();
        $disponibilite->setMedecin($this->getUser());

        $form = $this->createForm(DisponibiliteMedecinType::class, $disponibilite);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $dispoService->creerDisponibilite($disponibilite);
                $this->addFlash('success', 'Disponibilité ajoutée.');
                return $this->redirectToRoute('medecin_index');
            } catch (\DomainException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('medecin/creneau_new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/creneaux/{id}/edit', name: 'medecin_creneaux_edit')]
    public function editCreneau(Disponibilite $disponibilite, Request $request, DisponibiliteService $dispoService): Response
    {
        if (!$dispoService->appartientAuMedecin($disponibilite, $this->getUser())) {
            throw $this->createAccessDeniedException();
        }
        if (!$dispoService->peutModifier($disponibilite)) {
            $this->addFlash('error', 'Impossible de modifier une disponibilité réservée.');
            return $this->redirectToRoute('medecin_index');
        }

        $form = $this->createForm(DisponibiliteMedecinType::class, $disponibilite);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $dispoService->modifierDisponibilite($disponibilite);
                $this->addFlash('success', 'Disponibilité modifiée.');
                return $this->redirectToRoute('medecin_index');
            } catch (\DomainException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('medecin/creneau_edit.html.twig', [
            'form' => $form->createView(),
            'disponibilite' => $disponibilite,
        ]);
    }

    #[Route('/creneaux/{id}', name: 'medecin_creneaux_delete', methods: ['POST'])]
    public function deleteCreneau(Disponibilite $disponibilite, DisponibiliteService $dispoService, Request $request): Response
    {
        if (!$dispoService->appartientAuMedecin($disponibilite, $this->getUser())) {
            throw $this->createAccessDeniedException();
        }
        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_creneau_' . $disponibilite->getId(), $token)) {
            throw $this->createAccessDeniedException();
        }
        try {
            $dispoService->supprimerDisponibilite($disponibilite);
            $this->addFlash('success', 'Disponibilité supprimée.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }
        return $this->redirectToRoute('medecin_index');
    }
}
