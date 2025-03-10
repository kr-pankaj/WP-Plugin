<?php
if (!defined('ABSPATH')) exit;

class AI_Task_Automation {
    public function run_shortcut($shortcut) {
        switch (strtolower($shortcut)) {
            case preg_match('/create a category called (.*)/', $shortcut, $matches) ? true : false:
                $category_name = sanitize_text_field($matches[1]);
                $cat_id = wp_insert_category(['cat_name' => $category_name]);
                return $cat_id ? "Category '$category_name' created with ID $cat_id." : "Failed to create category.";
            case preg_match('/add a (.*) button/', $shortcut, $matches) ? true : false:
                $color = sanitize_text_field($matches[1]);
                $code = "<button style='background-color: $color; padding: 10px 20px; border: none; color: white; cursor: pointer;'>Click Me</button>";
                return "Code snippet generated:\n<pre>$code</pre>";
            default:
                return "Shortcut not recognized. Try 'Create a category called [name]' or 'Add a [color] button', or use the scheduling form to schedule a draft post.";
        }
    }
    public function get_draft_posts() {
        $post_types = get_post_types(['public' => true], 'names');
        $drafts = get_posts([
            'post_type' => $post_types,
            'post_status' => 'draft',
            'posts_per_page' => -1,
        ]);
        $result = [];
        foreach ($drafts as $draft) {
            $post_type_obj = get_post_type_object($draft->post_type);
            $result[] = [
                'ID' => $draft->ID,
                'post_title' => $draft->post_title ?: '(No title)',
                'post_type' => $post_type_obj->labels->singular_name,
            ];
        }
        return $result;
    }
}