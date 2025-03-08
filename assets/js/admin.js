/**
 * Block Usage Admin JavaScript
 */

(function($) {
    'use strict';

    // Document ready
    $(document).ready(function() {
        // Initialize any interactive elements if needed
        initBlockUsageTable();
    });

    /**
     * Initialize the block usage table
     */
    function initBlockUsageTable() {
        // Add any table functionality here
        // For example, sorting, filtering, etc.
        
        // Simple example: make rows clickable to show more details
        $('.wp-list-table tbody tr').on('click', function() {
            $(this).toggleClass('expanded');
        });
    }

})(jQuery);