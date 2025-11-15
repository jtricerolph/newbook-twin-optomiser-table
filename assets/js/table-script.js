/**
 * Newbook Twin Optomiser Table JavaScript
 */

(function($) {
    'use strict';

    /**
     * Initialize the table functionality
     */
    function initTwinOptomiserTable() {
        const datePicker = $('#ntot-start-date');

        if (!datePicker.length) {
            return;
        }

        // Date picker change handler
        datePicker.on('change', function() {
            const startDate = $(this).val();
            const days = $(this).data('days') || 14;

            if (startDate) {
                refreshTable(startDate, days);
            }
        });

        console.log('Twin Optomiser Table initialized');
    }

    /**
     * Refresh table via AJAX
     *
     * @param {string} startDate - Start date in Y-m-d format
     * @param {number} days - Number of days to display
     */
    function refreshTable(startDate, days) {
        const container = $('.ntot-table-container');
        const contentDiv = $('#ntot-table-content');

        if (!container.length || !contentDiv.length) {
            console.error('Table container not found');
            return;
        }

        // Add loading state
        container.addClass('loading');

        // Make AJAX request
        $.ajax({
            url: ntotData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ntot_refresh_table',
                nonce: ntotData.nonce,
                start_date: startDate,
                days: days
            },
            success: function(response) {
                if (response.success && response.data.html) {
                    // Update table content
                    contentDiv.html(response.data.html);
                    console.log('Table refreshed successfully');
                } else {
                    console.error('Failed to refresh table:', response.data?.message || 'Unknown error');
                    alert('Failed to refresh table. Please try again.');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                alert('An error occurred while refreshing the table. Please try again.');
            },
            complete: function() {
                // Remove loading state
                container.removeClass('loading');
            }
        });
    }

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        initTwinOptomiserTable();
    });

})(jQuery);
