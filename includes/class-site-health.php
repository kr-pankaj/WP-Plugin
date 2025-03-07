<?php
if (!defined('ABSPATH')) exit;

class AI_Site_Health {
    private $ai_provider;

    public function __construct() {
        $options = get_option('ai_admin_boost_settings', []);
        $model = $options['model'] ?? 'openai';
        $api_key = $options[$model . '_key'] ?? '';
        $this->ai_provider = new AI_Provider($model, $api_key);
    }

    public function analyze_health($data) {
        $prompt = "Analyze this site data and provide health suggestions: $data";
        return $this->ai_provider->generate_text($prompt);
    }
}