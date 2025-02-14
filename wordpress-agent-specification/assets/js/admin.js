/* global jQuery, wp */
(function($) {
    'use strict';

    // Initialize the admin interface
    function initAdmin() {
        initTabs();
        initSpecEditors();
        initSpecActions();
        initPreview();
        initStatusRefresh();
    }

    // Initialize tab navigation
    function initTabs() {
        $('.nav-tab').on('click', function(e) {
            e.preventDefault();
            var target = $(this).attr('href');
            
            // Update tabs
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            // Update content
            $('.tab-pane').removeClass('active');
            $(target).addClass('active');

            // Refresh content if needed
            if (target === '#status') {
                refreshStatus();
            }
        });
    }

    // Initialize specification editors
    function initSpecEditors() {
        $('.spec-editor').each(function() {
            var $editor = $(this);
            
            // Add validation for JSON content
            if ($editor.closest('.spec-item').find('h3').text().toLowerCase().includes('json')) {
                $editor.on('input', function() {
                    validateJson($editor);
                });
            }
        });
    }

    // Initialize specification actions
    function initSpecActions() {
        // Save specification
        $('.save-spec').on('click', function() {
            var $button = $(this);
            var specName = $button.data('spec');
            var $editor = $('.spec-editor[data-spec="' + specName + '"]');
            
            if ($editor.hasClass('json-error')) {
                showNotice('Invalid JSON content', 'error');
                return;
            }
            
            saveSpec(specName, $editor.val());
        });

        // Reset specification
        $('.reset-spec').on('click', function() {
            var $button = $(this);
            var specName = $button.data('spec');
            
            if (confirm('Are you sure you want to reset this specification to its default value?')) {
                resetSpec(specName);
            }
        });
    }

    // Initialize preview functionality
    function initPreview() {
        $('.view-spec').on('click', function(e) {
            e.preventDefault();
            window.open($(this).attr('href'), '_blank');
        });
    }

    // Initialize status refresh
    function initStatusRefresh() {
        if ($('#status').length) {
            setInterval(refreshStatus, 60000); // Refresh every minute
        }
    }

    // Validate JSON content
    function validateJson($editor) {
        try {
            JSON.parse($editor.val());
            $editor.removeClass('json-error');
            return true;
        } catch (e) {
            $editor.addClass('json-error');
            return false;
        }
    }

    // Save specification
    function saveSpec(name, content) {
        var $item = $('.spec-item[data-spec="' + name + '"]');
        $item.addClass('agentics-loading');

        wp.apiRequest({
            path: 'agentics/v1/specs/' + name,
            method: 'POST',
            data: { content: content }
        })
        .done(function() {
            showNotice('Specification saved successfully', 'success');
        })
        .fail(function(xhr) {
            showNotice(xhr.responseJSON?.message || 'Error saving specification', 'error');
        })
        .always(function() {
            $item.removeClass('agentics-loading');
        });
    }

    // Reset specification
    function resetSpec(name) {
        var $item = $('.spec-item[data-spec="' + name + '"]');
        $item.addClass('agentics-loading');

        wp.apiRequest({
            path: 'agentics/v1/specs/' + name + '/reset',
            method: 'POST'
        })
        .done(function(response) {
            $('.spec-editor[data-spec="' + name + '"]').val(response.content);
            showNotice('Specification reset successfully', 'success');
        })
        .fail(function(xhr) {
            showNotice(xhr.responseJSON?.message || 'Error resetting specification', 'error');
        })
        .always(function() {
            $item.removeClass('agentics-loading');
        });
    }

    // Refresh status information
    function refreshStatus() {
        var $status = $('#status');
        $status.addClass('agentics-loading');

        wp.apiRequest({
            path: 'agentics/v1/health',
            method: 'GET'
        })
        .done(function(response) {
            updateStatusDisplay(response);
        })
        .fail(function() {
            showNotice('Error refreshing status', 'error');
        })
        .always(function() {
            $status.removeClass('agentics-loading');
        });
    }

    // Update status display
    function updateStatusDisplay(data) {
        var $status = $('#status');
        
        // Update status indicators
        $status.find('.status-value').each(function() {
            var key = $(this).data('key');
            var value = data[key];
            
            if (value !== undefined) {
                $(this).text(value);
            }
        });

        // Update timestamp
        $status.find('.status-timestamp').text(new Date().toLocaleString());
    }

    // Show notification
    function showNotice(message, type) {
        var $notice = $('<div class="agentics-notice agentics-notice-' + type + '">')
            .text(message)
            .hide()
            .insertBefore('.agentics-admin-tabs')
            .slideDown();

        setTimeout(function() {
            $notice.slideUp(function() {
                $notice.remove();
            });
        }, 3000);
    }

    // Initialize when document is ready
    $(document).ready(initAdmin);

})(jQuery);