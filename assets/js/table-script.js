/**
 * Newbook Twin Optomiser Table JavaScript
 */

(function($) {
    'use strict';

    /**
     * Initialize the table functionality
     */
    function initTwinOptomiserTable() {
        const container = $('.ntot-table-container');

        if (!container.length) {
            return;
        }

        // Refresh button click handler
        $('.ntot-refresh-btn').on('click', function(e) {
            e.preventDefault();
            refreshTableData($(this).closest('.ntot-table-container'));
        });

        // Initialize any additional features here
        console.log('Twin Optomiser Table initialized');
    }

    /**
     * Refresh table data via AJAX
     */
    function refreshTableData(container) {
        const propertyId = container.data('property-id');

        // Add loading state
        container.addClass('loading');

        // Make AJAX request
        $.ajax({
            url: ntotData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ntot_refresh_data',
                nonce: ntotData.nonce,
                property_id: propertyId
            },
            success: function(response) {
                if (response.success) {
                    // Update table content
                    container.find('.ntot-table-content').html(response.data.html);
                    console.log('Table data refreshed successfully');
                } else {
                    console.error('Failed to refresh table data:', response.data.message);
                    alert('Failed to refresh data. Please try again.');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                alert('An error occurred while refreshing data.');
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
