<?php

namespace App\Service;

/**
 * Vérification des textes des dons : orthographe, grammaire (LanguageTool), mots inappropriés.
 * L'admin peut appliquer les corrections après confirmation.
 */
class DonTextCheckService
{
    private const LANGUAGETOOL_URL = 'https://api.languagetool.org/v2/check';
    private const LANGUAGE = 'fr';

    /**
     * Mots inappropriés avec remplacement suggéré (optionnel).
     * Clé = mot ou expression à détecter (minuscules), valeur = remplacement ou null (masquage).
     */
    private array $badWordsMap = [
        'merde' => '***',
        'putain' => '***',
        'fuck' => '***',
        'shit' => '***',
        'con' => '***',
        'connard' => '***',
        'salope' => '***',
        'enculé' => '***',
        'pd' => '***',
        'pute' => '***',
        'suicide' => '***',
    ];

    /**
     * Suggestions de correction pour expressions courantes (orthographe / grammaire).
     * Ex. "chaise nouveau" → "une nouvelle chaise", "un chaise on bonne etat" → "une chaise en bon état"
     */
    private array $phraseCorrections = [
        'un chaise on bonne etat' => 'une chaise en bon état',
        'un chaise en bonne etat' => 'une chaise en bon état',
        'un chaise on bon etat' => 'une chaise en bon état',
        'on bonne etat' => 'en bon état',
        'on bon etat' => 'en bon état',
        'un chaise' => 'une chaise',
        'chaise nouveau' => 'une nouvelle chaise',
        'chaise neuve' => 'une chaise neuve',
        'medicament nouveau' => 'un nouveau médicament',
        'medicaments nouveaux' => 'des nouveaux médicaments',
        'fauteuil nouveau' => 'un nouveau fauteuil',
        'lit nouveau' => 'un nouveau lit',
    ];

    /**
     * Analyse un texte et retourne les suggestions (LanguageTool + mots interdits + expressions).
     *
     * @return array{hasIssues: bool, suggestions: array, correctedText: string}
     */
    public function getSuggestions(string $text): array
    {
        $text = trim($text);
        $suggestions = [];
        $correctedText = $text;

        if ($text === '') {
            return [
                'hasIssues' => false,
                'suggestions' => [],
                'correctedText' => '',
            ];
        }

        // 1) Mots inappropriés
        $textLower = mb_strtolower($text, 'UTF-8');
        foreach ($this->badWordsMap as $word => $replacement) {
            if (str_contains($textLower, $word)) {
                $suggestions[] = [
                    'type' => 'badword',
                    'message' => 'Mot inapproprié détecté',
                    'original' => $word,
                    'replacement' => $replacement ?? '***',
                ];
                $correctedText = $this->replaceWordIgnoreCase($correctedText, $word, $replacement ?? '***');
            }
        }

        // 2) Expressions à corriger (ex: "chaise nouveau" → "une nouvelle chaise")
        foreach ($this->phraseCorrections as $phrase => $replacement) {
            if (mb_strpos($textLower, $phrase) !== false) {
                $suggestions[] = [
                    'type' => 'phrase',
                    'message' => 'Expression à corriger',
                    'original' => $phrase,
                    'replacement' => $replacement,
                ];
                $correctedText = $this->replacePhraseIgnoreCase($correctedText, $phrase, $replacement);
                $textLower = mb_strtolower($correctedText, 'UTF-8');
            }
        }

        // 3) LanguageTool (orthographe / grammaire) sur le texte déjà nettoyé
        $ltResult = $this->callLanguageTool($correctedText);
        if (!empty($ltResult['matches'])) {
            $offsets = [];
            foreach ($ltResult['matches'] as $match) {
                $offset = (int) $match['offset'];
                $length = (int) $match['length'];
                $replacement = isset($match['replacements'][0]['value'])
                    ? $match['replacements'][0]['value']
                    : null;
                $message = $match['message'] ?? $match['shortMessage'] ?? 'Correction suggérée';
                $suggestions[] = [
                    'type' => 'grammar',
                    'message' => $message,
                    'offset' => $offset,
                    'length' => $length,
                    'original' => mb_substr($correctedText, $offset, $length, 'UTF-8'),
                    'replacement' => $replacement,
                ];
                if ($replacement !== null) {
                    $offsets[] = ['offset' => $offset, 'length' => $length, 'replacement' => $replacement];
                }
            }
            usort($offsets, fn ($a, $b) => $b['offset'] <=> $a['offset']);
            foreach ($offsets as $item) {
                $correctedText = mb_substr($correctedText, 0, $item['offset'], 'UTF-8')
                    . $item['replacement']
                    . mb_substr($correctedText, $item['offset'] + $item['length'], null, 'UTF-8');
            }
        }

        return [
            'hasIssues' => count($suggestions) > 0,
            'suggestions' => $suggestions,
            'correctedText' => $correctedText,
        ];
    }

    /**
     * Appel à l'API LanguageTool (limité en gratuit : 20 req/min).
     */
    private function callLanguageTool(string $text): array
    {
        if (mb_strlen($text) > 20000) {
            $text = mb_substr($text, 0, 20000, 'UTF-8');
        }

        $postData = http_build_query([
            'text' => $text,
            'language' => self::LANGUAGE,
        ]);

        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-type: application/x-www-form-urlencoded\r\nContent-Length: " . strlen($postData),
                'content' => $postData,
                'timeout' => 10,
            ],
        ];
        $context = stream_context_create($opts);

        $response = @file_get_contents(self::LANGUAGETOOL_URL, false, $context);
        if ($response === false) {
            return ['matches' => []];
        }

        $data = json_decode($response, true);
        return is_array($data) ? $data : ['matches' => []];
    }

    private function replaceWordIgnoreCase(string $text, string $word, string $replacement): string
    {
        return preg_replace('/' . preg_quote($word, '/') . '/iu', $replacement, $text);
    }

    private function replacePhraseIgnoreCase(string $text, string $phrase, string $replacement): string
    {
        $pattern = '/\b' . preg_quote($phrase, '/') . '\b/iu';
        return preg_replace($pattern, $replacement, $text, 1);
    }
}
