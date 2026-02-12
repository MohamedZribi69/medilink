<?php

namespace App\Service;

use App\Entity\Dons;

class DonScoringService
{
    /**
     * Analyse un don et retourne un score (0-100) et une décision automatique.
     *
     * Décision possible :
     *  - valider : score élevé, peu de risques
     *  - rejeter : score très faible
     *  - review  : à laisser à la décision manuelle de l'admin
     */
    public function analyser(Dons $don): array
    {
        $score = 50; // base neutre
        $reasons = [];

        $description = trim(($don->getArticleDescription() ?? '') . ' ' . ($don->getDetailsSupplementaires() ?? ''));
        $descriptionLower = mb_strtolower($description, 'UTF-8');

        // 1) Règles bloquantes immédiates (rejet direct)

        // 1.a Mots interdits
        $forbiddenWords = ['suicide', 'fuck', 'shit', 'merde', 'putain'];
        foreach ($forbiddenWords as $word) {
            if ($word !== '' && str_contains($descriptionLower, $word)) {
                $reasons[] = sprintf("Mot interdit détecté ('%s')", $word);
                return [
                    'score' => 5,
                    'decision' => 'rejeter',
                    'decisionLabel' => 'Rejet automatique (contenu inapproprié)',
                    'reasons' => $reasons,
                ];
            }
        }

        // 1.b Don incohérent avec la catégorie (ex: fauteuil roulant mais description = don en argent)
        $hasMoneyPattern =
            str_contains($description, '€') ||
            str_contains($descriptionLower, ' euro') ||
            str_contains($descriptionLower, 'euros') ||
            preg_match('/\d+\s*€/', $description) === 1;

        $categorieNom = $don->getCategorie() ? mb_strtolower($don->getCategorie()->getNom() ?? '', 'UTF-8') : '';

        // Ici on considère que toutes les catégories actuelles représentent des biens matériels,
        // donc un don en argent est incohérent.
        if ($hasMoneyPattern) {
            $reasons[] = 'Don en argent détecté alors que la plateforme attend des biens matériels (incohérent avec la catégorie).';
            return [
                'score' => 10,
                'decision' => 'rejeter',
                'decisionLabel' => 'Rejet automatique (don en argent / incohérent avec la catégorie)',
                'reasons' => $reasons,
            ];
        }

        // 2) Règles de scoring "souple" (ajustent le score)

        // Urgence
        switch ($don->getNiveauUrgence()) {
            case 'Faible':
                $score += 5;
                $reasons[] = 'Urgence faible (+5)';
                break;
            case 'Moyen':
                // neutre
                break;
            case 'Élevé':
                $score -= 5;
                $reasons[] = 'Urgence élevée (-5)';
                break;
        }

        // État du don
        if ($don->getEtat() === 'Neuf / Non ouvert') {
            $score += 10;
            $reasons[] = 'État neuf / non ouvert (+10)';
        } elseif ($don->getEtat() === 'Bon état') {
            $score += 5;
            $reasons[] = 'Bon état (+5)';
        } elseif ($don->getEtat() === 'À vérifier') {
            $score -= 10;
            $reasons[] = 'État à vérifier (-10)';
        }

        // Quantité : très grande quantité = méfiance
        if ($don->getQuantite() !== null) {
            if ($don->getQuantite() <= 10) {
                $score += 5;
                $reasons[] = 'Quantité raisonnable (+5)';
            } elseif ($don->getQuantite() > 100) {
                $score -= 10;
                $reasons[] = 'Quantité très importante (-10)';
            }
        }

        // Date d'expiration proche
        if ($don->getDateExpiration() instanceof \DateTimeInterface) {
            $now = new \DateTimeImmutable('today');
            $interval = $now->diff($don->getDateExpiration());
            $days = (int) $interval->format('%r%a'); // jours restants

            if ($days < 30) {
                $score -= 20;
                $reasons[] = 'Date d\'expiration proche (< 30 jours) (-20)';
            } elseif ($days < 90) {
                $score -= 5;
                $reasons[] = 'Date d\'expiration dans moins de 3 mois (-5)';
            } else {
                $score += 5;
                $reasons[] = 'Bonne marge avant expiration (+5)';
            }
        }

        // Clamp score 0-100
        $score = max(0, min(100, $score));

        // Décision automatique selon le score
        if ($score >= 75) {
            $decision = 'valider';
            $decisionLabel = 'Valider automatiquement';
        } elseif ($score <= 40) {
            $decision = 'rejeter';
            $decisionLabel = 'Rejet automatique recommandé';
        } else {
            $decision = 'review';
            $decisionLabel = 'Nécessite une revue manuelle';
        }

        return [
            'score' => $score,
            'decision' => $decision,
            'decisionLabel' => $decisionLabel,
            'reasons' => $reasons,
        ];
    }
}

