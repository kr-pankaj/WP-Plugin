<?php
if (!defined('ABSPATH')) exit;

class AI_Provider {
    private $model;
    private $apiKey;

    public function __construct($model, $apiKey) {
        $this->model = $model;
        $this->apiKey = $apiKey;
    }

    public function generate_text($prompt, $max_tokens = 150) {
        if ($this->model === 'openai') {
            return $this->call_openai($prompt, $max_tokens);
        } elseif ($this->model === 'gemini') {
            return $this->call_gemini($prompt, $max_tokens);
        }
        return 'Model not supported.';
    }

    private function call_openai($prompt, $max_tokens) {
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'model' => 'gpt-3.5-turbo',
                'messages' => [['role' => 'user', 'content' => $prompt]],
                'max_tokens' => $max_tokens,
            ]),
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            return 'Error: ' . $response->get_error_message();
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body['choices'][0]['message']['content'] ?? 'No response from OpenAI.';
    }

    private function call_gemini($prompt, $max_tokens) {
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . urlencode($this->apiKey);
        $response = wp_remote_post($endpoint, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'maxOutputTokens' => $max_tokens,
                    'temperature' => 0.7,
                ]
            ]),
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            return 'Error: ' . $response->get_error_message();
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body['candidates'][0]['content']['parts'][0]['text'] ?? 'No response from Gemini.';
    }
}