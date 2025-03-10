jQuery(document).ready(function($) {
    // Fallback if ai_admin_boost is undefined
    if (typeof ai_admin_boost === 'undefined' || !ai_admin_boost.ajax_url) {
        console.error('AI Admin Boost: AJAX URL not defined. Ensure the plugin is properly initialized.');
        return;
    }

    // Tab switching
    $('.ai-tab').click(function() {
        $('.ai-tab').removeClass('active');
        $(this).addClass('active');
        
        const tabId = $(this).data('tab');
        $('.ai-tab-pane').removeClass('active');
        $('#' + tabId).addClass('active');
    });

    // Populate draft posts dropdown
    function loadDraftPosts() {
        $.get(ai_admin_boost.ajax_url, {
            action: 'get_draft_posts'
        }, function(response) {
            const $select = $('#post_selection');
            $select.empty();
            $select.append('<option value="">-- Select Content --</option>');
            if (response.success) {
                $.each(response.posts, function(index, post) {
                    const postType = post.post_type || 'Unknown'; // Fallback if post_type is missing
                    $select.append(`<option value="${post.ID}">[${postType}] ${post.post_title || '(No title)'}</option>`);
                });
            } else {
                $('#task_output').text('Error loading draft posts: ' + (response.message || 'Unknown error'));
            }
        }).fail(function(jqXHR) {
            $('#task_output').text('Error loading draft posts: ' + (jqXHR.responseJSON?.message || 'Server error'));
        });
    }

    // Load drafts when Automation tab is active
    if ($('#post_selection').length) {
        loadDraftPosts();
    }

    // Generate blog post (single)
    $('#create_blog_post').click(function(e) {
        e.preventDefault();
        const topic = $('#blog_topic').val();
        if (!topic) {
            $('#blog_output').text('Please enter a topic.');
            return;
        }
        $('#blog_output').text('Generating...');
        $(this).addClass('loading');
        $.post(ai_admin_boost.ajax_url, {
            action: 'create_blog_post',
            topic: topic
        }, function(response) {
            if (response.success) {
                $('#blog_output').text('Post created and saved as draft. View it under Posts > All Posts.');
                loadDraftPosts(); // Refresh draft list
            } else {
                $('#blog_output').text('Error: ' + (response.data?.message || 'Unknown error occurred.'));
            }
        }).fail(function(jqXHR) {
            $('#blog_output').text('Error creating blog post: ' + (jqXHR.responseJSON?.data?.message || 'Server error'));
        }).always(function() {
            $('#create_blog_post').removeClass('loading');
        });
    });

    // Add more topic input fields
    $('#ai-bulk-add-more').click(function(e) {
        e.preventDefault();
        const rowCount = $('.ai-bulk-topic-row').length + 1;
        $('.ai-bulk-topics').append(`
            <div class="ai-bulk-topic-row">
                <input type="text" class="ai-bulk-topic-input" placeholder="Enter topic ${rowCount}">
            </div>
        `);
    });

    // Handle CSV upload and populate topics
    $('#ai-bulk-csv').change(function(e) {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function(event) {
            const text = event.target.result;
            const topics = text.split('\n').map(line => line.trim()).filter(line => line);
            $('.ai-bulk-topics').empty();
            topics.forEach((topic, index) => {
                $('.ai-bulk-topics').append(`
                    <div class="ai-bulk-topic-row">
                        <input type="text" class="ai-bulk-topic-input" value="${topic}" placeholder="Enter topic ${index + 1}">
                    </div>
                `);
            });
        };
        reader.readAsText(file);
    });

    // Bulk generate posts
    $('#ai-bulk-generate').click(function(e) {
        e.preventDefault();

        // Collect topics from input fields
        const topics = $('.ai-bulk-topic-input')
            .map(function() { return $(this).val().trim(); })
            .get()
            .filter(topic => topic);

        if (!topics.length) {
            $('#ai-bulk-output').text('Please enter at least one topic.');
            return;
        }

        $('#ai-bulk-output').text('');
        $(this).addClass('loading');

        // Process topics sequentially
        let generatedCount = 0;
        const totalTopics = topics.length;

        function generateNextTopic(index) {
            if (index >= totalTopics) {
                $('#ai-bulk-output').text(`All ${totalTopics} posts generated and saved as drafts. View them under Posts > All Posts.`);
                $('#ai-bulk-generate').removeClass('loading');
                loadDraftPosts(); // Refresh draft list for Automation tab
                return;
            }

            const topic = topics[index];
            $('#ai-bulk-output').text(`Generating post ${index + 1} of ${totalTopics}...`);

            $.post(ai_admin_boost.ajax_url, {
                action: 'create_blog_post',
                topic: topic
            }, function(response) {
                if (response.success) {
                    generatedCount++;
                    $('#ai-bulk-output').text(`${generatedCount} post${generatedCount !== 1 ? 's' : ''} generated...`);
                    generateNextTopic(index + 1); // Process next topic
                } else {
                    $('#ai-bulk-output').text(`Error generating post ${index + 1}: ${response.data?.message || 'Unknown error'}`);
                    $('#ai-bulk-generate').removeClass('loading');
                }
            }).fail(function(jqXHR) {
                $('#ai-bulk-output').text(`Error generating post ${index + 1}: ${jqXHR.responseJSON?.data?.message || 'Server error'}`);
                $('#ai-bulk-generate').removeClass('loading');
            });
        }

        generateNextTopic(0); // Start with the first topic
    });

    // Suggest SEO
    $('#suggest_seo').click(function(e) {
        e.preventDefault();
        const content = $('#seo_content').val();
        if (!content) {
            $('#seo_output').text('Please enter content to analyze.');
            return;
        }
        $('#seo_output').text('Generating SEO suggestions...');
        $(this).addClass('loading');
        $.post(ai_admin_boost.ajax_url, {
            action: 'suggest_seo',
            content: content
        }, function(response) {
            if (response.html) {
                $('#seo_output').text(response.html);
            } else {
                $('#seo_output').text('Error: No suggestions returned.');
            }
        }).fail(function(jqXHR) {
            $('#seo_output').text('Error fetching SEO suggestions: ' + (jqXHR.responseJSON?.message || 'Server error'));
        }).always(function() {
            $('#suggest_seo').removeClass('loading');
        });
    });

    // Analyze SEO specific
    $('#analyze_seo_specific').click(function(e) {
        e.preventDefault();
        const post_id = $('#specific_page').val();
        if (!post_id) {
            alert('Please select content.');
            return;
        }
        $('#seo_analysis_output').text('Analyzing...');
        $(this).addClass('loading');
        $.post(ai_admin_boost.ajax_url, {
            action: 'analyze_seo_specific',
            page_id: post_id
        }, function(response) {
            if (response.html) {
                $('#seo_analysis_output').text(response.html);
            } else {
                $('#seo_analysis_output').text('Error: No analysis returned.');
            }
        }).fail(function(jqXHR) {
            $('#seo_analysis_output').text('Error analyzing content: ' + (jqXHR.responseJSON?.message || 'Server error'));
        }).always(function() {
            $('#analyze_seo_specific').removeClass('loading');
        });
    });

    // Schedule post
    $('#generate_task').click(function(e) {
        e.preventDefault();
        const postId = $('#post_selection').val();
        const datetime = $('#schedule_datetime').val();

        if (!postId) {
            $('#task_output').text('Please select draft content.');
            return;
        }
        if (!datetime) {
            $('#task_output').text('Please select a date and time.');
            return;
        }

        $('#task_output').text('Scheduling...');
        $(this).addClass('loading');
        $.post(ai_admin_boost.ajax_url, {
            action: 'schedule_post',
            post_id: postId,
            datetime: datetime
        }, function(response) {
            $('#task_output').text(response.message || response);
            if (response.success) {
                loadDraftPosts(); // Refresh draft list
            }
        }).fail(function(jqXHR) {
            $('#task_output').text('Error scheduling post: ' + (jqXHR.responseJSON?.message || 'Server error'));
        }).always(function() {
            $('#generate_task').removeClass('loading');
        });
    });

    // Run admin shortcut (if applicable)
    $('#run_admin_shortcut').click(function(e) {
        e.preventDefault();
        const shortcut = $('#admin_shortcut').val();
        $.post(ai_admin_boost.ajax_url, {
            action: 'run_admin_shortcut',
            shortcut: shortcut
        }, function(response) {
            $('#shortcut_output').text(response);
        }).fail(function() {
            $('#shortcut_output').text('Error running shortcut.');
        });
    });
});