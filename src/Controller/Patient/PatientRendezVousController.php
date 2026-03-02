<?php

namespace App\Controller\Patient;

use App\Entity\Disponibilite;
use App\Entity\RendezVous;
use App\Repository\DisponibiliteRepository;
use App\Repository\RendezVousRepository;
use App\Service\RendezVousService;
use App\Service\RendezVousRecommendationService;
use App\Form\Patient\PatientPreferencesType;
use App\Form\Patient\ReviewType;
use App\Entity\Review;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/patient')]
#[IsGranted('ROLE_USER')]
final class PatientRendezVousController extends AbstractController
{
    #[Route('/rendez-vous', name: 'patient_rendezvous_index')]
    public function index(
        Request $request,
        DisponibiliteRepository $dispoRepo,
        RendezVousRepository $rvRepo,
        RendezVousRecommendationService $recoService,
        PaginatorInterface $paginator
    ): Response
    {
        $patient = $this->getUser();
        $disponibilites = $dispoRepo->findAvailableForPatient();

        $recommendedDispos = [];
        if ($patient instanceof \App\Entity\User && !empty($disponibilites)) {
            $recommendedDispos = $recoService->recommend($patient, $disponibilites, 3);
        }

        $qbRdv = $rvRepo->getFindByPatientQueryBuilder($patient);
        $paginationRdv = $paginator->paginate($qbRdv, $request->query->getInt('page', 1), 10);

        $from = (new \DateTimeImmutable())->modify('-7 days');
        $hotMedecinIds = $rvRepo->findHotMedecinIds($from, 10);

        $statutsLabels = array_flip(RendezVous::getStatuts());

        return $this->render('patient/rendezvous_index.html.twig', [
            'disponibilites' => $disponibilites,
            'recommendedDispos' => $recommendedDispos,
            'paginationRdv' => $paginationRdv,
            'statutsLabels' => $statutsLabels,
            'hotMedecinIds' => $hotMedecinIds,
        ]);
    }

    #[Route('/rendez-vous/reserver/{id}', name: 'patient_rendezvous_reserver', methods: ['POST'])]
    public function reserver(Disponibilite $disponibilite, Request $request, RendezVousService $rdvService): Response
    {
        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('reserver_' . $disponibilite->getId(), $token)) {
            throw $this->createAccessDeniedException();
        }

        if ($disponibilite->getMedecin() === null) {
            $this->addFlash('error', 'Disponibilité invalide.');
            return $this->redirectToRoute('patient_rendezvous_index');
        }

        try {
            $rdvService->creerRendezVous($disponibilite, $this->getUser());
            $this->addFlash('success', 'Rendez-vous réservé avec succès !');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('patient_rendezvous_index');
    }

    #[Route('/rendez-vous/{id}/review', name: 'patient_rendezvous_review')]
    public function review(
        RendezVous $rendezVous,
        Request $request,
        ReviewRepository $reviewRepo,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        if ($rendezVous->getPatient() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $now = new \DateTimeImmutable();
        if ($rendezVous->getDateHeure() > $now) {
            $this->addFlash('error', 'Vous ne pouvez noter le médecin qu\'après le rendez-vous.');
            return $this->redirectToRoute('patient_rendezvous_index');
        }

        if (!in_array($rendezVous->getStatut(), [RendezVous::STATUT_CONFIRME, RendezVous::STATUT_TERMINE], true)) {
            $this->addFlash('error', 'Vous ne pouvez noter que les rendez-vous confirmés ou terminés.');
            return $this->redirectToRoute('patient_rendezvous_index');
        }

        if ($reviewRepo->findOneBy(['rendezVous' => $rendezVous]) !== null) {
            $this->addFlash('error', 'Vous avez déjà noté ce rendez-vous.');
            return $this->redirectToRoute('patient_rendezvous_index');
        }

        $disponibilite = $rendezVous->getDisponibilite();
        $medecin = $disponibilite?->getMedecin();
        if ($medecin === null) {
            $this->addFlash('error', 'Impossible de déterminer le médecin pour ce rendez-vous.');
            return $this->redirectToRoute('patient_rendezvous_index');
        }

        $review = new Review();
        $review->setPatient($user);
        $review->setMedecin($medecin);
        $review->setRendezVous($rendezVous);

        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($review);
            $em->flush();

            $this->addFlash('success', 'Merci pour votre avis sur ce médecin.');
            return $this->redirectToRoute('patient_rendezvous_index');
        }

        return $this->render('patient/review.html.twig', [
            'form' => $form->createView(),
            'rendezVous' => $rendezVous,
            'medecin' => $medecin,
        ]);
    }

    #[Route('/preferences', name: 'patient_preferences')]
    public function preferences(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(PatientPreferencesType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Vos préférences de rendez-vous ont été mises à jour.');

            return $this->redirectToRoute('patient_rendezvous_index');
        }

        return $this->render('patient/preferences.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
