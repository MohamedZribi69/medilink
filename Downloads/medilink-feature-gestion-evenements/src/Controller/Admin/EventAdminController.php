<?php

namespace App\Controller\Admin;

use App\Entity\Evenement;
use App\Entity\Participation;
use App\Form\Admin\EventType;
use App\Repository\EvenementRepository;
use App\Repository\ParticipationRepository;
use App\Service\EventDescriptionGenerator;
use App\Service\EventNotificationMailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormError;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/events')]
final class EventAdminController extends AbstractController
{
    private const UPLOAD_DIR = 'uploads/events';

    private function handleEventPhoto(Evenement $evenement, $form, string $projectDir, SluggerInterface $slugger): void
    {
        $file = $form->get('photoFile')->getData();
        if ($file) {
            $this->saveEventPhoto($evenement, $file, $projectDir, $slugger);
        }
    }

    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5 Mo
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    /**
     * @return string[] Liste des messages d'erreur pour le fichier (vide si valide)
     */
    private function validateUploadedPhoto(?UploadedFile $file): array
    {
        if ($file === null) {
            return [];
        }
        $errors = [];
        if (!$file->isValid()) {
            $errors[] = 'Erreur lors de l\'envoi du fichier.';
            return $errors;
        }
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            $errors[] = 'Le fichier ne doit pas dépasser 5 Mo.';
        }
        $ext = strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
        if ($ext === '' || !\in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            $errors[] = 'Veuillez envoyer une image (JPEG, JPG, PNG, GIF ou WebP).';
        }
        return $errors;
    }

    private function saveEventPhoto(Evenement $evenement, UploadedFile $file, string $projectDir, SluggerInterface $slugger): void
    {
        $ext = strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
        if ($ext === '' || !\in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            $ext = 'jpg';
        }
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = $slugger->slug($originalName) ?: 'photo';
        $fileName = $safeName . '-' . uniqid('', true) . '.' . $ext;
        $dir = $projectDir . '/public/' . self::UPLOAD_DIR;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        try {
            $file->move($dir, $fileName);
            $evenement->setPhoto($fileName);
        } catch (FileException) {
            // ignore
        }
    }
    #[Route('/api/generate-description', name: 'admin_events_api_generate_description', methods: ['GET', 'POST'])]
    public function generateDescription(Request $request, EventDescriptionGenerator $descriptionGenerator): Response
    {
        $title = $request->request->get('title') ?? $request->query->get('title', '');
        $title = is_string($title) ? trim($title) : '';
        if ($title === '') {
            return $this->json(['description' => '', 'error' => 'Titre manquant'], 400);
        }
        $description = $descriptionGenerator->generateFromTitle($title);
        if ($description === '') {
            return $this->json([
                'description' => '',
                'error' => 'Impossible de générer une description pour le moment (API Hugging Face). Réessayez dans quelques secondes.',
            ], 503);
        }

        return $this->json(['description' => $description]);
    }

    #[Route('', name: 'admin_events_index')]
    public function index(Request $request, EvenementRepository $repo, ParticipationRepository $participationRepo): Response
    {
        $q = (string) $request->query->get('q', '');
        $ordre = $request->query->get('ordre');

        $qb = $repo->createQueryBuilder('e');

        if ($q !== '') {
            $qb->andWhere('LOWER(e.titre) LIKE :q OR LOWER(e.lieu) LIKE :q OR LOWER(e.type) LIKE :q')
               ->setParameter('q', '%' . mb_strtolower($q) . '%');
        }

        $orderDir = ($ordre === 'asc') ? 'ASC' : 'DESC';
        $evenements = $qb->orderBy('e.dateEvenement', $orderDir)->addOrderBy('e.id', $orderDir)->getQuery()->getResult();

        $aVenir = $repo->findUpcoming();
        $participationsEnAttente = 0;
        foreach ($evenements as $ev) {
            foreach ($ev->getParticipations() as $p) {
                if ($p->getStatut() === 'en_attente') {
                    $participationsEnAttente++;
                }
            }
        }

        $stats = [
            'total' => count($evenements),
            'a_venir' => count($aVenir),
            'participations_attente' => $participationsEnAttente,
        ];

        return $this->render('admin/event/index.html.twig', [
            'evenements' => $evenements,
            'filters' => ['q' => $q, 'ordre' => $ordre],
            'stats' => $stats,
            'events_count' => count($evenements),
        ]);
    }

    #[Route('/new', name: 'admin_events_new')]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $evenement = new Evenement();
        $form = $this->createForm(EventType::class, $evenement, ['photo_required' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fileErrors = $this->validateUploadedPhoto($form->get('photoFile')->getData());
            if ($fileErrors !== []) {
                foreach ($fileErrors as $msg) {
                    $form->get('photoFile')->addError(new FormError($msg));
                }
            } else {
                $this->handleEventPhoto($evenement, $form, $this->getParameter('kernel.project_dir'), $slugger);
                $em->persist($evenement);
                $em->flush();
                $this->addFlash('success', 'Événement ajouté.');
                return $this->redirectToRoute('admin_events_index');
            }
        }

        return $this->render('admin/event/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/new-modal', name: 'admin_events_new_modal')]
    public function newModal(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $evenement = new Evenement();
        $form = $this->createForm(EventType::class, $evenement, ['photo_required' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fileErrors = $this->validateUploadedPhoto($form->get('photoFile')->getData());
            if ($fileErrors !== []) {
                foreach ($fileErrors as $msg) {
                    $form->get('photoFile')->addError(new FormError($msg));
                }
            } else {
                $this->handleEventPhoto($evenement, $form, $this->getParameter('kernel.project_dir'), $slugger);
                $em->persist($evenement);
                $em->flush();
                return $this->json(['ok' => true]);
            }
        }

        return $this->render('admin/event/_modal_form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_events_edit', requirements: ['id' => '\d+'])]
    public function edit(Evenement $evenement, Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(EventType::class, $evenement, ['photo_required' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fileErrors = $this->validateUploadedPhoto($form->get('photoFile')->getData());
            if ($fileErrors !== []) {
                foreach ($fileErrors as $msg) {
                    $form->get('photoFile')->addError(new FormError($msg));
                }
            } else {
                $this->handleEventPhoto($evenement, $form, $this->getParameter('kernel.project_dir'), $slugger);
                $em->flush();
                $this->addFlash('success', 'Événement modifié.');
                return $this->redirectToRoute('admin_events_index');
            }
        }

        return $this->render('admin/event/edit.html.twig', [
            'form' => $form->createView(),
            'evenement' => $evenement,
        ]);
    }

    #[Route('/{id}', name: 'admin_events_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        Evenement $evenement,
        EntityManagerInterface $em,
        Request $request,
        ParticipationRepository $participationRepo,
        EventNotificationMailer $eventNotificationMailer
    ): Response {
        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_event_' . $evenement->getId(), $token)) {
            throw $this->createAccessDeniedException();
        }

        $participations = $participationRepo->findByEvenement($evenement);
        $recipientEmails = [];
        foreach ($participations as $p) {
            $user = $p->getUser();
            if ($user !== null && $user->getEmail() !== null && $user->getEmail() !== '') {
                $recipientEmails[] = $user->getEmail();
            }
        }
        $recipientEmails = array_unique($recipientEmails);

        if ($recipientEmails !== []) {
            $eventNotificationMailer->sendEventDeletedNotification($evenement, $recipientEmails);
        }

        $em->remove($evenement);
        $em->flush();
        $this->addFlash('success', 'Événement supprimé.' . ($recipientEmails !== [] ? ' Les participants ont été notifiés par e-mail.' : ''));
        return $this->redirectToRoute('admin_events_index');
    }

    #[Route('/{id}/participants', name: 'admin_events_participants', requirements: ['id' => '\d+'])]
    public function participants(Evenement $evenement, ParticipationRepository $participationRepo): Response
    {
        $participations = $participationRepo->findByEvenement($evenement);

        return $this->render('admin/event/participants.html.twig', [
            'evenement' => $evenement,
            'participations' => $participations,
        ]);
    }

    #[Route('/{id}/participants/{participationId}/accepter', name: 'admin_events_participant_accept', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function acceptParticipant(Evenement $evenement, int $participationId, EntityManagerInterface $em, Request $request): Response
    {
        $participation = $em->getRepository(Participation::class)->find($participationId);
        if (!$participation || $participation->getEvenement() !== $evenement) {
            $this->addFlash('error', 'Participation introuvable.');
            return $this->redirectToRoute('admin_events_participants', ['id' => $evenement->getId()]);
        }
        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('accept_participant_' . $participationId, $token)) {
            throw $this->createAccessDeniedException();
        }
        $participation->setStatut(Participation::STATUT_CONFIRME);
        $em->flush();
        $this->addFlash('success', 'Participant accepté.');
        return $this->redirectToRoute('admin_events_participants', ['id' => $evenement->getId()]);
    }

    #[Route('/{id}/participants/{participationId}/supprimer', name: 'admin_events_participant_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteParticipant(Evenement $evenement, int $participationId, EntityManagerInterface $em, Request $request): Response
    {
        $participation = $em->getRepository(Participation::class)->find($participationId);
        if (!$participation || $participation->getEvenement() !== $evenement) {
            $this->addFlash('error', 'Participation introuvable.');
            return $this->redirectToRoute('admin_events_participants', ['id' => $evenement->getId()]);
        }
        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_participant_' . $participationId, $token)) {
            throw $this->createAccessDeniedException();
        }
        $em->remove($participation);
        $em->flush();
        $this->addFlash('success', 'Participant supprimé de l\'événement.');
        return $this->redirectToRoute('admin_events_participants', ['id' => $evenement->getId()]);
    }
}
