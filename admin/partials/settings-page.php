<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap">
    <h1>AI Admin Boost</h1>
    <form method="post" action="options.php">
        <?php
        settings_fields('ai_admin_boost_options');
        do_settings_sections('ai-admin-boost');
        submit_button();
        ?>
    </form>

    <h2>Generate Blog Content</h2>
    <input type="text" id="blog_topic" placeholder="Enter a keyword">
    <button id="create_blog_post">Generate Blog Post</button>
    <div id="blog_output"></div>

    <h2>SEO Suggestions</h2>
    <textarea id="seo_content" placeholder="Paste content here"></textarea>
    <button id="suggest_seo">Get SEO Suggestions</button>
    <div id="seo_output"></div>

    <h2>SEO Analysis</h2>
    <select id="specific_page">
        <option value="">Select a page or post</option>
        <?php
        // Fetch pages
        $pages = get_pages();
        foreach ($pages as $page) {
            echo '<option value="' . $page->ID . '">[Page] ' . esc_html($page->post_title) . '</option>';
        }
        // Fetch published posts
        $posts = get_posts(['post_status' => 'publish', 'posts_per_page' => -1]);
        foreach ($posts as $post) {
            echo '<option value="' . $post->ID . '">[Post] ' . esc_html($post->post_title) . '</option>';
        }
        ?>
    </select>
    <button id="analyze_seo_specific">Analyze Content</button>
    <div id="seo_analysis_output"></div>

    <h2>Task Automation</h2>
    <label for="post_selection">Select a Draft Post to Schedule:</label>
    <select id="post_selection">
        <option value="">-- Select a Post --</option>
    </select>
    <label for="schedule_datetime">Schedule Date and Time:</label>
    <input type="datetime-local" id="schedule_datetime" required>
    <button id="generate_task">Run Task</button>
    <div id="task_output"></div>
</div>