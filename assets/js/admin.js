/**
 * Block Usage Admin JavaScript
 */

(function($) {
    'use strict';

    // Document ready
    $(document).ready(function() {
        // Initialize any interactive elements if needed
        initBlockUsageTable();
        const $mainContent = $('.block-usage-main');
        const $sidebar = $('.block-usage-sidebar');
        const $sidebarTitle = $('#selected-block-title');
        const $sidebarResults = $('.sidebar-results');
        const $sidebarLoading = $('.sidebar-loading');
        
        // Handle block title click
        $('.block-title-link').on('click', function(e) {
            e.preventDefault();
            
            const blockName = $(this).data('block-name');
            const searchPattern = $(this).data('search-pattern');
            const blockTitle = $(this).text().trim();
            
            // Show sidebar
            $mainContent.addClass('with-sidebar');
            $sidebar.show();
            $sidebarTitle.text(blockTitle);
            
            // Show loading indicator
            $sidebarLoading.show();
            $sidebarResults.empty();
            
            // Fetch posts containing this block
            fetchPostsWithBlock(blockName, searchPattern);
        });
        
        // Close sidebar
        $('.close-sidebar').on('click', function() {
            $mainContent.removeClass('with-sidebar');
            $sidebar.hide();
        });
        
        /**
         * Fetch posts containing a specific block
         * 
         * @param {string} blockName - The block name (e.g. 'core/paragraph')
         * @param {string} searchPattern - The pattern to search for (e.g. 'paragraph' for core blocks)
         */
        function fetchPostsWithBlock(blockName, searchPattern) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'block_usage_find_posts',
                    block_name: blockName,
                    search_pattern: searchPattern,
                    nonce: blockUsageData.nonce
                },
                success: function(response) {
                    $sidebarLoading.hide();
                    
                    if (response.success && response.data.posts.length > 0) {
                        displayPosts(response.data.posts);
                    } else {
                        $sidebarResults.html('<div class="no-posts-found">No posts found using this block.</div>');
                    }
                },
                error: function() {
                    $sidebarLoading.hide();
                    $sidebarResults.html('<div class="no-posts-found">Error fetching posts.</div>');
                }
            });
        }
        
        /**
         * Display list of posts in the sidebar
         * 
         * @param {Array} posts - Array of post objects
         */
        function displayPosts(posts) {
            let html = '<ul>';
            
            posts.forEach(function(post) {
                const editUrl = post.edit_url || '#';
                const postTypeLabel = post.post_type_label || post.post_type;
                
                html += `<li>
                    <a href="${editUrl}" target="_blank">${post.title}</a>
                    <span class="post-type">(${postTypeLabel})</span>
                </li>`;
            });
            
            html += '</ul>';
            $sidebarResults.html(html);
        }
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