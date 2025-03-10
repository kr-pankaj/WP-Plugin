<?php
if (!defined('ABSPATH')) exit;

class AI_SEO_Suggestions {
    private $ai_provider;

    public function __construct() {
        $options = get_option('ai_admin_boost_settings', []);
        $model = $options['model'] ?? 'openai';
        $api_key = $options[$model . '_key'] ?? '';
        if (empty(AI_Admin_Boost_Encryption::decrypt($api_key))) {
            error_log("AI_SEO_Suggestions: No valid API key for model $model");
            throw new Exception("No valid API key provided for the selected AI model.");
        }
        $this->ai_provider = new AI_Provider($model, $api_key);
    }

    public function suggest_seo($content) {
        $options = get_option('ai_admin_boost_settings', []);
        $max_tokens = $options['max_tokens'] ?? 500;

        $system_prompt = "You are an expert SEO assistant. Analyze the provided content and provide specific, concise, and actionable suggestions to improve its SEO. Tailor your advice directly to the content, focusing on keyword optimization, meta title, meta description, headings, and internal linking. Avoid lengthy explanations. Ensure the response is complete within the token limit. Don't use markdown characters.";
        $user_prompt = "Analyze the SEO of this content and provide suggestions for improvement:\n\n$content";
        
        $prompt = "$system_prompt\n\n$user_prompt";
        return $this->ai_provider->generate_text($prompt, $max_tokens);
    }

    public function analyze_seo_all() {
        $options = get_option('ai_admin_boost_settings', []);
        $max_tokens = $options['max_tokens'] ?? 500;

        // Fetch both pages and published posts
        $pages = get_pages();
        $posts = get_posts(['post_status' => 'publish', 'posts_per_page' => -1]);
        $content = '';

        foreach ($pages as $page) {
            $meta_title = get_post_meta($page->ID, '_yoast_wpseo_title', true) ?: $page->post_title;
            $meta_desc = get_post_meta($page->ID, '_yoast_wpseo_metadesc', true) ?: '';
            $content .= "Page: " . $page->post_title . "\nMeta Title: $meta_title\nMeta Description: $meta_desc\nContent: " . $page->post_content . "\n\n";
        }

        foreach ($posts as $post) {
            $meta_title = get_post_meta($post->ID, '_yoast_wpseo_title', true) ?: $post->post_title;
            $meta_desc = get_post_meta($post->ID, '_yoast_wpseo_metadesc', true) ?: '';
            $content .= "Post: " . $post->post_title . "\nMeta Title: $meta_title\nMeta Description: $meta_desc\nContent: " . $post->post_content . "\n\n";
        }

        $system_prompt = "You are an expert SEO assistant. Analyze the provided pages and posts (including titles, meta tags, and raw HTML content) and provide specific, concise suggestions to improve their SEO. Focus on site-wide patterns and key issues. Keep it brief. Don't use markdown characters.";
        $user_prompt = "Analyze the SEO of these pages and posts and provide suggestions for improvement:\n$content";
        
        $prompt = "$system_prompt\n\n$user_prompt";
        return $this->ai_provider->generate_text($prompt, $max_tokens * 2);
    }

    public function analyze_seo_specific($post_id) {
        $options = get_option('ai_admin_boost_settings', []);
        $max_tokens = $options['max_tokens'] ?? 500;

        $post = get_post($post_id);
        if (!$post) return 'Content not found.';
        
        $type = ($post->post_type === 'page') ? 'Page' : 'Post';
        $meta_title = get_post_meta($post->ID, '_yoast_wpseo_title', true) ?: $post->post_title;
        $meta_desc = get_post_meta($post->ID, '_yoast_wpseo_metadesc', true) ?: '';
        
        $content = "$type: " . $post->post_title . "\nMeta Title: $meta_title\nMeta Description: $meta_desc\nContent: " . $post->post_content;

        $system_prompt = "You are an expert SEO assistant. Analyze the provided $type (including title, meta tags, and raw HTML content) and provide specific, concise, and actionable suggestions to improve its SEO. Focus on keyword optimization, meta tags, headings, and internal linking. Don't use markdown characters.";
        $user_prompt = "Analyze the SEO of this $type and provide suggestions for improvement:\n$content";
        
        $prompt = "$system_prompt\n\n$user_prompt";
        return $this->ai_provider->generate_text($prompt, $max_tokens);
    }
}