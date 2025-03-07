jQuery(document).ready(function($) {
    // Fallback if ai_admin_boost is undefined
    if (typeof ai_admin_boost === 'undefined' || !ai_admin_boost.ajax_url) {
        console.error('AI Admin Boost: AJAX URL not defined. Ensure the plugin is properly initialized.');
        return;
    }

    $('form[action="options.php"]').on('submit', function(e) {
        return true;
    });

    // Populate draft posts dropdown
    function loadDraftPosts() {
        $.get(ai_admin_boost.ajax_url, {
            action: 'get_draft_posts'
        }, function(response) {
            const $select = $('#post_selection');
            $select.empty(); // Clear existing options
            $select.append('<option value="">-- Select a Post --</option>');
            $.each(response.posts, function(index, post) {
                $select.append(`<option value="${post.ID}">${post.post_title}</option>`);
            });
        }).fail(function(jqXHR) {
            $('#task_output').text('Error loading draft posts: ' + (jqXHR.responseJSON?.message || 'Server error.'));
        });
    }

    // Load drafts on page load
    loadDraftPosts();

    $('#create_blog_post').click(function(e) {
        e.preventDefault();
        const topic = $('#blog_topic').val();
        if (!topic) {
            $('#blog_output').text('Please enter a topic.');
            return;
        }
        $('#blog_output').text('Generating...');
        $.post(ai_admin_boost.ajax_url, {
            action: 'create_blog_post',
            topic: topic
        }, function(response) {
            if (response.success) {
                $('#blog_output').text('Post created and saved as draft. View it under Posts > All Posts.');
                loadDraftPosts(); // Refresh draft list
            } else {
                $('#blog_output').text('Error: ' + (response.message || 'Unknown error occurred.'));
            }
        }).fail(function(jqXHR) {
            $('#blog_output').text('Error creating blog post: ' + (jqXHR.responseJSON?.message || 'Server error.'));
        });
    });

    $('#suggest_seo').click(function(e) {
        e.preventDefault();
        const content = $('#seo_content').val();
        if (!content) {
            $('#seo_output').text('Please enter content to analyze.');
            return;
        }
        $('#seo_output').text('Generating SEO suggestions...');
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
            $('#seo_output').text('Error fetching SEO suggestions: ' + (jqXHR.responseJSON?.message || 'Server error.'));
        });
    });

    $('#analyze_seo_specific').click(function(e) {
        e.preventDefault();
        const page_id = $('#specific_page').val();
        if (!page_id) {
            alert('Please select a page.');
            return;
        }
        $('#seo_analysis_output').text('Analyzing...');
        $.post(ai_admin_boost.ajax_url, {
            action: 'analyze_seo_specific',
            page_id: page_id
        }, function(response) {
            if (response.html) {
                $('#seo_analysis_output').text(response.html);
            } else {
                $('#seo_analysis_output').text('Error: No analysis returned.');
            }
        }).fail(function(jqXHR) {
            $('#seo_analysis_output').text('Error analyzing page: ' + (jqXHR.responseJSON?.message || 'Server error.'));
        });
    });

    $('#generate_task').click(function(e) {
        e.preventDefault();
        const postId = $('#post_selection').val();
        const datetime = $('#schedule_datetime').val();

        if (!postId) {
            $('#task_output').text('Please select a draft post.');
            return;
        }
        if (!datetime) {
            $('#task_output').text('Please select a date and time.');
            return;
        }

        $('#task_output').text('Scheduling...'); // Feedback during execution
        $.post(ai_admin_boost.ajax_url, {
            action: 'schedule_post',
            post_id: postId,
            datetime: datetime
        }, function(response) {
            $('#task_output').text(response.message || response);
            if (response.success) {
                loadDraftPosts(); // Refresh draft list after scheduling
            }
        }).fail(function(jqXHR) {
            $('#task_output').text('Error scheduling post: ' + (jqXHR.responseJSON?.message || 'Server error.'));
        });
    });

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