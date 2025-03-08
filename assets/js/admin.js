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
            
            // Remove active class from all rows
            $('.block-row').removeClass('active');
            
            // Add active class to clicked row
            $(this).closest('.block-row').addClass('active');
            
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

        // Make block groups collapsible
        $('.block-group-title').on('click', function() {
            const $title = $(this);
            const $group = $title.next('.block-group');
            
            $title.toggleClass('collapsed');
            $group.toggleClass('collapsed');
            
            // Save collapsed state to localStorage
            const groupId = $title.text().trim();
            if ($title.hasClass('collapsed')) {
                saveCollapsedState(groupId, true);
            } else {
                saveCollapsedState(groupId, false);
            }
        });
        
        // Initialize collapsed states from localStorage
        $('.block-group-title').each(function() {
            const $title = $(this);
            const groupId = $title.text().trim();
            const isCollapsed = getCollapsedState(groupId);
            
            if (isCollapsed) {
                $title.addClass('collapsed');
                $title.next('.block-group').addClass('collapsed');
            }
        });

        // Usage filter functionality
        $('.usage-filter').on('click', function(e) {
            e.preventDefault();
            
            // Update active filter
            $('.usage-filter').removeClass('current');
            $(this).addClass('current');
            
            const filterType = $(this).data('filter');
            
            // If we're showing all blocks, just show everything
            if (filterType === 'all') {
                $('.block-row').show();
                return;
            }
            
            // Check if we need to analyze block usage first
            const needsAnalysis = $('.block-row[data-usage-status="loading"]').length > 0;
            
            if (needsAnalysis) {
                // Show loading indicator
                $('.usage-loading').show();
                
                // Start analyzing all blocks
                analyzeBlockUsage(function() {
                    // When analysis is complete, filter blocks
                    filterBlocksByUsage(filterType);
                    $('.usage-loading').hide();
                });
            } else {
                // We already have usage data, just filter
                filterBlocksByUsage(filterType);
            }
        });
        
        /**
         * Save block group collapsed state to localStorage
         * 
         * @param {string} groupId - Group identifier
         * @param {boolean} isCollapsed - Whether the group is collapsed
         */
        function saveCollapsedState(groupId, isCollapsed) {
            if (typeof(Storage) !== 'undefined') {
                const states = JSON.parse(localStorage.getItem('blockGroupStates') || '{}');
                states[groupId] = isCollapsed;
                localStorage.setItem('blockGroupStates', JSON.stringify(states));
            }
        }
        
        /**
         * Get block group collapsed state from localStorage
         * 
         * @param {string} groupId - Group identifier
         * @return {boolean} Whether the group is collapsed
         */
        function getCollapsedState(groupId) {
            if (typeof(Storage) !== 'undefined') {
                const states = JSON.parse(localStorage.getItem('blockGroupStates') || '{}');
                return states[groupId] === true;
            }
            return false;
        }
        
        /**
         * Analyze usage for all blocks
         * 
         * @param {Function} callback - Function to call when analysis is complete
         */
        function analyzeBlockUsage(callback) {
            const blocks = [];
            
            // Collect all blocks to analyze
            $('.block-row').each(function() {
                const $row = $(this);
                const blockName = $row.data('block-name');
                const $link = $row.find('.block-title-link');
                const searchPattern = $link.data('search-pattern');
                
                blocks.push({
                    element: $row,
                    blockName: blockName,
                    searchPattern: searchPattern
                });
            });
            
            // Process blocks in batches to avoid overwhelming the server
            processBatch(blocks, 0, 5, callback);
        }
        
        /**
         * Process a batch of blocks for usage analysis
         * 
         * @param {Array} blocks - Array of block objects
         * @param {number} startIndex - Starting index for this batch
         * @param {number} batchSize - Number of blocks to process in this batch
         * @param {Function} callback - Function to call when all batches are complete
         */
        function processBatch(blocks, startIndex, batchSize, callback) {
            if (startIndex >= blocks.length) {
                // All blocks processed
                if (callback) callback();
                return;
            }
            
            const endIndex = Math.min(startIndex + batchSize, blocks.length);
            const batch = blocks.slice(startIndex, endIndex);
            let completedRequests = 0;
            
            // Process each block in the current batch
            batch.forEach(function(block) {
                checkBlockUsage(block.blockName, block.searchPattern, function(isUsed) {
                    // Update block row with usage status
                    block.element.attr('data-usage-status', isUsed ? 'used' : 'unused');
                    
                    // Track completed requests
                    completedRequests++;
                    
                    // If all requests in this batch are complete, process the next batch
                    if (completedRequests === batch.length) {
                        processBatch(blocks, endIndex, batchSize, callback);
                    }
                });
            });
        }
        
        /**
         * Check if a block is used in any posts
         * 
         * @param {string} blockName - Block name
         * @param {string} searchPattern - Search pattern
         * @param {Function} callback - Callback with result
         */
        function checkBlockUsage(blockName, searchPattern, callback) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'block_usage_check_usage',
                    block_name: blockName,
                    search_pattern: searchPattern,
                    nonce: blockUsageData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        callback(response.data.is_used);
                    } else {
                        callback(false);
                    }
                },
                error: function() {
                    callback(false);
                }
            });
        }
        
        /**
         * Filter blocks by usage status
         * 
         * @param {string} filterType - Filter type ('used' or 'unused')
         */
        function filterBlocksByUsage(filterType) {
            $('.block-row').each(function() {
                const $row = $(this);
                const usageStatus = $row.attr('data-usage-status');
                
                if (usageStatus === 'loading') {
                    // If still loading, show for now
                    $row.show();
                } else if (filterType === 'used' && usageStatus === 'used') {
                    $row.show();
                } else if (filterType === 'unused' && usageStatus === 'unused') {
                    $row.show();
                } else {
                    $row.hide();
                }
            });
        }
        
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
                        // Update block usage status if needed
                        $('.block-row[data-block-name="' + blockName + '"]').attr('data-usage-status', 'used');
                    } else {
                        $sidebarResults.html('<div class="no-posts-found">No posts found using this block.</div>');
                        // Update block usage status if needed
                        $('.block-row[data-block-name="' + blockName + '"]').attr('data-usage-status', 'unused');
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
     * Initialize block usage table functionality
     */
    function initBlockUsageTable() {
        // Any additional initialization
    }

})(jQuery);