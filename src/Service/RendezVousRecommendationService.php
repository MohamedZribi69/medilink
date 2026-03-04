<?php

namespace App\Service;

use App\Entity\Disponibilite;
use App\Entity\User;

/**
 * Service de recommandation de créneaux de rendez-vous pour un patient.
 * Logique simple de scoring (date proche, plage horaire, etc.).
 */
final class RendezVousRecommendationService
{
    /**
     * @param Disponibilite[] $dispos  Liste de disponibilités libres et futures
     * @return Disponibilite[]         Top N recommandations
     */
    public function recommend(User $patient, array $dispos, int $limit = 3): array
    {
        $patientPrefs = $this->getPatientPreferences($patient);

        $scored = [];
        foreach ($dispos as $d) {
            $scored[] = [
                'dispo' => $d,
                'score' => $this->scoreDispo($d, $patientPrefs),
            ];
        }

        usort($scored, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        return array_map(
            static fn (array $x): Disponibilite => $x['dispo'],
            array_slice($scored, 0, max(0, $limit))
        );
    }

    /**
     * À terme, ces préférences pourront venir de l'entité User (champs dédiés).
     */
    private function getPatientPreferences(User $patient): array
    {
        $preferredTime = $patient->getPreferredTime();
        $maxDaysAhead = $patient->getMaxDaysAhead();

        return [
            'preferredTime' => $preferredTime ?? 'morning', // 'morning' ou 'afternoon'
            'maxDaysAhead' => $maxDaysAhead ?? 14,        // éviter les créneaux trop lointains
        ];
    }

    /**
     * Calcule un score pour une disponibilité donnée.
     */
    private function scoreDispo(Disponibilite $dispo, array $prefs): int
    {
        $score = 0;

        $dateHeure = $dispo->getDateHeureRendezVous();
        if ($dateHeure === null) {
            return $score;
        }

        $now = new \DateTimeImmutable();

        // 1) Plus tôt = mieux (réduit l'attente)
        $diffDays = (int) $now->diff($dateHeure)->format('%r%a');
        if ($diffDays >= 0) {
            $score += max(0, 20 - $diffDays); // demain ~ +19, dans 10 jours ~ +10
        } else {
            // créneau dans le passé, on pénalise fortement
            $score -= 50;
        }

        // 2) Pénaliser si trop loin
        if ($diffDays > $prefs['maxDaysAhead']) {
            $score -= 10;
        }

        // 3) Préférence horaire (matin / après-midi)
        $hour = (int) $dateHeure->format('H');
        $isMorning = $hour < 12;

        if ($prefs['preferredTime'] === 'morning' && $isMorning) {
            $score += 5;
        }
        if ($prefs['preferredTime'] === 'afternoon' && !$isMorning) {
            $score += 5;
        }

        // 4) Bonus horaires "confortables" (plage 9h-17h)
        if ($hour >= 9 && $hour <= 17) {
            $score += 2;
        }

        return $score;
    }
}

