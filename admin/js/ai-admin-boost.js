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
            const $selects = $('.post_selection');
            $selects.each(function() {
                const $select = $(this);
                const currentValue = $select.val(); // Preserve selected value if any
                $select.empty();
                $select.append('<option value="">-- Select Content --</option>');
                if (response.success) {
                    $.each(response.posts, function(index, post) {
                        const postType = post.post_type || 'Unknown';
                        $select.append(`<option value="${post.ID}">[${postType}] ${post.post_title || '(No title)'}</option>`);
                    });
                    // Restore the selected value if it still exists
                    if (currentValue) {
                        $select.val(currentValue);
                    }
                } else {
                    $('#task_output').html('<div>Error loading draft posts: ' + (response.message || 'Unknown error') + '</div>');
                }
            });
        }).fail(function(jqXHR) {
            $('#task_output').html('<div>Error loading draft posts: ' + (jqXHR.responseJSON?.message || 'Server error') + '</div>');
        });
    }

    // Load drafts when Automation tab is active
    if ($('.post_selection').length) {
        loadDraftPosts();
    }

    // Function to toggle visibility of Remove buttons
    function toggleRemoveButtons() {
        const rowCount = $('.ai-schedule-post-row').length;
        if (rowCount > 1) {
            $('.ai-remove-row').removeClass('hidden');
        } else {
            $('.ai-remove-row').addClass('hidden');
        }
    }

    // Add more scheduling rows
    $('#ai-schedule-add-more').click(function(e) {
        e.preventDefault();
        const rowCount = $('.ai-schedule-post-row').length + 1;
        $('.ai-schedule-posts').append(`
            <div class="ai-schedule-post-row" data-row-id="${rowCount}">
                <label for="post_selection_${rowCount}">Select a Draft to Schedule:</label>
                <select class="post_selection" name="post_selection[]">
                    <option value="">-- Select Content --</option>
                </select>
                <label for="schedule_datetime_${rowCount}">Schedule Date and Time:</label>
                <input type="datetime-local" class="schedule_datetime" name="schedule_datetime[]" required>
                <button class="ai-remove-row" data-row-id="${rowCount}">Remove</button>
            </div>
        `);
        // Populate the new dropdown with draft posts
        loadDraftPosts();
        // Toggle Remove buttons visibility
        toggleRemoveButtons();
    });

    // Remove a scheduling row
    $(document).on('click', '.ai-remove-row', function(e) {
        e.preventDefault();
        const $row = $(this).closest('.ai-schedule-post-row');
        const rowId = $row.data('row-id');
        
        // Confirm removal (optional, can be removed for simplicity)
        if (confirm('Are you sure you want to remove this row?')) {
            $row.remove();
            
            // Re-index remaining rows (optional, for cosmetic consistency)
            $('.ai-schedule-post-row').each(function(index) {
                const newIndex = index + 1;
                $(this).attr('data-row-id', newIndex);
                $(this).find('label[for="post_selection_' + (index + 1) + '"]').attr('for', 'post_selection_' + newIndex);
                $(this).find('label[for="schedule_datetime_' + (index + 1) + '"]').attr('for', 'schedule_datetime_' + newIndex);
                $(this).find('.post_selection').attr('id', 'post_selection_' + newIndex).attr('name', 'post_selection[]');
                $(this).find('.schedule_datetime').attr('id', 'schedule_datetime_' + newIndex).attr('name', 'schedule_datetime[]');
                $(this).find('.ai-remove-row').attr('data-row-id', newIndex).text('Remove');
            });

            // Toggle Remove buttons visibility
            toggleRemoveButtons();
        }
    });

    // Reset the scheduling form to its initial state
    function resetScheduleForm() {
        // Keep only the first row
        $('.ai-schedule-post-row').slice(1).remove();
        
        // Reset the first row
        const $firstRow = $('.ai-schedule-post-row').first();
        $firstRow.attr('data-row-id', '1');
        $firstRow.find('label[for="post_selection_1"]').attr('for', 'post_selection_1');
        $firstRow.find('label[for="schedule_datetime_1"]').attr('for', 'schedule_datetime_1');
        $firstRow.find('.post_selection').attr('id', 'post_selection_1').attr('name', 'post_selection[]').val('');
        $firstRow.find('.schedule_datetime').attr('id', 'schedule_datetime_1').attr('name', 'schedule_datetime[]').val('');
        $firstRow.find('.ai-remove-row').attr('data-row-id', '1').text('Remove');

        // Re-populate the dropdown
        loadDraftPosts();

        // Toggle Remove buttons visibility
        toggleRemoveButtons();
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

        let generatedCount = 0;
        const totalTopics = topics.length;

        function generateNextTopic(index) {
            if (index >= totalTopics) {
                $('#ai-bulk-output').text(`All ${totalTopics} posts generated and saved as drafts. View them under Posts > All Posts.`);
                $('#ai-bulk-generate').removeClass('loading');
                loadDraftPosts();
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
                    generateNextTopic(index + 1);
                } else {
                    $('#ai-bulk-output').text(`Error generating post ${index + 1}: ${response.data?.message || 'Unknown error'}`);
                    $('#ai-bulk-generate').removeClass('loading');
                }
            }).fail(function(jqXHR) {
                $('#ai-bulk-output').text(`Error generating post ${index + 1}: ${jqXHR.responseJSON?.data?.message || 'Server error'}`);
                $('#ai-bulk-generate').removeClass('loading');
            });
        }

        generateNextTopic(0);
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

    // Schedule multiple posts
    $('#generate_task').click(function(e) {
        e.preventDefault();

        // Collect all post IDs and datetimes
        const schedules = [];
        let hasError = false;

        // Check for duplicate posts
        const selectedPosts = $('.post_selection')
            .map(function() { return $(this).val(); })
            .get()
            .filter(val => val); // Filter out empty values

        const duplicates = selectedPosts.filter((item, index) => selectedPosts.indexOf(item) !== index);
        if (duplicates.length > 0) {
            // Find the titles of duplicate posts for the error message
            const duplicateTitles = [];
            const postTitles = {};
            $('.post_selection').each(function() {
                const postId = $(this).val();
                if (postId && duplicates.includes(postId)) {
                    const title = $(this).find('option:selected').text();
                    if (!duplicateTitles.includes(title)) {
                        duplicateTitles.push(title);
                    }
                }
            });
            $('#task_output').html(`<div>Error: You have selected the same post(s) multiple times: ${duplicateTitles.join(', ')}. Please select unique posts for each row.</div>`);
            return;
        }

        $('.ai-schedule-post-row').each(function(index) {
            const postId = $(this).find('.post_selection').val();
            const datetime = $(this).find('.schedule_datetime').val();

            if (!postId) {
                $('#task_output').html(`<div>Please select draft content for row ${index + 1}.</div>`);
                hasError = true;
                return false; // Exit loop
            }
            if (!datetime) {
                $('#task_output').html(`<div>Please select a date and time for row ${index + 1}.</div>`);
                hasError = true;
                return false; // Exit loop
            }

            schedules.push({ post_id: postId, datetime: datetime });
        });

        if (hasError || schedules.length === 0) {
            return;
        }

        $('#task_output').text('Scheduling posts...');
        $(this).addClass('loading');

        $.post(ai_admin_boost.ajax_url, {
            action: 'schedule_post',
            schedules: schedules
        }, function(response) {
            if (response.success) {
                let output = '';
                $.each(response.results, function(index, result) {
                    if (result.success) {
                        output += `<div>Post ${result.post_title} scheduled for ${result.datetime} with ID ${result.post_id}.</div>`;
                    } else {
                        output += `<div>Error scheduling post ID ${result.post_id}: ${result.message}</div>`;
                    }
                });
                $('#task_output').html(output);
                loadDraftPosts(); // Refresh draft list
                resetScheduleForm(); // Reset the form after successful scheduling
            } else {
                $('#task_output').text('Error: ' + (response.message || 'Unknown error occurred.'));
            }
        }).fail(function(jqXHR) {
            $('#task_output').text('Error scheduling posts: ' + (jqXHR.responseJSON?.message || 'Server error'));
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