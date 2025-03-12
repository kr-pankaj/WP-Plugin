<?php
/*
 * Plugin Name: AI Admin Boost
 * Description: An AI-powered assistant for WordPress admins with multi-model support.
 * Version: 1.0.0
 * Author: Chandrakant Kumar
 * License: GPL-2.0+
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'includes/class-encryption.php';

class AI_Admin_Boost {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_create_blog_post', [$this, 'ajax_create_blog_post']);
        add_action('wp_ajax_suggest_seo', [$this, 'ajax_suggest_seo']);
        add_action('wp_ajax_analyze_seo_all', [$this, 'ajax_analyze_seo_all']);
        add_action('wp_ajax_analyze_seo_specific', [$this, 'ajax_analyze_seo_specific']);
        add_action('wp_ajax_run_admin_shortcut', [$this, 'ajax_run_admin_shortcut']);
        add_action('wp_ajax_get_draft_posts', [$this, 'ajax_get_draft_posts']);
        add_action('wp_ajax_schedule_post', [$this, 'ajax_schedule_post']);
        add_action('wp_ajax_generate_meta_description', [$this, 'ajax_generate_meta_description']);
        add_action('wp_ajax_analyze_content', [$this, 'ajax_analyze_content']);
        add_action('wp_ajax_get_scheduled_posts', [$this, 'ajax_get_scheduled_posts']);
        add_action('wp_ajax_bulk_update_post_dates', [$this, 'ajax_bulk_update_post_dates']);
    }

    public static function activate() {
        AI_Admin_Boost_Encryption::generate_key_on_activation();
    }

    public function add_admin_menu() {
        add_menu_page(
            'AI Admin Boost',
            'AI Boost',
            'manage_options',
            'ai-admin-boost',
            [$this, 'render_settings_page'],
            'dashicons-admin-generic'
        );
    }

    public function register_settings() {
        register_setting('ai_admin_boost_options', 'ai_admin_boost_settings', [
            'sanitize_callback' => [$this, 'sanitize_settings']
        ]);

        add_settings_section('ai_admin_boost_main', 'AI Settings', null, 'ai-admin-boost');
        add_settings_field('ai_api_keys', 'API Keys', [$this, 'render_api_keys_field'], 'ai-admin-boost', 'ai_admin_boost_main');
        add_settings_field('ai_model', 'Default AI Model', [$this, 'render_model_field'], 'ai-admin-boost', 'ai_admin_boost_main');
        add_settings_field('ai_max_tokens', 'Max Tokens', [$this, 'render_max_tokens_field'], 'ai-admin-boost', 'ai_admin_boost_main');
    }

    public function render_settings_page() {
        require_once plugin_dir_path(__FILE__) . 'admin/partials/settings-page.php';
    }

    public function render_api_keys_field() {
        $options = get_option('ai_admin_boost_settings', []);
        $openai_key = $options['openai_key'] ?? '';
        $gemini_key = $options['gemini_key'] ?? '';
        $openai_display = $openai_key;
        $gemini_display = $gemini_key;
        ?>
        <label>OpenAI API Key: <input type="password" name="ai_admin_boost_settings[openai_key]" value="<?php echo esc_attr($openai_display); ?>" placeholder="Enter OpenAI API Key"></label><br>
        <label>Gemini API Key: <input type="password" name="ai_admin_boost_settings[gemini_key]" value="<?php echo esc_attr($gemini_display); ?>" placeholder="Enter Gemini API Key"></label>
        <?php
    }

    public function render_model_field() {
        $options = get_option('ai_admin_boost_settings', []);
        $model = $options['model'] ?? 'openai';
        ?>
        <select name="ai_admin_boost_settings[model]">
            <option value="openai" <?php selected($model, 'openai'); ?>>OpenAI</option>
            <option value="gemini" <?php selected($model, 'gemini'); ?>>Gemini</option>
        </select>
        <?php
    }

    public function render_max_tokens_field() {
        $options = get_option('ai_admin_boost_settings', []);
        $max_tokens = $options['max_tokens'] ?? 500;
        ?>
        <label>Max Tokens: <input type="number" name="ai_admin_boost_settings[max_tokens]" value="<?php echo esc_attr($max_tokens); ?>" min="50" max="4000" step="50"></label>
        <p class="description">Set the maximum number of tokens for AI responses (50-4000). Higher values allow longer responses.</p>
        <?php
    }

    public function sanitize_settings($input) {
        $sanitized = [];
        $sanitized['openai_key'] = AI_Admin_Boost_Encryption::encrypt(sanitize_text_field($input['openai_key'] ?? ''));
        $sanitized['gemini_key'] = AI_Admin_Boost_Encryption::encrypt(sanitize_text_field($input['gemini_key'] ?? ''));
        $sanitized['model'] = in_array($input['model'], ['openai', 'gemini']) ? $input['model'] : 'openai';
        $sanitized['max_tokens'] = max(50, min(4000, intval($input['max_tokens'] ?? 500)));
        return $sanitized;
    }

    public function enqueue_assets($hook) {
        if ($hook !== 'toplevel_page_ai-admin-boost') return;
        wp_enqueue_style('ai-admin-boost', plugin_dir_url(__FILE__) . 'admin/css/ai-admin-boost.css');
        wp_enqueue_script('ai-admin-boost', plugin_dir_url(__FILE__) . 'admin/js/ai-admin-boost.js', ['jquery'], null, true);
        wp_localize_script('ai-admin-boost', 'ai_admin_boost', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_admin_boost_nonce'),
        ]);
    }

    public function ajax_create_blog_post() {
        check_ajax_referer('ai_admin_boost_nonce', 'nonce');
        try {
            $topic = sanitize_text_field($_POST['topic'] ?? '');
            if (empty($topic)) {
                wp_send_json_error(['message' => 'Topic is required.']);
            }
            $ideation = new AI_Content_Ideation();
            $result = $ideation->create_blog_post($topic);
            if (isset($result['post_id'])) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error(['message' => $result['message']]);
            }
        } catch (Exception $e) {
            error_log("AJAX Create Blog Post Error: " . $e->getMessage());
            wp_send_json_error(['message' => 'Failed to create blog post: ' . $e->getMessage()]);
        }
    }

    public function ajax_suggest_seo() {
        check_ajax_referer('ai_admin_boost_nonce', 'nonce');
        try {
            $content = sanitize_textarea_field($_POST['content']);
            $seo = new AI_SEO_Suggestions();
            $response = $seo->suggest_seo($content);
            wp_send_json(['html' => $response]);
        } catch (Exception $e) {
            error_log("AJAX Suggest SEO Error: " . $e->getMessage());
            wp_send_json_error(['message' => 'Failed to generate SEO suggestions.']);
        }
    }

    public function ajax_analyze_seo_all() {
        check_ajax_referer('ai_admin_boost_nonce', 'nonce');
        try {
            $seo = new AI_SEO_Suggestions();
            $response = $seo->analyze_seo_all();
            wp_send_json(['html' => $response]);
        } catch (Exception $e) {
            error_log("AJAX Analyze SEO All Error: " . $e->getMessage());
            wp_send_json_error(['message' => 'Failed to analyze all pages.']);
        }
    }

    public function ajax_analyze_seo_specific() {
        check_ajax_referer('ai_admin_boost_nonce', 'nonce');
        try {
            $post_id = intval($_POST['page_id']);
            $post = get_post($post_id);
            error_log("AJAX Analyze SEO Specific: Post ID = $post_id, Type = " . ($post ? $post->post_type : 'Not found'));
            $seo = new AI_SEO_Suggestions();
            $response = $seo->analyze_seo_specific($post_id);
            wp_send_json(['html' => $response]);
        } catch (Exception $e) {
            error_log("AJAX Analyze SEO Specific Error: " . $e->getMessage());
            wp_send_json_error(['message' => 'Failed to analyze content: ' . $e->getMessage()]);
        }
    }

    public function ajax_run_admin_shortcut() {
        check_ajax_referer('ai_admin_boost_nonce', 'nonce');
        try {
            $shortcut = sanitize_text_field($_POST['shortcut']);
            $automation = new AI_Task_Automation();
            $response = $automation->run_shortcut($shortcut);
            wp_send_json(['message' => $response]);
        } catch (Exception $e) {
            error_log("AJAX Run Admin Shortcut Error: " . $e->getMessage());
            wp_send_json(['success' => false, 'message' => 'Failed to run shortcut: ' . $e->getMessage()]);
        }
    }

    public function ajax_get_draft_posts() {
        check_ajax_referer('ai_admin_boost_nonce', 'nonce');
        try {
            $args = [
                'post_status' => 'draft',
                'post_type' => get_post_types(['public' => true], 'names'),
                'posts_per_page' => -1,
            ];
            $posts = get_posts($args);
            $draft_posts = array_map(function($post) {
                $post_type_obj = get_post_type_object($post->post_type);
                $post_type_label = !empty($post_type_obj->labels->singular_name) ? $post_type_obj->labels->singular_name : $post->post_type;
                return [
                    'ID' => $post->ID,
                    'post_title' => $post->post_title ?: '(No title)',
                    'post_type' => $post_type_label,
                ];
            }, $posts);
            wp_send_json(['success' => true, 'posts' => $draft_posts]);
        } catch (Exception $e) {
            error_log("AJAX Get Draft Posts Error: " . $e->getMessage());
            wp_send_json(['success' => false, 'message' => 'Failed to load draft posts: ' . $e->getMessage()]);
        }
    }

    public function ajax_schedule_post() {
        check_ajax_referer('ai_admin_boost_nonce', 'nonce');
        try {
            if (isset($_POST['schedules']) && is_array($_POST['schedules'])) {
                $schedules = $_POST['schedules'];
                $results = [];

                foreach ($schedules as $index => $schedule) {
                    $post_id = intval($schedule['post_id'] ?? 0);
                    $datetime = sanitize_text_field($schedule['datetime'] ?? '');

                    if (!$post_id || !$datetime) {
                        $results[] = [
                            'post_id' => $post_id,
                            'success' => false,
                            'message' => "Invalid post ID or datetime for schedule #$index."
                        ];
                        continue;
                    }

                    $post = get_post($post_id);
                    if (!$post || $post->post_status !== 'draft') {
                        $results[] = [
                            'post_id' => $post_id,
                            'success' => false,
                            'message' => "Post #$post_id is not a draft or does not exist."
                        ];
                        continue;
                    }

                    $updated = wp_update_post([
                        'ID' => $post_id,
                        'post_status' => 'future',
                        'post_date' => $datetime,
                        'post_date_gmt' => get_gmt_from_date($datetime),
                        'edit_date' => true,
                    ]);

                    if ($updated) {
                        error_log("Post $post_id scheduled for $datetime");
                        $results[] = [
                            'post_id' => $post_id,
                            'post_title' => $post-> post_title,
                            'datetime' => $datetime,
                            'success' => true,
                            'message' => "Post '$post->post_title' scheduled for $datetime."
                        ];
                    } else {
                        $results[] = [
                            'post_id' => $post_id,
                            'success' => false,
                            'message' => "Failed to schedule post #$post_id."
                        ];
                    }
                }

                wp_send_json(['success' => true, 'results' => $results]);
            } else {
                $post_id = intval($_POST['post_id']);
                $datetime = sanitize_text_field($_POST['datetime']);

                $post = get_post($post_id);
                if (!$post || $post->post_status !== 'draft') {
                    throw new Exception('Selected post is not a draft or does not exist.');
                }

                $updated = wp_update_post([
                    'ID' => $post_id,
                    'post_status' => 'future',
                    'post_date' => $datetime,
                    'post_date_gmt' => get_gmt_from_date($datetime),
                    'edit_date' => true,
                ]);

                if ($updated) {
                    error_log("Post $post_id scheduled for $datetime");
                    wp_send_json(['success' => true, 'message' => "Post '$post->post_title' scheduled for $datetime with ID $post_id."]);
                } else {
                    throw new Exception('Failed to schedule post.');
                }
            }
        } catch (Exception $e) {
            error_log("AJAX Schedule Post Error: " . $e->getMessage());
            wp_send_json(['success' => false, 'message' => 'Failed to schedule post(s): ' . $e->getMessage()]);
        }
    }

    public function ajax_generate_meta_description() {
        check_ajax_referer('ai_admin_boost_nonce', 'nonce');
        try {
            $post_id = intval($_POST['post_id'] ?? 0);
            if (!$post_id) {
                wp_send_json_error(['message' => 'Post ID is required.']);
            }

            $post = get_post($post_id);
            if (!$post) {
                wp_send_json_error(['message' => 'Post not found.']);
            }

            $ideation = new AI_Content_Ideation();
            $meta_description = $ideation->generate_meta_description($post->post_content, $post->post_title);

            wp_send_json_success([
                'meta_description' => $meta_description,
                'message' => 'Meta description generated successfully.'
            ]);
        } catch (Exception $e) {
            error_log("AJAX Generate Meta Description Error: " . $e->getMessage());
            wp_send_json_error(['message' => 'Failed to generate meta description: ' . $e->getMessage()]);
        }
    }

    public function ajax_analyze_content() {
        check_ajax_referer('ai_admin_boost_nonce', 'nonce');
        try {
            $post_id = intval($_POST['post_id'] ?? 0);
            if (!$post_id) {
                wp_send_json_error(['message' => 'Post ID is required.']);
            }

            $post = get_post($post_id);
            if (!$post) {
                wp_send_json_error(['message' => 'Post not found.']);
            }

            $ideation = new AI_Content_Ideation();
            $suggestions = $ideation->analyze_content($post->post_content);

            wp_send_json_success([
                'suggestions' => $suggestions,
                'message' => 'Content analyzed successfully.'
            ]);
        } catch (Exception $e) {
            error_log("AJAX Analyze Content Error: " . $e->getMessage());
            wp_send_json_error(['message' => 'Failed to analyze content: ' . $e->getMessage()]);
        }
    }

    public function ajax_get_scheduled_posts() {
        check_ajax_referer('ai_admin_boost_nonce', 'nonce');
        try {
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 5;

            $args = [
                'post_status' => 'future',
                'posts_per_page' => $per_page,
                'paged' => $page,
            ];
            $posts = get_posts($args);
            $total_posts = wp_count_posts('post', 'future')->future;

            $response = [
                'success' => true,
                'posts' => array_map(function($post) {
                    return [
                        'ID' => $post->ID,
                        'post_title' => $post->post_title,
                        'schedule_date' => get_the_date('Y-m-d H:i', $post->ID),
                    ];
                }, $posts),
                'has_more' => ($page * $per_page) < $total_posts,
            ];

            wp_send_json($response);
        } catch (Exception $e) {
            error_log("AJAX Get Scheduled Posts Error: " . $e->getMessage());
            wp_send_json(['success' => false, 'message' => 'Failed to load scheduled posts: ' . $e->getMessage()]);
        }
    }

    public function ajax_bulk_update_post_dates() {
        check_ajax_referer('ai_admin_boost_nonce', 'nonce');
        try {
            $updates = isset($_POST['updates']) && is_array($_POST['updates']) ? $_POST['updates'] : [];
            if (empty($updates)) {
                wp_send_json_error(['message' => 'No updates provided']);
                return;
            }

            $updated = 0;
            foreach ($updates as $update) {
                $post_id = intval($update['post_id'] ?? 0);
                $new_date = sanitize_text_field($update['new_date'] ?? '');

                if (!$post_id || !$new_date) {
                    continue;
                }

                $post = [
                    'ID' => $post_id,
                    'post_date' => $new_date,
                    'post_date_gmt' => get_gmt_from_date($new_date),
                    'edit_date' => true,
                ];

                if (wp_update_post($post) !== 0) { // Check if update was successful
                    $updated++;
                }
            }

            wp_send_json_success(['updated' => $updated]);
        } catch (Exception $e) {
            error_log("AJAX Bulk Update Post Dates Error: " . $e->getMessage());
            wp_send_json_error(['message' => 'Failed to update post dates: ' . $e->getMessage()]);
        }
    }
}

register_activation_hook(__FILE__, ['AI_Admin_Boost', 'activate']);
new AI_Admin_Boost();

require_once plugin_dir_path(__FILE__) . 'includes/class-ai-provider.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-content-ideation.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-seo-suggestions.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-task-automation.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-site-health.php';