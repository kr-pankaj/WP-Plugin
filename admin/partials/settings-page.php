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
    <button id="generate_blog_content">Generate Blog Post</button>
    <div id="blog_output"></div>

    <h2>SEO Suggestions</h2>
    <textarea id="seo_content" placeholder="Paste content here"></textarea>
    <button id="suggest_seo">Get SEO Suggestions</button>
    <div id="seo_output"></div>

    <h2>SEO Analysis</h2>
    <!-- <button id="analyze_seo_all">Analyze All Pages</button> -->
    <select id="specific_page">
        <option value="">Select a page</option>
        <?php
        $pages = get_pages();
        foreach ($pages as $page) {
            echo '<option value="' . $page->ID . '">' . esc_html($page->post_title) . '</option>';
        }
        ?>
    </select>
    <button id="analyze_seo_specific">Analyze Page</button>
    <div id="seo_analysis_output"></div>

    <h2>Task Automation</h2>
    <input type="text" id="task_description" placeholder="E.g., Schedule a post for next week or Create a category called Technology">
    <button id="generate_task">Run Task</button>
    <div id="task_output"></div>
</div><?php if (!defined('ABSPATH')) exit; ?>
