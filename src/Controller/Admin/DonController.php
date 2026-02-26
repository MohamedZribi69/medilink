<?php

namespace App\Controller\Admin;

use App\Entity\Dons;
use App\Form\DonAdminType;
use App\Repository\DonsRepository;
use App\Repository\CategorieDonRepository;
use App\Service\DonScoringService;
use App\Service\DonTextCheckService;
use App\Service\PdfService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

#[Route('/admin/dons')]
class DonController extends AbstractController
{
    #[Route('/', name: 'admin_don_index', methods: ['GET'])]
    public function index(
        Request $request,
        DonsRepository $donRepository,
        CategorieDonRepository $categorieDonRepository,
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

        $categories = $categorieDonRepository->findAll();
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

    #[Route('/{id}/text-suggestions', name: 'admin_don_text_suggestions', methods: ['GET'])]
    public function textSuggestions(Dons $don, DonTextCheckService $textCheckService): JsonResponse
    {
        $description = $don->getArticleDescription() ?? '';
        $details = $don->getDetailsSupplementaires() ?? '';

        $resultDesc = $textCheckService->getSuggestions($description);
        $resultDetails = $details !== '' ? $textCheckService->getSuggestions($details) : ['hasIssues' => false, 'suggestions' => [], 'correctedText' => ''];

        return new JsonResponse([
            'articleDescription' => $description,
            'detailsSupplementaires' => $details,
            'suggestions' => [
                'articleDescription' => $resultDesc['suggestions'],
                'detailsSupplementaires' => $resultDetails['suggestions'],
            ],
            'correctedArticleDescription' => $resultDesc['correctedText'],
            'correctedDetailsSupplementaires' => $resultDetails['correctedText'],
            'hasIssues' => $resultDesc['hasIssues'] || $resultDetails['hasIssues'],
        ]);
    }

    #[Route('/{id}/apply-correction', name: 'admin_don_apply_correction', methods: ['POST'])]
    public function applyCorrection(Request $request, Dons $don, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('apply_correction' . $don->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin_don_show', ['id' => $don->getId()]);
        }

        $articleDescription = $request->request->get('article_description');
        $detailsSupplementaires = $request->request->get('details_supplementaires');

        if ($articleDescription !== null) {
            $don->setArticleDescription(mb_substr(trim((string) $articleDescription), 0, 255));
        }
        if ($detailsSupplementaires !== null) {
            $don->setDetailsSupplementaires(trim((string) $detailsSupplementaires) ?: null);
        }

        $entityManager->flush();

        $this->addFlash('success', 'Les corrections ont été appliquées au don.');
        return $this->redirectToRoute('admin_don_show', ['id' => $don->getId()]);
    }

    #[Route('/{id}', name: 'admin_don_show', methods: ['GET'])]
    public function show(Dons $don): Response
    {
        return $this->render('admin/don/show.html.twig', [
            'don' => $don,
        ]);
    }

    #[Route('/{id}/recu-pdf', name: 'admin_don_recu_pdf', methods: ['GET'])]
    public function recuPdf(Dons $don, PdfService $pdfService, Environment $twig): Response
    {
        if (!$don->estValide()) {
            $this->addFlash('warning', 'Un reçu PDF ne peut être généré que pour un don validé.');
            return $this->redirectToRoute('admin_don_show', ['id' => $don->getId()]);
        }

        $html = $twig->render('pdf/recu_don.html.twig', ['don' => $don]);
        $pdf = $pdfService->generateFromHtml($html);

        $filename = sprintf('Recu_MediLink_Don_%d.pdf', $don->getId());
        $response = new Response($pdf);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }

    #[Route('/{id}/envoyer-recu-email', name: 'admin_don_envoyer_recu_email', methods: ['POST'])]
    public function envoyerRecuEmail(
        Request $request,
        Dons $don,
        PdfService $pdfService,
        MailerInterface $mailer,
        Environment $twig
    ): Response {
        if (!$this->isCsrfTokenValid('envoyer_recu_email' . $don->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('admin_don_show', ['id' => $don->getId()]);
        }

        if (!$don->estValide()) {
            $this->addFlash('warning', 'Un reçu ne peut être envoyé que pour un don validé.');
            return $this->redirectToRoute('admin_don_show', ['id' => $don->getId()]);
        }

        $donateur = $don->getDonateur();
        if (!$donateur || !$donateur->getEmail()) {
            $this->addFlash('warning', 'Ce don n\'a pas de patient (donateur) associé avec un e-mail. Impossible d\'envoyer le reçu.');
            return $this->redirectToRoute('admin_don_show', ['id' => $don->getId()]);
        }

        try {
            $from = $this->getParameter('mailer_from') ?: 'noreply@medilink.org';
        } catch (\Throwable $e) {
            $from = 'noreply@medilink.org';
        }

        try {
            $html = $twig->render('pdf/recu_don.html.twig', ['don' => $don]);
            $pdf = $pdfService->generateFromHtml($html);
            $filename = sprintf('Recu_MediLink_Don_%d.pdf', $don->getId());

            $email = (new \Symfony\Component\Mime\Email())
                ->from($from)
                ->to($donateur->getEmail())
                ->subject(sprintf('Votre reçu de don MediLink #%d', $don->getId()))
                ->text(sprintf(
                    "Bonjour %s,\n\nVotre don #%d a été validé. Veuillez trouver ci-joint votre reçu officiel MediLink.\n\nMerci pour votre générosité.\n\nL'équipe MediLink",
                    $donateur->getFullName() ?? 'Donateur',
                    $don->getId()
                ))
                ->attach($pdf, $filename, 'application/pdf');

            $mailer->send($email);

            $this->addFlash('success', sprintf(
                'Le reçu a été envoyé par e-mail à %s (expéditeur : %s).',
                $donateur->getEmail(),
                $from
            ));
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Impossible d\'envoyer l\'e-mail : ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_don_show', ['id' => $don->getId()]);
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

        $message = sprintf(
            'Analyse automatique effectuée (score %d/100) : %s.',
            $resultat['score'],
            $resultat['decisionLabel']
        );
        if (!empty($resultat['apiError'])) {
            $message .= ' Détail : ' . $resultat['apiError'];
        }
        $this->addFlash('success', $message);

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