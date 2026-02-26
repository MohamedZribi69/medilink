<?php

namespace App\Service;

use App\Entity\CategorieDon;

/**
 * Suggestion de catégorie pour un don à partir de la description (IA OpenAI ou fallback par mots-clés).
 */
class DonCategorySuggestionService
{
    private const OPENAI_URL = 'https://api.openai.com/v1/chat/completions';
    private const MAX_TOKENS = 50;

    public function __construct(
        private readonly ?string $openaiApiKey = null
    ) {
    }

    /**
     * Suggère la catégorie la plus pertinente pour une description de don.
     *
     * @param CategorieDon[] $categories
     * @return array{id: int, nom: string}|null
     */
    public function suggestCategory(string $description, array $categories): ?array
    {
        $description = trim($description);
        if ($description === '' || empty($categories)) {
            return null;
        }

        if ($this->openaiApiKey !== null && $this->openaiApiKey !== '') {
            $suggested = $this->suggestWithOpenAi($description, $categories);
            if ($suggested !== null) {
                return $suggested;
            }
        }

        return $this->suggestWithKeywords($description, $categories);
    }

    /**
     * Appel à l'API OpenAI pour choisir la catégorie la plus adaptée.
     *
     * @param CategorieDon[] $categories
     * @return array{id: int, nom: string}|null
     */
    private function suggestWithOpenAi(string $description, array $categories): ?array
    {
        $names = array_map(fn (CategorieDon $c) => $c->getNom(), $categories);
        $list = implode(', ', $names);

        $prompt = sprintf(
            "Tu es un assistant pour une plateforme de dons médicaux. Voici la liste des catégories de dons : %s.\n\n"
            . "Pour la description de don suivante, réponds avec UNIQUEMENT le nom exact d'une seule catégorie (celle qui correspond le mieux), sans ponctuation ni explication.\n\n"
            . "Description du don : %s",
            $list,
            $description
        );

        $body = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => self::MAX_TOKENS,
            'temperature' => 0.2,
        ];

        $json = json_encode($body);
        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n"
                    . "Authorization: Bearer " . $this->openaiApiKey . "\r\n"
                    . "Content-Length: " . strlen($json) . "\r\n",
                'content' => $json,
                'timeout' => 15,
            ],
        ];
        $context = stream_context_create($opts);

        $response = @file_get_contents(self::OPENAI_URL, false, $context);
        if ($response === false) {
            return null;
        }

        $data = json_decode($response, true);
        $content = $data['choices'][0]['message']['content'] ?? null;
        if ($content === null) {
            return null;
        }

        $suggestedName = trim($content);
        foreach ($categories as $c) {
            if (strcasecmp($c->getNom() ?? '', $suggestedName) === 0) {
                return ['id' => $c->getId(), 'nom' => $c->getNom()];
            }
        }

        return null;
    }

    /**
     * Fallback : suggestion par mots-clés (mots du nom de catégorie OU mots courants → catégorie).
     *
     * @param CategorieDon[] $categories
     * @return array{id: int, nom: string}|null
     */
    private function suggestWithKeywords(string $description, array $categories): ?array
    {
        $descLower = mb_strtolower($description, 'UTF-8');
        $bestScore = 0;
        $best = null;

        // 1) Mots du nom de la catégorie présents dans la description
        foreach ($categories as $c) {
            $nom = $c->getNom() ?? '';
            $words = preg_split('/\s+/u', $nom, -1, PREG_SPLIT_NO_EMPTY);
            $score = 0;
            foreach ($words as $word) {
                $word = mb_strtolower($word, 'UTF-8');
                if (mb_strlen($word) >= 2 && mb_strpos($descLower, $word) !== false) {
                    $score += 1;
                }
            }
            if ($score > 0 && $score > $bestScore) {
                $bestScore = $score;
                $best = $c;
            }
        }

        // 2) Mots-clés courants → termes à chercher dans le nom de catégorie (si aucune suggestion ci-dessus)
        $keywordToSearch = [
            'fauteuil' => ['mobilité', 'équipement', 'mobilier'],
            'roulant' => ['mobilité', 'équipement'],
            'déambulateur' => ['mobilité', 'équipement'],
            'deambulateur' => ['mobilité', 'équipement'],
            'béquille' => ['mobilité', 'équipement'],
            'bequille' => ['mobilité', 'équipement'],
            'medicament' => ['médicament'],
            'médicament' => ['médicament'],
            'paracetamol' => ['médicament'],
            'doliprane' => ['médicament'],
            'masque' => ['médicament', 'masque'],
            'matériel' => ['matériel', 'médical'],
            'materiel' => ['matériel', 'medical'],
        ];
        if ($best === null) {
            foreach ($keywordToSearch as $keyword => $searchTerms) {
                $terms = is_array($searchTerms) ? $searchTerms : [$searchTerms];
                if (mb_strlen($keyword) >= 2 && mb_strpos($descLower, $keyword) !== false) {
                    foreach ($categories as $c) {
                        $nomLower = mb_strtolower($c->getNom() ?? '', 'UTF-8');
                        foreach ($terms as $searchInNom) {
                            $searchLower = mb_strtolower($searchInNom, 'UTF-8');
                            if (mb_strpos($nomLower, $searchLower) !== false) {
                                return ['id' => $c->getId(), 'nom' => $c->getNom()];
                            }
                        }
                    }
                }
            }
        }

        if ($best === null) {
            return null;
        }

        return ['id' => $best->getId(), 'nom' => $best->getNom()];
    }
}
