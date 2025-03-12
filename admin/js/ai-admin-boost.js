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

        // Load scheduled posts when Automation tab is activated
        if (tabId === 'automation') {
            loadScheduledPosts(1);
        }
    });

    // Populate draft posts dropdown
    function loadDraftPosts() {
        $.ajax({
            url: ai_admin_boost.ajax_url,
            type: 'GET',
            data: {
                action: 'get_draft_posts',
                nonce: ai_admin_boost.nonce
            },
            success: function(response) {
                const $selects = $('.post_selection');
                $selects.each(function() {
                    const $select = $(this);
                    const currentValue = $select.val();
                    $select.empty();
                    $select.append('<option value="">-- Select Content --</option>');
                    if (response.success) {
                        $.each(response.posts, function(index, post) {
                            const postType = post.post_type || 'Unknown';
                            $select.append(`<option value="${post.ID}">[${postType}] ${post.post_title || '(No title)'}</option>`);
                        });
                        if (currentValue) {
                            $select.val(currentValue);
                        }
                    } else {
                        $('#task_output').html('<div>Error loading draft posts: ' + (response.message || 'Unknown error') + '</div>');
                    }
                    updateDropdownOptions();
                });
            },
            error: function(jqXHR) {
                $('#task_output').html('<div>Error loading draft posts: ' + (jqXHR.responseJSON?.message || 'Server error') + '</div>');
            }
        });
    }

    if ($('.post_selection').length) {
        loadDraftPosts();
    }

    function toggleRemoveButtons() {
        const rowCount = $('.ai-schedule-post-row').length;
        if (rowCount > 1) {
            $('.ai-remove-row').removeClass('hidden');
        } else {
            $('.ai-remove-row').addClass('hidden');
        }
    }

    function showConfirmation(message, containerId, duration = 3000) {
        const $container = $(`#${containerId}`);
        $container.text(message).addClass('show');
        setTimeout(() => {
            $container.removeClass('show');
        }, duration);
    }

    function updateDropdownOptions() {
        const selectedValues = $('.post_selection').map(function() {
            return $(this).val();
        }).get();

        $('.post_selection').each(function() {
            const $select = $(this);
            const currentValue = $select.val();
            $select.find('option').prop('disabled', false);
            $.each(selectedValues, function(index, value) {
                if (value && value !== currentValue) {
                    $select.find(`option[value="${value}"]`).prop('disabled', true);
                }
            });
        });
    }

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
        loadDraftPosts();
        toggleRemoveButtons();
        updateDropdownOptions();
    });

    $(document).on('click', '.ai-remove-row', function(e) {
        e.preventDefault();
        const $row = $(this).closest('.ai-schedule-post-row');
        const rowId = $row.data('row-id');
        
        if (confirm('Are you sure you want to remove this row?')) {
            $row.remove();
            
            $('.ai-schedule-post-row').each(function(index) {
                const newIndex = index + 1;
                $(this).attr('data-row-id', newIndex);
                $(this).find('label[for="post_selection_' + (index + 1) + '"]').attr('for', 'post_selection_' + newIndex);
                $(this).find('label[for="schedule_datetime_' + (index + 1) + '"]').attr('for', 'schedule_datetime_' + newIndex);
                $(this).find('.post_selection').attr('id', 'post_selection_' + newIndex).attr('name', 'post_selection[]');
                $(this).find('.schedule_datetime').attr('id', 'schedule_datetime_' + newIndex).attr('name', 'schedule_datetime[]');
                $(this).find('.ai-remove-row').attr('data-row-id', newIndex).text('Remove');
            });

            toggleRemoveButtons();
            updateDropdownOptions();
        }
    });

    function resetScheduleForm() {
        $('.ai-schedule-post-row').slice(1).remove();
        
        const $firstRow = $('.ai-schedule-post-row').first();
        $firstRow.attr('data-row-id', '1');
        $firstRow.find('label[for="post_selection_1"]').attr('for', 'post_selection_1');
        $firstRow.find('label[for="schedule_datetime_1"]').attr('for', 'schedule_datetime_1');
        $firstRow.find('.post_selection').attr('id', 'post_selection_1').attr('name', 'post_selection[]').val('');
        $firstRow.find('.schedule_datetime').attr('id', 'schedule_datetime_1').attr('name', 'schedule_datetime[]').val('');
        $firstRow.find('.ai-remove-row').attr('data-row-id', '1').text('Remove');

        loadDraftPosts();
        toggleRemoveButtons();
        updateDropdownOptions();
    }

    $(document).on('change', '.post_selection', function() {
        updateDropdownOptions();
    });

    $('#create_blog_post').click(function(e) {
        e.preventDefault();
        const topic = $('#blog_topic').val();
        if (!topic) {
            $('#blog_output').text('Please enter a topic.');
            return;
        }
        $('#blog_output').text('Generating...');
        $(this).addClass('loading');
        $.ajax({
            url: ai_admin_boost.ajax_url,
            type: 'POST',
            data: {
                action: 'create_blog_post',
                topic: topic,
                nonce: ai_admin_boost.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#blog_output').text('Post created and saved as draft. View it under Posts > All Posts.');
                    showConfirmation('Post generated successfully!', 'blog_confirmation');
                    loadDraftPosts();
                } else {
                    $('#blog_output').text('Error: ' + (response.data?.message || 'Unknown error occurred.'));
                }
            },
            error: function(jqXHR) {
                $('#blog_output').text('Error creating blog post: ' + (jqXHR.responseJSON?.data?.message || 'Server error'));
            },
            complete: function() {
                $('#create_blog_post').removeClass('loading');
            }
        });
    });

    $('#ai-bulk-add-more').click(function(e) {
        e.preventDefault();
        const rowCount = $('.ai-bulk-topic-row').length + 1;
        $('.ai-bulk-topics').append(`
            <div class="ai-bulk-topic-row">
                <input type="text" class="ai-bulk-topic-input" placeholder="Enter topic ${rowCount}" title="Enter a topic for bulk post generation (e.g., 'SEO Tips')">
            </div>
        `);
    });

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
                        <input type="text" class="ai-bulk-topic-input" value="${topic}" placeholder="Enter topic ${index + 1}" title="Enter a topic for bulk post generation (e.g., 'SEO Tips')">
                    </div>
                `);
            });
        };
        reader.readAsText(file);
    });

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
                showConfirmation(`Successfully generated ${totalTopics} posts!`, 'bulk_confirmation');
                $('#ai-bulk-generate').removeClass('loading');
                loadDraftPosts();
                return;
            }

            const topic = topics[index];
            $('#ai-bulk-output').text(`Generating post ${index + 1} of ${totalTopics}...`);

            $.ajax({
                url: ai_admin_boost.ajax_url,
                type: 'POST',
                data: {
                    action: 'create_blog_post',
                    topic: topic,
                    nonce: ai_admin_boost.nonce
                },
                success: function(response) {
                    if (response.success) {
                        generatedCount++;
                        $('#ai-bulk-output').text(`${generatedCount} post${generatedCount !== 1 ? 's' : ''} generated...`);
                        generateNextTopic(index + 1);
                    } else {
                        $('#ai-bulk-output').text(`Error generating post ${index + 1}: ${response.data?.message || 'Unknown error'}`);
                        $('#ai-bulk-generate').removeClass('loading');
                    }
                },
                error: function(jqXHR) {
                    $('#ai-bulk-output').text(`Error generating post ${index + 1}: ${jqXHR.responseJSON?.data?.message || 'Server error'}`);
                    $('#ai-bulk-generate').removeClass('loading');
                }
            });
        }

        generateNextTopic(0);
    });

    $('#suggest_seo').click(function(e) {
        e.preventDefault();
        const content = $('#seo_content').val();
        if (!content) {
            $('#seo_output').text('Please enter content to analyze.');
            return;
        }
        $('#seo_output').text('Generating SEO suggestions...');
        $(this).addClass('loading');
        $.ajax({
            url: ai_admin_boost.ajax_url,
            type: 'POST',
            data: {
                action: 'suggest_seo',
                content: content,
                nonce: ai_admin_boost.nonce
            },
            success: function(response) {
                if (response.html) {
                    $('#seo_output').text(response.html);
                    showConfirmation('SEO suggestions generated successfully!', 'seo_confirmation');
                } else {
                    $('#seo_output').text('Error: No suggestions returned.');
                }
            },
            error: function(jqXHR) {
                $('#seo_output').text('Error fetching SEO suggestions: ' + (jqXHR.responseJSON?.message || 'Server error'));
            },
            complete: function() {
                $('#suggest_seo').removeClass('loading');
            }
        });
    });

    $('#analyze_seo_specific').click(function(e) {
        e.preventDefault();
        const post_id = $('#specific_page').val();
        if (!post_id) {
            alert('Please select content.');
            return;
        }
        $('#seo_analysis_output').text('Analyzing...');
        $(this).addClass('loading');
        $.ajax({
            url: ai_admin_boost.ajax_url,
            type: 'POST',
            data: {
                action: 'analyze_seo_specific',
                page_id: post_id,
                nonce: ai_admin_boost.nonce
            },
            success: function(response) {
                if (response.html) {
                    $('#seo_analysis_output').text(response.html);
                    showConfirmation('SEO analysis completed successfully!', 'seo_analysis_confirmation');
                } else {
                    $('#seo_analysis_output').text('Error: No analysis returned.');
                }
            },
            error: function(jqXHR) {
                $('#seo_analysis_output').text('Error analyzing content: ' + (jqXHR.responseJSON?.message || 'Server error'));
            },
            complete: function() {
                $('#analyze_seo_specific').removeClass('loading');
            }
        });
    });

    $('#generate_task').click(function(e) {
        e.preventDefault();

        const schedules = [];
        let hasError = false;

        const selectedPosts = $('.post_selection')
            .map(function() { return $(this).val(); })
            .get()
            .filter(val => val);

        const duplicates = selectedPosts.filter((item, index) => selectedPosts.indexOf(item) !== index);
        if (duplicates.length > 0) {
            const duplicateTitles = [];
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
                return false;
            }
            if (!datetime) {
                $('#task_output').html(`<div>Please select a date and time for row ${index + 1}.</div>`);
                hasError = true;
                return false;
            }

            schedules.push({ post_id: postId, datetime: datetime });
        });

        if (hasError || schedules.length === 0) {
            return;
        }

        $('#task_output').text('Scheduling posts...');
        $(this).addClass('loading');

        $.ajax({
            url: ai_admin_boost.ajax_url,
            type: 'POST',
            data: {
                action: 'schedule_post',
                schedules: schedules,
                nonce: ai_admin_boost.nonce
            },
            success: function(response) {
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
                    showConfirmation('Posts scheduled successfully!', 'task_confirmation');
                    loadDraftPosts();
                    resetScheduleForm();
                } else {
                    $('#task_output').text('Error: ' + (response.message || 'Unknown error occurred.'));
                }
            },
            error: function(jqXHR) {
                $('#task_output').text('Error scheduling posts: ' + (jqXHR.responseJSON?.message || 'Server error'));
            },
            complete: function() {
                $('#generate_task').removeClass('loading');
            }
        });
    });

    // Load scheduled posts with pagination
    function loadScheduledPosts(page) {
        const $spinner = $('#bulk_edit_spinner');
        const $list = $('#scheduled_posts_list');
        const $loadMoreButton = $('#bulk_load_more');
        const $updateButton = $('#bulk_update_dates');
        const perPage = parseInt($('#posts_per_page').val()) || 5;

        $spinner.show();
        $.ajax({
            url: ai_admin_boost.ajax_url,
            type: 'GET',
            data: {
                action: 'get_scheduled_posts',
                page: page,
                per_page: perPage,
                nonce: ai_admin_boost.nonce
            },
            success: function(response) {
                $spinner.hide();
                if (response.success) {
                    let html = '<h3>Scheduled Posts</h3><table class="ai-scheduled-table"><thead><tr><th>Select</th><th>Title</th><th>Current Schedule</th><th>New Schedule</th></tr></thead><tbody>';
                    if (response.posts.length > 0) {
                        $.each(response.posts, function(index, post) {
                            html += `
                                <tr>
                                    <td><input type="checkbox" name="bulk_edit_post[]" value="${post.ID}" class="bulk-edit-checkbox"></td>
                                    <td>${post.post_title} (ID: ${post.ID})</td>
                                    <td>${post.schedule_date}</td>
                                    <td><input type="datetime-local" class="bulk-datetime" data-post-id="${post.ID}" value="${post.schedule_date.replace(' ', 'T')}"></td>
                                </tr>
                            `;
                        });
                        html += '</tbody></table>';
                        if (page === 1) {
                            $list.html(html);
                        } else {
                            $list.find('tbody').append($(html).find('tbody').html());
                        }
                        $updateButton.show();
                        if (response.has_more) {
                            $loadMoreButton.show().data('page', page + 1);
                        } else {
                            $loadMoreButton.hide();
                        }
                    } else {
                        $list.html('<div>No scheduled posts found.</div>');
                        $loadMoreButton.hide();
                        $updateButton.hide();
                    }
                } else {
                    $list.html('<div>No scheduled posts found.</div>');
                    $loadMoreButton.hide();
                    $updateButton.hide();
                }
            },
            error: function(jqXHR) {
                $spinner.hide();
                $list.html('<div>Error loading scheduled posts: ' + (jqXHR.responseJSON?.message || 'Server error') + '</div>');
                $loadMoreButton.hide();
                $updateButton.hide();
            }
        });
    }

    // Load more scheduled posts
    $('#bulk_load_more').click(function(e) {
        e.preventDefault();
        const nextPage = $(this).data('page') || 2;
        loadScheduledPosts(nextPage);
    });

    // Update per page and reload
    $('#posts_per_page').change(function() {
        loadScheduledPosts(1);
    });

    // Bulk update dates for selected posts
    $('#bulk_update_dates').click(function(e) {
        e.preventDefault();
        const $spinner = $('#bulk_edit_spinner');
        const $list = $('#scheduled_posts_list');
        const updates = [];

        $('input[name="bulk_edit_post[]"]:checked').each(function() {
            const postId = $(this).val();
            const newDate = $(`.bulk-datetime[data-post-id="${postId}"]`).val();
            if (newDate) {
                updates.push({ post_id: postId, new_date: newDate });
            }
        });

        if (updates.length === 0) {
            $list.html('<div>Please select at least one post and set a new date.</div>');
            return;
        }

        $spinner.show();
        $.ajax({
            url: ai_admin_boost.ajax_url,
            type: 'POST',
            data: {
                action: 'bulk_update_post_dates',
                updates: updates,
                nonce: ai_admin_boost.nonce
            },
            success: function(response) {
                $spinner.hide();
                if (response.success && typeof response.data.updated !== 'undefined') {
                    const updatedCount = response.data.updated;
                    $list.html(`<div>${updatedCount} post${updatedCount !== 1 ? 's' : ''} updated successfully.</div>`);
                    showConfirmation(`${updatedCount} post${updatedCount !== 1 ? 's' : ''} updated!`, 'bulk_edit_confirmation');
                    loadScheduledPosts(1); // Refresh the list
                } else {
                    $list.html('<div>Error updating posts: ' + (response.data?.message || 'Unknown error') + '</div>');
                }
            },
            error: function(jqXHR) {
                $spinner.hide();
                $list.html('<div>Error updating posts: ' + (jqXHR.responseJSON?.message || 'Server error') + '</div>');
            }
        });
    });

    $('#run_admin_shortcut').click(function(e) {
        e.preventDefault();
        const shortcut = $('#admin_shortcut').val();
        $.ajax({
            url: ai_admin_boost.ajax_url,
            type: 'POST',
            data: {
                action: 'run_admin_shortcut',
                shortcut: shortcut,
                nonce: ai_admin_boost.nonce
            },
            success: function(response) {
                $('#shortcut_output').text(response);
            },
            error: function() {
                $('#shortcut_output').text('Error running shortcut.');
            }
        });
    });
});