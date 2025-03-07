<?php
if (!defined('ABSPATH')) exit;

class AI_SEO_Suggestions {
    private $ai_provider;

    public function __construct() {
        $options = get_option('ai_admin_boost_settings', []);
        $model = $options['model'] ?? 'openai';
        $api_key = $options[$model . '_key'] ?? '';
        $this->ai_provider = new AI_Provider($model, $api_key);
    }

    public function suggest_seo($content) {
        $options = get_option('ai_admin_boost_settings', []);
        $max_tokens = $options['max_tokens'] ?? 500;

        $system_prompt = "You are an expert SEO assistant. Analyze the provided content and provide specific, concise, and actionable suggestions to improve its SEO. Tailor your advice directly to the content, focusing on keyword optimization, meta title, meta description, headings, and internal linking. Avoid lengthy explanations. Ensure the response is complete within the token limit.";
        $user_prompt = "Analyze the SEO of this content and provide suggestions for improvement:\n\n$content";
        
        $prompt = $this->ai_provider->get_model() === 'openai' ? "$system_prompt\n\n$user_prompt" : "$system_prompt\n\n$user_prompt";
        return $this->ai_provider->generate_text($prompt, $max_tokens);
    }

    public function analyze_seo_all() {
        $options = get_option('ai_admin_boost_settings', []);
        $max_tokens = $options['max_tokens'] ?? 500;

        $pages = get_pages();
        $content = '';
        foreach ($pages as $page) {
            $meta_title = get_post_meta($page->ID, '_yoast_wpseo_title', true) ?: $page->post_title;
            $meta_desc = get_post_meta($page->ID, '_yoast_wpseo_metadesc', true) ?: '';
            $content .= "Page: " . $page->post_title . "\nMeta Title: $meta_title\nMeta Description: $meta_desc\nContent: " . $page->post_content . "\n\n";
        }
        $system_prompt = "You are an expert SEO assistant. Analyze the provided pages (including title, meta tags, and raw HTML content) and provide specific, concise suggestions to improve their SEO. Focus on site-wide patterns and key page issues. Keep it brief.";
        $user_prompt = "Analyze the SEO of these pages and provide suggestions for improvement:\n$content";
        
        $prompt = "$system_prompt\n\n$user_prompt";
        return $this->ai_provider->generate_text($prompt, $max_tokens * 2);
    }

    public function analyze_seo_specific($page_id) {
        $options = get_option('ai_admin_boost_settings', []);
        $max_tokens = $options['max_tokens'] ?? 500;

        $page = get_post($page_id);
        if (!$page) return 'Page not found.';
        
        // Fetch meta data (Yoast SEO as example; adjust for other plugins if needed)
        $meta_title = get_post_meta($page->ID, '_yoast_wpseo_title', true) ?: $page->post_title;
        $meta_desc = get_post_meta($page->ID, '_yoast_wpseo_metadesc', true) ?: '';
        
        // Send raw content with HTML
        $content = "Page: " . $page->post_title . "\nMeta Title: $meta_title\nMeta Description: $meta_desc\nContent: " . $page->post_content;

        $system_prompt = "You are an expert SEO assistant. Analyze the provided page (including title, meta tags, and raw HTML content) and provide specific, concise, and actionable suggestions to improve its SEO. Focus on keyword optimization, meta tags, headings, and internal linking. Use single asterisks (*) for bullet points and keep it brief.";
        $user_prompt = "Analyze the SEO of this page and provide suggestions for improvement:\n$content";
        
        $prompt = "$system_prompt\n\n$user_prompt";
        return $this->ai_provider->generate_text($prompt, $max_tokens);
    }
}