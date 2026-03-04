<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Génère une description d'événement à partir du titre via l'API Hugging Face (Router).
 */
final class EventDescriptionGenerator
{
    /**
     * Endpoint OpenAI-compatible via le router Hugging Face.
     * Documentation : https://huggingface.co/docs/api-inference/tasks/chat-completion
     */
    private const HF_CHAT_COMPLETIONS_URL = 'https://router.huggingface.co/v1/chat/completions';

    /**
     * Modèle gratuit disponible sur le router Hugging Face.
     * Llama-3.1-8B-Instruct est disponible pour les comptes gratuits.
     */
    private const MODEL_ID = 'meta-llama/Llama-3.1-8B-Instruct';
    private const MAX_NEW_TOKENS = 150;
    private const TIMEOUT = 60;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?string $huggingfaceApiKey = null,
    ) {
    }

    /**
     * Génère une courte description en français pour un événement à partir de son titre.
     * Retourne une chaîne vide en cas d'erreur ou si la clé API n'est pas configurée.
     */
    public function generateFromTitle(string $title): string
    {
        $title = trim($title);
        if ($title === '') {
            return '';
        }

        if ($this->huggingfaceApiKey === null || $this->huggingfaceApiKey === '') {
            return '';
        }

        try {
            $response = $this->httpClient->request('POST', self::HF_CHAT_COMPLETIONS_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->huggingfaceApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => self::MODEL_ID,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Tu es un assistant qui rédige des descriptions courtes et professionnelles pour des événements médicaux en français. Réponds uniquement avec la description, sans introduction ni explication supplémentaire.',
                        ],
                        [
                            'role' => 'user',
                            'content' => sprintf(
                                'Écris une description courte (2 à 3 phrases) en français pour un événement intitulé : %s.',
                                $title
                            ),
                        ],
                    ],
                    'max_tokens' => self::MAX_NEW_TOKENS,
                    'temperature' => 0.7,
                ],
                'timeout' => self::TIMEOUT,
            ]);

            $status = $response->getStatusCode();
            $data = $response->toArray(false);

            if ($status !== 200) {
                return '';
            }

            return $this->extractGeneratedText($data);
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function extractGeneratedText(array $data): string
    {
        // Format OpenAI-compatible : choices[0].message.content
        if (isset($data['choices'][0]['message']['content']) && is_string($data['choices'][0]['message']['content'])) {
            return trim($data['choices'][0]['message']['content']);
        }

        return '';
    }
}
