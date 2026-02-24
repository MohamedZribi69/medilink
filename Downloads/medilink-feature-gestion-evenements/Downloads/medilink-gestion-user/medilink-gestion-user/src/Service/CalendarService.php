<?php

namespace App\Service;

use App\Entity\RendezVous;

/**
 * Génère les liens "Ajouter au calendrier" (Google, Outlook) et le contenu ICS
 * pour un rendez-vous. Aucune API externe requise.
 */
final class CalendarService
{
    public function __construct(
        private string $appName = 'MediLink'
    ) {
    }

    /**
     * URL pour ajouter l'événement à Google Calendar (ouvre le formulaire pré-rempli).
     */
    public function getGoogleCalendarUrl(RendezVous $rdv): string
    {
        $start = $rdv->getDateHeure();
        $end = $this->getEndDateTime($rdv);
        if (!$start || !$end) {
            return '#';
        }

        $title = $this->getEventTitle($rdv);
        $description = $this->getEventDescription($rdv);
        $location = '';

        $params = [
            'action' => 'TEMPLATE',
            'text' => $title,
            'dates' => $this->formatGoogleDates($start, $end),
            'details' => $description,
            'location' => $location,
        ];

        return 'https://calendar.google.com/calendar/render?' . http_build_query($params);
    }

    /**
     * URL pour ajouter l'événement à Outlook / Microsoft 365.
     */
    public function getOutlookCalendarUrl(RendezVous $rdv): string
    {
        $start = $rdv->getDateHeure();
        $end = $this->getEndDateTime($rdv);
        if (!$start || !$end) {
            return '#';
        }

        $title = $this->getEventTitle($rdv);
        $description = $this->getEventDescription($rdv);

        $params = [
            'path' => '/calendar/action/compose',
            'rru' => 'addevent',
            'subject' => $title,
            'body' => $description,
            'startdt' => $start->format('Y-m-d\TH:i:s'),
            'enddt' => $end->format('Y-m-d\TH:i:s'),
        ];

        return 'https://outlook.live.com/calendar/0/action/compose?' . http_build_query($params);
    }

    /**
     * Contenu iCal (.ics) pour téléchargement (compatible tous calendriers).
     */
    public function getIcsContent(RendezVous $rdv): string
    {
        $start = $rdv->getDateHeure();
        $end = $this->getEndDateTime($rdv);
        if (!$start || !$end) {
            return '';
        }

        $uid = 'medilink-rdv-' . $rdv->getId() . '@medilink';
        $title = $this->escapeIcs($this->getEventTitle($rdv));
        $description = $this->escapeIcs($this->getEventDescription($rdv));

        $ics = "BEGIN:VCALENDAR\r\n";
        $ics .= "VERSION:2.0\r\n";
        $ics .= "PRODID:-//" . $this->appName . "//Rendez-vous//FR\r\n";
        $ics .= "CALSCALE:GREGORIAN\r\n";
        $ics .= "METHOD:PUBLISH\r\n";
        $ics .= "BEGIN:VEVENT\r\n";
        $ics .= "UID:" . $uid . "\r\n";
        $ics .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
        $ics .= "DTSTART:" . $start->format('Ymd\THis') . "\r\n";
        $ics .= "DTEND:" . $end->format('Ymd\THis') . "\r\n";
        $ics .= "SUMMARY:" . $title . "\r\n";
        $ics .= "DESCRIPTION:" . $description . "\r\n";
        $ics .= "END:VEVENT\r\n";
        $ics .= "END:VCALENDAR\r\n";

        return $ics;
    }

    private function getEventTitle(RendezVous $rdv): string
    {
        $medecin = $rdv->getDisponibilite()?->getMedecin();
        $medecinName = $medecin ? $medecin->getFullName() : 'Médecin';
        return 'RDV MediLink - ' . $medecinName;
    }

    private function getEventDescription(RendezVous $rdv): string
    {
        $parts = [];
        $medecin = $rdv->getDisponibilite()?->getMedecin();
        if ($medecin) {
            $parts[] = 'Médecin : ' . $medecin->getFullName();
        }
        if ($rdv->getMotif()) {
            $parts[] = 'Motif : ' . $rdv->getMotif();
        }
        $parts[] = 'Statut : ' . ($rdv->getStatut());
        return implode("\n", $parts);
    }

    private function getEndDateTime(RendezVous $rdv): ?\DateTimeInterface
    {
        $dispo = $rdv->getDisponibilite();
        if (!$dispo || !$dispo->getHeureFin()) {
            $start = $rdv->getDateHeure();
            if ($start) {
                $end = \DateTime::createFromInterface($start);
                $end->modify('+30 minutes');
                return $end;
            }
            return null;
        }
        $date = $dispo->getDate();
        $heureFin = $dispo->getHeureFin();
        if (!$date || !$heureFin) {
            return null;
        }
        $d = \DateTime::createFromInterface($date);
        $h = \DateTime::createFromInterface($heureFin);
        $d->setTime((int) $h->format('H'), (int) $h->format('i'), (int) $h->format('s'));
        return $d;
    }

    private function formatGoogleDates(\DateTimeInterface $start, \DateTimeInterface $end): string
    {
        return $start->format('Ymd\THis') . '/' . $end->format('Ymd\THis');
    }

    private function escapeIcs(string $text): string
    {
        return str_replace(["\r\n", "\n", "\r", ',', ';', '\\'], ['\n', '\n', '\n', '\,', '\;', '\\\\'], $text);
    }
}
