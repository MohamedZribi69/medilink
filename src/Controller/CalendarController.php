<?php

namespace App\Controller;

use App\Entity\RendezVous;
use App\Service\CalendarService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Export calendrier : liens Google/Outlook et téléchargement ICS.
 */
#[Route('/rendez-vous')]
final class CalendarController extends AbstractController
{
    #[Route('/{id}/calendrier', name: 'calendar_rendezvous', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function calendrier(RendezVous $rendezVous, CalendarService $calendarService): Response
    {
        $this->assertCanAccessRendezVous($rendezVous);

        return $this->render('calendar/rendezvous.html.twig', [
            'rendezVous' => $rendezVous,
            'googleUrl' => $calendarService->getGoogleCalendarUrl($rendezVous),
            'outlookUrl' => $calendarService->getOutlookCalendarUrl($rendezVous),
        ]);
    }

    #[Route('/{id}/calendar.ics', name: 'calendar_rendezvous_ics', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function ics(RendezVous $rendezVous, CalendarService $calendarService): Response
    {
        $this->assertCanAccessRendezVous($rendezVous);

        $content = $calendarService->getIcsContent($rendezVous);
        $filename = 'rendez-vous-' . $rendezVous->getDateHeure()?->format('Y-m-d-Hi') . '.ics';

        $response = new Response($content, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');

        return $response;
    }

    private function assertCanAccessRendezVous(RendezVous $rdv): void
    {
        $user = $this->getUser();
        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }
        if ($rdv->getPatient() === $user) {
            return;
        }
        if ($rdv->getDisponibilite()?->getMedecin() === $user) {
            return;
        }
        throw $this->createAccessDeniedException('Vous n\'avez pas accès à ce rendez-vous.');
    }
}
