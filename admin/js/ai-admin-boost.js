jQuery(document).ready(function($) {
    // Fallback if ai_admin_boost is undefined
    if (typeof ai_admin_boost === 'undefined' || !ai_admin_boost.ajax_url) {
        console.error('AI Admin Boost: AJAX URL not defined. Ensure the plugin is properly initialized.');
        return;
    }

    $('form[action="options.php"]').on('submit', function(e) {
        return true;
    });

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
        $.post(ai_admin_boost.ajax_url, {
            action: 'suggest_seo',
            content: content
        }, function(response) {
            $('#seo_output').text(response);
        }).fail(function() {
            $('#seo_output').text('Error fetching SEO suggestions.');
        });
    });

    $('#analyze_seo_all').click(function(e) {
        e.preventDefault();
        $.post(ai_admin_boost.ajax_url, {
            action: 'analyze_seo_all'
        }, function(response) {
            $('#seo_analysis_output').text(response);
        }).fail(function() {
            $('#seo_analysis_output').text('Error analyzing pages.');
        });
    });

    $('#analyze_seo_specific').click(function(e) {
        e.preventDefault();
        const page_id = $('#specific_page').val();
        if (!page_id) return alert('Please select a page.');
        $.post(ai_admin_boost.ajax_url, {
            action: 'analyze_seo_specific',
            page_id: page_id
        }, function(response) {
            $('#seo_analysis_output').text(response);
        }).fail(function() {
            $('#seo_analysis_output').text('Error analyzing page.');
        });
    });

    $('#generate_task').click(function(e) {
        e.preventDefault();
        const task = $('#task_description').val().trim();
        if (!task) {
            $('#task_output').text('Please enter a task description.');
            return;
        }
        $('#task_output').text('Processing...'); // Feedback during execution
        $.post(ai_admin_boost.ajax_url, {
            action: 'run_admin_shortcut',
            shortcut: task
        }, function(response) {
            $('#task_output').text(response.message || response); // Display the result
        }).fail(function(jqXHR) {
            $('#task_output').text('Error running task: ' + (jqXHR.responseJSON?.message || 'Server error.'));
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