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
            <input type="text" id="blog_topic" placeholder="Enter a keyword" title="Enter a topic or keyword to generate a blog post (e.g., 'AI Trends 2025')">
            <button id="create_blog_post">Generate Blog Post</button>
            <div class="ai-spinner" style="display: none;">Loading...</div>
            <div id="blog_output"></div>
            <div id="blog_confirmation" class="confirmation-message"></div>

            <h3>Bulk Post Generation</h3>
            <div class="ai-bulk-post-form">
                <div class="ai-bulk-topics">
                    <!-- Default 5 input fields -->
                    <div class="ai-bulk-topic-row">
                        <input type="text" class="ai-bulk-topic-input" placeholder="Enter topic 1" title="Enter a topic for bulk post generation (e.g., 'SEO Tips')">
                    </div>
                    <div class="ai-bulk-topic-row">
                        <input type="text" class="ai-bulk-topic-input" placeholder="Enter topic 2" title="Enter a topic for bulk post generation (e.g., 'WordPress Security')">
                    </div>
                    <div class="ai-bulk-topic-row">
                        <input type="text" class="ai-bulk-topic-input" placeholder="Enter topic 3" title="Enter a topic for bulk post generation (e.g., 'AI in 2025')">
                    </div>
                    <div class="ai-bulk-topic-row">
                        <input type="text" class="ai-bulk-topic-input" placeholder="Enter topic 4" title="Enter a topic for bulk post generation (e.g., 'Blogging Tips')">
                    </div>
                    <div class="ai-bulk-topic-row">
                        <input type="text" class="ai-bulk-topic-input" placeholder="Enter topic 5" title="Enter a topic for bulk post generation (e.g., 'Content Strategy')">
                    </div>
                </div>
                <button id="ai-bulk-add-more" class="ai-add-more-btn">Add More</button>
                
                <h4>Or Import CSV</h4>
                <input type="file" id="ai-bulk-csv" accept=".csv" title="Upload a CSV file with one topic per line (e.g., 'SEO Tips\nWordPress Security')">
                <p><small>CSV should have one column with topics (e.g., "SEO Tips,WordPress Security,AI in 2025").</small></p>
                
                <button id="ai-bulk-generate" class="ai-generate-btn">Generate Posts</button>
                <div class="ai-spinner" style="display: none;">Loading...</div>
                <div id="ai-bulk-output"></div>
                <div id="bulk_confirmation" class="confirmation-message"></div>
            </div>
        </div>

        <!-- SEO Tab -->
        <div id="seo" class="ai-tab-pane">
            <h2>SEO Suggestions</h2>
            <textarea id="seo_content" placeholder="Paste content here" title="Paste the content you want SEO suggestions for"></textarea>
            <button id="suggest_seo">Get SEO Suggestions</button>
            <div class="ai-spinner" style="display: none;">Loading...</div>
            <div id="seo_output"></div>
            <div id="seo_confirmation" class="confirmation-message"></div>

            <h2>SEO Analysis</h2>
            <select id="specific_page" title="Select a published post or page to analyze its SEO">
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
            <div class="ai-spinner" style="display: none;">Loading...</div>
            <div id="seo_analysis_output"></div>
            <div id="seo_analysis_confirmation" class="confirmation-message"></div>
        </div>

        <!-- Automation Tab -->
        <div id="automation" class="ai-tab-pane">
            <h2>Task Automation</h2>
            <div class="ai-schedule-posts">
                <!-- Default 1 scheduling field group -->
                <div class="ai-schedule-post-row" data-row-id="1">
                    <label for="post_selection_1">Select a Draft to Schedule:</label>
                    <select class="post_selection" name="post_selection[]" title="Select a draft post to schedule">
                        <option value="">-- Select Content --</option>
                    </select>
                    <label for="schedule_datetime_1">Schedule Date and Time:</label>
                    <input type="datetime-local" class="schedule_datetime" name="schedule_datetime[]" required title="Select the date and time to schedule the post">
                    <button class="ai-remove-row hidden" data-row-id="1">Remove</button>
                </div>
            </div>
            <button id="ai-schedule-add-more" class="ai-add-more-btn">Add More</button>
            <button id="generate_task">Schedule Posts</button>
            <div class="ai-spinner" style="display: none;">Loading...</div>
            <div id="task_output"></div>
            <div id="task_confirmation" class="confirmation-message"></div>

            <!-- Bulk Edit Scheduled Posts -->
            <h2>Bulk Edit Scheduled Posts</h2>
            <div id="bulk_edit_section">
                <div class="ai-spinner" style="display: none;" id="bulk_edit_spinner">Loading...</div>
                <div class="ai-pagination-controls">
                    <label for="posts_per_page">Posts per page:</label>
                    <select id="posts_per_page">
                        <option value="5">5</option>
                        <option value="10">10</option>
                        <option value="20">20</option>
                    </select>
                </div>
                <div id="scheduled_posts_list">
                    <!-- Posts will be dynamically loaded here -->
                </div>
                <button id="bulk_load_more" style="display: none;">Load More</button>
                <button id="bulk_update_dates" style="display: none;">Update Selected Posts</button>
                <div id="bulk_edit_confirmation" class="confirmation-message"></div>
            </div>
        </div>
    </div>
</div>