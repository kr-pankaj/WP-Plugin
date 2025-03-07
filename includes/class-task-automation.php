<?php
if (!defined('ABSPATH')) exit;

class AI_Task_Automation {
    public function run_shortcut($shortcut) {
        switch (strtolower($shortcut)) {
            case 'schedule a post for next week':
                $post_id = wp_insert_post([
                    'post_title'   => 'Scheduled Post',
                    'post_content' => 'This is a placeholder post scheduled by AI Admin Boost.',
                    'post_status'  => 'future',
                    'post_date'    => date('Y-m-d H:i:s', strtotime('+1 week')),
                    'post_type'    => 'post',
                ]);
                return $post_id ? "Post scheduled with ID $post_id." : "Failed to schedule post.";
            case preg_match('/create a category called (.*)/', $shortcut, $matches) ? true : false:
                $category_name = $matches[1];
                $cat_id = wp_insert_category(['cat_name' => $category_name]);
                return $cat_id ? "Category '$category_name' created with ID $cat_id." : "Failed to create category.";
            case preg_match('/add a (.*) button/', $shortcut, $matches) ? true : false:
                $color = sanitize_text_field($matches[1]);
                $code = "<button style='background-color: $color; padding: 10px 20px; border: none; color: white; cursor: pointer;'>Click Me</button>";
                return "Code snippet generated:\n<pre>$code</pre>";
            default:
                return "Shortcut not recognized. Try 'Schedule a post for next week', 'Create a category called [name]', or 'Add a [color] button'.";
        }
    }
}