<?php

namespace App\Service;

use App\Entity\Dons;
use Psr\Log\LoggerInterface;

/**
 * Analyse / scoring des dons par IA (OpenAI) ou fallback neutre.
 * Évalue la pertinence et la qualité du don pour proposer une décision (valider / rejeter / en_attente).
 */
class DonScoringService
{
    private const OPENAI_URL = 'https://api.openai.com/v1/chat/completions';
    private const MAX_TOKENS = 200;

    /** Dernière erreur rencontrée (pour affichage en dev). */
    private ?string $lastError = null;

    public function __construct(
        private readonly ?string $openaiApiKey = null,
        private readonly ?LoggerInterface $logger = null
    ) {
    }

    /**
     * @return array{score: int, decision: string, decisionLabel: string, apiError?: string}
     */
    public function analyser(Dons $don): array
    {
        $this->lastError = null;
        if ($this->openaiApiKey !== null && $this->openaiApiKey !== '') {
            $result = $this->analyserWithOpenAi($don);
            if ($result !== null) {
                return $result;
            }
        } else {
            $this->lastError = 'Clé OPENAI_API_KEY absente ou vide dans .env';
        }

        $reason = $this->getFallbackReason();
        $fallback = $this->fallbackNeutre($reason);
        // Ne pas exposer le détail technique pour 429 (message déjà clair)
        if ($this->lastError !== null && $reason !== 'rate_limit') {
            $fallback['apiError'] = $this->lastError;
        }
        return $fallback;
    }

    /** Raison du fallback pour adapter le libellé (sans "IA non disponible" en cas d'erreur API). */
    private function getFallbackReason(): string
    {
        if ($this->openaiApiKey === null || $this->openaiApiKey === '') {
            return 'no_key';
        }
        if ($this->lastError !== null && (str_contains($this->lastError, '429') || stripos($this->lastError, 'Too Many Requests') !== false)) {
            return 'rate_limit';
        }
        if ($this->lastError !== null) {
            return 'error';
        }
        return 'no_key';
    }

    /**
     * Appel à l'API OpenAI pour analyser le don.
     *
     * @return array{score: int, decision: string, decisionLabel: string}|null
     */
    private function analyserWithOpenAi(Dons $don): ?array
    {
        $texte = $this->buildDescription($don);
        if ($texte === '') {
            return null;
        }

        $prompt = "Tu es un assistant pour une plateforme de dons médicaux (MediLink). "
            . "Analyse le don suivant et réponds UNIQUEMENT avec un objet JSON valide (pas de texte avant ou après), de la forme exacte : "
            . "{\"score\": <nombre entre 0 et 100>, \"decision\": \"valider\" ou \"rejeter\" ou \"en_attente\", \"reason\": \"<courte raison en une phrase>\"}. "
            . "Critères : score élevé si don pertinent (équipement/médicament/matériel médical), description claire, état et quantité cohérents ; "
            . "score bas si contenu inapproprié, description vide ou hors-sujet, médicaments périmés. "
            . "decision = valider si score >= 70, rejeter si score < 40, en_attente sinon.\n\n"
            . "Don à analyser :\n" . $texte;

        $body = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => self::MAX_TOKENS,
            'temperature' => 0.3,
        ];

        $json = json_encode($body);
        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n"
                    . "Authorization: Bearer " . $this->openaiApiKey . "\r\n"
                    . "Content-Length: " . strlen($json) . "\r\n",
                'content' => $json,
                'timeout' => 20,
            ],
        ];
        $context = stream_context_create($opts);

        $response = @file_get_contents(self::OPENAI_URL, false, $context);
        if ($response === false) {
            $err = error_get_last();
            $msg = $err['message'] ?? 'Requête HTTP échouée (vérifier allow_url_fopen, SSL, pare-feu)';
            $this->lastError = $msg;
            $this->logger?->warning('OpenAI analyse don: requête échouée', [
                'message' => $msg,
                'don_id' => $don->getId(),
            ]);
            return null;
        }

        $data = json_decode($response, true);
        if (isset($data['error'])) {
            $msg = $data['error']['message'] ?? json_encode($data['error']);
            $this->lastError = $msg;
            $this->logger?->warning('OpenAI analyse don: erreur API', [
                'error' => $msg,
                'don_id' => $don->getId(),
            ]);
            return null;
        }

        $content = $data['choices'][0]['message']['content'] ?? null;
        if ($content === null) {
            $this->lastError = 'Réponse OpenAI invalide (pas de content)';
            return null;
        }

        $content = trim($content);
        if (preg_match('/\{[^}]+\}/s', $content, $m)) {
            $content = $m[0];
        }
        $parsed = json_decode($content, true);
        if (!is_array($parsed) || !isset($parsed['score'], $parsed['decision'])) {
            $this->lastError = 'Réponse OpenAI : JSON invalide ou champs score/decision manquants';
            return null;
        }

        $score = max(0, min(100, (int) $parsed['score']));
        $decision = strtolower(trim((string) $parsed['decision']));
        if (!in_array($decision, ['valider', 'rejeter', 'en_attente'], true)) {
            $decision = 'en_attente';
        }

        $labels = [
            'valider' => 'Validé par l\'IA',
            'rejeter' => 'Rejeté par l\'IA',
            'en_attente' => 'En attente de validation manuelle',
        ];
        $reason = isset($parsed['reason']) ? trim((string) $parsed['reason']) : '';
        $decisionLabel = $labels[$decision];
        if ($reason !== '') {
            $decisionLabel .= ' : ' . $reason;
        }

        return [
            'score' => $score,
            'decision' => $decision,
            'decisionLabel' => $decisionLabel,
        ];
    }

    private function buildDescription(Dons $don): string
    {
        $parts = [];
        $parts[] = 'Description : ' . ($don->getArticleDescription() ?? '');
        $details = $don->getDetailsSupplementaires();
        if ($details !== null && $details !== '') {
            $parts[] = 'Détails : ' . $details;
        }
        $cat = $don->getCategorie();
        if ($cat !== null) {
            $parts[] = 'Catégorie : ' . $cat->getNom();
        }
        $parts[] = 'Quantité : ' . ($don->getQuantite() ?? 0) . ' ' . ($don->getUnite() ?? 'unités');
        $parts[] = 'État : ' . ($don->getEtat() ?? '');
        $parts[] = 'Urgence : ' . ($don->getNiveauUrgence() ?? '');
        $exp = $don->getDateExpiration();
        if ($exp !== null) {
            $parts[] = 'Date d\'expiration : ' . $exp->format('Y-m-d');
        }
        return implode("\n", $parts);
    }

    /**
     * @param string $reason no_key | rate_limit | error
     * @return array{score: int, decision: string, decisionLabel: string}
     */
    private function fallbackNeutre(string $reason = 'no_key'): array
    {
        $labels = [
            'no_key' => 'En attente de validation manuelle (IA non configurée)',
            'rate_limit' => 'En attente de validation manuelle (limite de requêtes atteinte, réessayez dans un moment)',
            'error' => 'En attente de validation manuelle (service temporairement indisponible, réessayez plus tard)',
        ];
        return [
            'score' => 50,
            'decision' => 'en_attente',
            'decisionLabel' => $labels[$reason] ?? $labels['error'],
        ];
    }
}
