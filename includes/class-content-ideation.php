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

            // First, generate a content outline
            $outline_prompt = "Create a detailed outline for a blog post about '$topic'. Include main sections, subsections, and key points to cover. Format as a structured list.";
            $outline = $this->ai_provider->generate_text($outline_prompt, 200);

            // Generate the main content with better structure
            $content_prompt = "Write a comprehensive blog post about '$topic' using this outline:\n$outline\n\nInclude:\n1. An engaging introduction\n2. Well-structured sections with H2 and H3 headings\n3. Relevant examples and explanations\n4. A strong conclusion\n5. Suggested image placements with <!-- Add image here: [description] -->\n6. Internal linking suggestions with <!-- Internal link: [text] -> [url] -->\n7. Key takeaways or summary points\n\nFormat using HTML tags. Don't use markdown.";
            $content = $this->ai_provider->generate_text($content_prompt, $max_tokens);

            // Generate optimized meta title and description
            $meta_prompt = "Create an SEO-optimized meta title and description for a blog post about '$topic'. The title should be compelling and under 60 characters. The description should be informative and under 160 characters. Include relevant keywords naturally. Format as: [META TITLE: title] [META DESCRIPTION: description]";
            $meta_response = $this->ai_provider->generate_text($meta_prompt, 100);

            // Parse meta information
            preg_match('/\[META TITLE: (.*?)\]/', $meta_response, $meta_title_match);
            preg_match('/\[META DESCRIPTION: (.*?)\]/', $meta_response, $meta_desc_match);
            $meta_title = $meta_title_match[1] ?? "$topic - Your Site Name";
            $meta_description = $meta_desc_match[1] ?? "Learn about $topic in this insightful blog post.";

            // Generate content optimization suggestions
            $optimization_prompt = "Analyze this blog post content and provide 3-5 specific suggestions for improvement:\n$content\n\nFocus on:\n1. SEO optimization\n2. Readability\n3. Engagement\n4. Structure\n\nFormat as: [OPTIMIZATION: suggestion1|suggestion2|suggestion3]";
            $optimization_response = $this->ai_provider->generate_text($optimization_prompt, 150);
            preg_match('/\[OPTIMIZATION: (.*?)\]/', $optimization_response, $optimization_match);
            $optimization_suggestions = explode('|', $optimization_match[1] ?? '');

            // Clean up the content
            $content = preg_replace('/\[META TITLE:.*?\]|\[META DESCRIPTION:.*?\]|\[OPTIMIZATION:.*?\]/', '', $content);
            
            // Add optimization suggestions as comments
            $content .= "\n\n<!-- Content Optimization Suggestions:\n";
            foreach ($optimization_suggestions as $suggestion) {
                $content .= "- " . trim($suggestion) . "\n";
            }
            $content .= "-->\n\n<!-- SEO Meta Title: $meta_title -->\n<!-- SEO Meta Description: $meta_description -->";

            // Create the post
            $post_data = [
                'post_title'   => $meta_title,
                'post_content' => $content,
                'post_status'  => 'draft',
                'post_type'    => 'post',
            ];
            $post_id = wp_insert_post($post_data);

            if ($post_id) {
                // Add optimization suggestions as post meta
                update_post_meta($post_id, '_ai_optimization_suggestions', $optimization_suggestions);
                
                error_log("AI_Content_Ideation: Blog post created with ID $post_id for topic '$topic'");
                return [
                    'success' => true, 
                    'message' => "Blog post created as draft with ID $post_id.", 
                    'post_id' => $post_id,
                    'optimization_suggestions' => $optimization_suggestions
                ];
            } else {
                error_log("AI_Content_Ideation: Failed to create blog post for topic '$topic'");
                return ['success' => false, 'message' => 'Failed to create blog post.'];
            }
        } catch (Exception $e) {
            error_log("AI_Content_Ideation Error for topic '$topic': " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to generate blog post: ' . $e->getMessage()];
        }
    }

    /**
     * Generate meta description for existing content
     */
    public function generate_meta_description($content, $title) {
        try {
            $prompt = "Create an SEO-optimized meta description for this content:\nTitle: $title\n\nContent preview: " . substr(strip_tags($content), 0, 200) . "...\n\nRequirements:\n- Under 160 characters\n- Include main keywords naturally\n- Be compelling and informative\n- End with a call to action\n\nFormat as: [META DESCRIPTION: description]";
            
            $response = $this->ai_provider->generate_text($prompt, 100);
            preg_match('/\[META DESCRIPTION: (.*?)\]/', $response, $match);
            
            return $match[1] ?? "Learn more about $title in this comprehensive article.";
        } catch (Exception $e) {
            error_log("Meta Description Generation Error: " . $e->getMessage());
            return "Learn more about $title in this comprehensive article.";
        }
    }

    /**
     * Analyze content and suggest improvements
     */
    public function analyze_content($content) {
        try {
            $prompt = "Analyze this content and provide specific suggestions for improvement:\n$content\n\nFocus on:\n1. SEO optimization\n2. Readability\n3. Engagement\n4. Structure\n5. Internal linking opportunities\n\nFormat as: [ANALYSIS: suggestion1|suggestion2|suggestion3|suggestion4|suggestion5]";
            
            $response = $this->ai_provider->generate_text($prompt, 200);
            preg_match('/\[ANALYSIS: (.*?)\]/', $response, $match);
            
            return explode('|', $match[1] ?? '');
        } catch (Exception $e) {
            error_log("Content Analysis Error: " . $e->getMessage());
            return [];
        }
    }
}