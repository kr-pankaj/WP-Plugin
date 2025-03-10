<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap ai-admin-boost-wrap">
    <h1>AI Admin Boost</h1>

    <!-- Tab Navigation -->
    <ul class="ai-tabs">
        <li class="ai-tab active" data-tab="settings">Settings</li>
        <li class="ai-tab" data-tab="content">Content Generation</li>
        <li class="ai-tab" data-tab="seo">SEO</li>
        <li class="ai-tab" data-tab="automation">Automation</li>
    </ul>

    <!-- Tab Content -->
    <div class="ai-tab-content">
        <!-- Settings Tab -->
        <div id="settings" class="ai-tab-pane active">
            <form method="post" action="options.php">
                <?php
                settings_fields('ai_admin_boost_options');
                do_settings_sections('ai-admin-boost');
                submit_button();
                ?>
            </form>
        </div>

        <!-- Content Generation Tab -->
        <div id="content" class="ai-tab-pane">
            <h2>Generate Blog Content</h2>
            <h3>Single Post</h3>
            <input type="text" id="blog_topic" placeholder="Enter a keyword">
            <button id="create_blog_post">Generate Blog Post</button>
            <div class="ai-spinner" style="display: none;">Loading...</div> <!-- Added spinner -->
            <div id="blog_output"></div>

            <h3>Bulk Post Generation</h3>
            <div class="ai-bulk-post-form">
                <div class="ai-bulk-topics">
                    <!-- Default 5 input fields -->
                    <div class="ai-bulk-topic-row">
                        <input type="text" class="ai-bulk-topic-input" placeholder="Enter topic 1">
                    </div>
                    <div class="ai-bulk-topic-row">
                        <input type="text" class="ai-bulk-topic-input" placeholder="Enter topic 2">
                    </div>
                    <div class="ai-bulk-topic-row">
                        <input type="text" class="ai-bulk-topic-input" placeholder="Enter topic 3">
                    </div>
                    <div class="ai-bulk-topic-row">
                        <input type="text" class="ai-bulk-topic-input" placeholder="Enter topic 4">
                    </div>
                    <div class="ai-bulk-topic-row">
                        <input type="text" class="ai-bulk-topic-input" placeholder="Enter topic 5">
                    </div>
                </div>
                <button id="ai-bulk-add-more" class="ai-add-more-btn">Add More</button>
                
                <h4>Or Import CSV</h4>
                <input type="file" id="ai-bulk-csv" accept=".csv">
                <p><small>CSV should have one column with topics (e.g., "SEO Tips,WordPress Security,AI in 2025").</small></p>
                
                <button id="ai-bulk-generate" class="ai-generate-btn">Generate Posts</button>
                <div class="ai-spinner" style="display: none;">Loading...</div> <!-- Ensure spinner is present -->
                <div id="ai-bulk-output"></div>
            </div>
        </div>

        <!-- SEO Tab -->
        <div id="seo" class="ai-tab-pane">
            <h2>SEO Suggestions</h2>
            <textarea id="seo_content" placeholder="Paste content here"></textarea>
            <button id="suggest_seo">Get SEO Suggestions</button>
            <div id="seo_output"></div>

            <h2>SEO Analysis</h2>
            <select id="specific_page">
                <option value="">Select content</option>
                <?php
                $post_types = get_post_types(['public' => true], 'objects');
                foreach ($post_types as $post_type) {
                    $items = get_posts([
                        'post_type' => $post_type->name,
                        'post_status' => 'publish',
                        'posts_per_page' => -1,
                    ]);
                    foreach ($items as $item) {
                        echo '<option value="' . $item->ID . '">[' . $post_type->labels->singular_name . '] ' . esc_html($item->post_title) . '</option>';
                    }
                }
                ?>
            </select>
            <button id="analyze_seo_specific">Analyze Content</button>
            <div id="seo_analysis_output"></div>
        </div>

        <!-- Automation Tab -->
        <div id="automation" class="ai-tab-pane">
            <h2>Task Automation</h2>
            <label for="post_selection">Select a Draft to Schedule:</label>
            <select id="post_selection">
                <option value="">-- Select Content --</option>
            </select>
            <label for="schedule_datetime">Schedule Date and Time:</label>
            <input type="datetime-local" id="schedule_datetime" required>
            <button id="generate_task">Run Task</button>
            <div id="task_output"></div>
        </div>
    </div>
</div>