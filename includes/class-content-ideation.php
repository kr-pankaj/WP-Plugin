<?php
if (!defined('ABSPATH')) exit;

class AI_Content_Ideation {
    private $ai_provider;

    public function __construct() {
        $options = get_option('ai_admin_boost_settings', []);
        $model = $options['model'] ?? 'openai';
        $api_key = $options[$model . '_key'] ?? '';
        if (empty($api_key)) {
            error_log("AI_Content_Ideation: No API key provided for model $model");
            throw new Exception("No API key provided for the selected AI model.");
        }
        $this->ai_provider = new AI_Provider($model, $api_key);
    }

    public function create_blog_post($topic) {
        try {
            $options = get_option('ai_admin_boost_settings', []);
            $max_tokens = $options['max_tokens'] ?? 500;

            $prompt = "Write a blog post about '$topic'. Include a title, content with SEO-friendly headings, and suggest image locations with comments like <!-- Add image here: [description] -->. Also provide an SEO-optimized meta title and description at the end in this format: [META TITLE: title] [META DESCRIPTION: description]. Aim for a complete response within the token limit. Don't use markdown characters. use html tags instead.";
            $response = $this->ai_provider->generate_text($prompt, $max_tokens);
            error_log("AI_Content_Ideation: Generated response for topic '$topic': " . substr($response, 0, 200) . "...");

            // Parse the response
            preg_match('/\[META TITLE: (.*?)\]/', $response, $meta_title_match);
            preg_match('/\[META DESCRIPTION: (.*?)\]/', $response, $meta_desc_match);
            $meta_title = $meta_title_match[1] ?? "$topic - Your Site Name";
            $meta_description = $meta_desc_match[1] ?? "Learn about $topic in this insightful blog post.";
            $content = preg_replace('/\[META TITLE:.*?\]|\[META DESCRIPTION:.*?\]/', '', $response);
            $content .= "\n\n<!-- SEO Meta Title: $meta_title -->\n<!-- SEO Meta Description: $meta_description -->";

            // Create the post
            $post_data = [
                'post_title'   => $meta_title,
                'post_content' => $content,
                'post_status'  => 'draft',
                'post_type'    => 'post',
            ];
            $post_id = wp_insert_post($post_data);

            if ($post_id) {
                error_log("AI_Content_Ideation: Blog post created with ID $post_id for topic '$topic'");
                return ['success' => true, 'message' => "Blog post created as draft with ID $post_id.", 'post_id' => $post_id];
            } else {
                error_log("AI_Content_Ideation: Failed to create blog post for topic '$topic'");
                return ['success' => false, 'message' => 'Failed to create blog post.'];
            }
        } catch (Exception $e) {
            error_log("AI_Content_Ideation Error for topic '$topic': " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to generate blog post: ' . $e->getMessage()];
        }
    }
}