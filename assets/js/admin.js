/**
 * Block Usage Admin JavaScript
 */

(function($) {
    'use strict';

    // Document ready
    $(document).ready(function() {
        const $mainContent = $('.block-usage-main');
        const $sidebar = $('.block-usage-sidebar');
        const $sidebarTitle = $('#selected-block-title');
        const $sidebarResults = $('.sidebar-results');
        const $sidebarLoading = $('.sidebar-loading');
        
        // Initialize the plugin
        initBlockUsageTable();
        
        // Handle block title click
        $('.block-title-link').on('click', function(e) {
            e.preventDefault();
            
            const $link = $(this);
            const blockName = $link.data('block-name');
            const searchPattern = $link.data('search-pattern');
            
            // Set selected block title
            $sidebarTitle.text(blockName);
            
            // Show sidebar
            $mainContent.addClass('with-sidebar');
            $sidebar.show();
            
            // Clear previous results
            $sidebarResults.empty();
            
            // Show loading indicator
            $sidebarLoading.show();
            
            // Mark this row as active
            $('.block-row').removeClass('active');
            $link.closest('.block-row').addClass('active');
            
            // Fetch posts with this block
            fetchPostsWithBlock(blockName, searchPattern);
        });
        
        /**
         * Save the collapsed state of a block group to localStorage
         */
        function saveCollapsedState(groupId, isCollapsed) {
            try {
                const states = JSON.parse(localStorage.getItem('blockUsageCollapsedGroups') || '{}');
                states[groupId] = isCollapsed;
                localStorage.setItem('blockUsageCollapsedGroups', JSON.stringify(states));
            } catch (e) {
                console.error('Failed to save collapsed state:', e);
            }
        }
        
        /**
         * Get the collapsed state of a block group from localStorage
         */
        function getCollapsedState(groupId) {
            try {
                const states = JSON.parse(localStorage.getItem('blockUsageCollapsedGroups') || '{}');
                return states[groupId] === true;
            } catch (e) {
                console.error('Failed to get collapsed state:', e);
                return false;
            }
        }
        
        /**
         * Check if a block is used in any posts
         */
        function checkBlockUsage(blockName, searchPattern, callback) {
            // Send AJAX request to check block usage
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'block_usage_check_usage',
                    nonce: blockUsageData.nonce,
                    block_name: blockName,
                    search_pattern: searchPattern
                },
                success: function(response) {
                    if (response.success) {
                        // Call the callback with usage information
                        if (callback) {
                            callback(response.data.is_used, response.data.usage_count);
                        }
                        
                        // Store the breakdown data for this block if available
                        if (response.data.breakdown) {
                            const $row = $(`.block-row[data-block-name="${blockName}"]`);
                            $row.data('usage-breakdown', response.data.breakdown);
                        }
                    } else {
                        console.error('Error checking block usage:', response);
                        // Assume not used if there's an error
                        if (callback) {
                            callback(false, 0);
                        }
                    }
                },
                error: function() {
                    console.error('AJAX error when checking block usage');
                    // Assume not used if there's an error
                    if (callback) {
                        callback(false, 0);
                    }
                }
            });
        }
        
        /**
         * Fetch posts that use a specific block
         */
        function fetchPostsWithBlock(blockName, searchPattern) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'block_usage_find_posts',
                    nonce: blockUsageData.nonce,
                    block_name: blockName,
                    search_pattern: searchPattern
                },
                success: function(response) {
                    if (response.success) {
                        displayPosts(response.data.posts);
                    } else {
                        $sidebarLoading.hide();
                        $sidebarResults.html('<div class="notice notice-error"><p>Error: ' + response.data.message + '</p></div>');
                    }
                },
                error: function() {
                    $sidebarLoading.hide();
                    $sidebarResults.html('<div class="notice notice-error"><p>Network error when fetching posts.</p></div>');
                }
            });
        }
        
        /**
         * Display posts in the sidebar
         */
        function displayPosts(posts) {
            $sidebarLoading.hide();
            
            if (posts.length === 0) {
                $sidebarResults.html('<div class="no-posts-found">No posts found using this block.</div>');
                return;
            }
            
            // Group posts by type
            const postsByType = {};
            posts.forEach(function(post) {
                if (!postsByType[post.post_type]) {
                    postsByType[post.post_type] = {
                        label: post.post_type_label,
                        items: []
                    };
                }
                postsByType[post.post_type].items.push(post);
            });
            
            let html = '';
            
            // Create a section for each post type
            Object.keys(postsByType).forEach(function(type) {
                const group = postsByType[type];
                html += `<div class="post-type-group">
                    <h4 class="post-type-heading">${group.label} (${group.items.length})</h4>
                    <ul>`;
                
                group.items.forEach(function(post) {
                    html += `
                        <li>
                            <a href="${post.edit_url}" target="_blank">${post.title}</a>
                        </li>
                    `;
                });
                
                html += `</ul></div>`;
            });
            
            $sidebarResults.html(html);
        }
        
        /**
         * Filter blocks by usage status
         */
        function filterBlocksByUsage(filterType) {
            // Set active class on filter buttons
            $('.filter-blocks .filter-link').removeClass('active');
            $(`.filter-link[data-filter="${filterType}"]`).addClass('active');
            
            const $blockRows = $('.block-row');
            
            if (filterType === 'all') {
                // Show all blocks
                $blockRows.show();
                return;
            }
            
            // Show loading state for rows without status
            $blockRows.each(function() {
                const $row = $(this);
                const status = $row.attr('data-usage-status');
                
                if (!status || status === 'loading') {
                    $row.attr('data-usage-status', 'loading');
                    $row.find('.usage-count-cell').html('<span class="usage-count-placeholder">—</span>');
                }
            });
            
            // Filter visible rows
            $blockRows.each(function() {
                const $row = $(this);
                const currentStatus = $row.attr('data-usage-status');
                
                if (currentStatus === 'loading') {
                    // Need to check this block
                    const blockName = $row.data('block-name');
                    const $link = $row.find('.block-title-link');
                    const searchPattern = $link.data('search-pattern');
                    
                    // Check if this block is used
                    checkBlockUsage(blockName, searchPattern, function(isUsed, count) {
                        // Update the block status
                        $row.attr('data-usage-status', isUsed ? 'used' : 'unused');
                        
                        // Update the usage count
                        const $countCell = $row.find('.usage-count-cell');
                        $countCell.html(`<span class="usage-count">${count}</span>`);
                        
                        // Format count based on value
                        const $count = $countCell.find('.usage-count');
                        if (count > 10) {
                            $count.attr('data-count', 'high');
                        } else if (count > 0) {
                            $count.attr('data-count', 'medium');
                        } else {
                            $count.attr('data-count', 'low');
                        }
                        
                        // Apply filter
                        if ((filterType === 'used' && isUsed) || 
                            (filterType === 'unused' && !isUsed)) {
                            $row.show();
                        } else {
                            $row.hide();
                        }
                        
                        // Update filter counts
                        updateFilterCounts();
                    });
                } else {
                    // Already have status, just filter
                    if ((filterType === 'used' && currentStatus === 'used') || 
                        (filterType === 'unused' && currentStatus === 'unused')) {
                        $row.show();
                    } else {
                        $row.hide();
                    }
                }
            });
            
            // Update filter counts right away with existing data
            updateFilterCounts();
        }
        
        /**
         * Update filter counts based on current block statuses
         */
        function updateFilterCounts() {
            // Count blocks by status
            let total = 0;
            let used = 0;
            let unused = 0;
            
            $('.block-row').each(function() {
                total++;
                const status = $(this).attr('data-usage-status');
                if (status === 'used') {
                    used++;
                } else if (status === 'unused') {
                    unused++;
                }
            });
            
            // Update filter labels
            $('.filter-link[data-filter="all"]').html(`All (${total})`);
            $('.filter-link[data-filter="used"]').html(`Used (${used})`);
            $('.filter-link[data-filter="unused"]').html(`Unused (${unused})`);
            
            // Update block group counts
            updateBlockGroupCounts();
        }
        
        /**
         * Update counts in block group headers
         */
        function updateBlockGroupCounts() {
            // Process each block group
            $('.block-group').each(function() {
                const $group = $(this);
                const $rows = $group.find('.block-row');
                const total = $rows.length;
                let used = 0;
                
                // Count used blocks in this group
                $rows.each(function() {
                    if ($(this).attr('data-usage-status') === 'used') {
                        used++;
                    }
                });
                
                // Update the group header
                const $title = $group.find('.block-group-title');
                $title.find('.block-count').remove();
                $title.append(`<span class="block-count">${used} / ${total}</span>`);
            });
        }
        
        /**
         * Initialize block usage table and functionality
         */
        function initBlockUsageTable() {
            // Initialize collapsible block groups
            initCollapseGroups();
            
            // Set initial filter counts based on existing data
            updateFilterCounts();
            
            // Add click handler for filter links
            $('.filter-link').on('click', function(e) {
                e.preventDefault();
                const filterType = $(this).data('filter');
                filterBlocksByUsage(filterType);
            });
            
            // Add click handler for close sidebar button
            $('.close-sidebar').on('click', function() {
                $mainContent.removeClass('with-sidebar');
                $sidebar.hide();
            });
            
            // Add click handler for "Scan Again" toggle
            $('.scan-toggle').on('click', function(e) {
                e.preventDefault();
                $('.scan-again-container').slideToggle(200);
            });
            
            // Add click handler for scan button
            $('.scan-blocks').on('click', function() {
                // Show loading indicator
                $('.scan-blocks').hide();
                const $scanContainer = $('.block-usage-scan');
                
                $scanContainer.html(`
                    <div class="scan-status">
                        <span class="spinner is-active"></span>
                        <div class="scan-progress-text">
                            Scanning blocks usage... <span class="scan-percentage">0%</span>
                        </div>
                        <div class="scan-progress-bar-container">
                            <div class="scan-progress-bar" style="width: 0%"></div>
                        </div>
                    </div>
                `);
                
                // Reset all blocks to loading state
                $('.block-row').attr('data-usage-status', 'loading');
                $('.usage-count-cell').html('<span class="usage-count-placeholder">—</span>');
                
                // Get all blocks and analyze them
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
                
                // Set up progress tracking
                const totalBlocks = blocks.length;
                let processedBlocks = 0;
                
                // Process blocks in batches
                processScanBatch(blocks, 0, 5, processedBlocks, totalBlocks);
            });
            
            // Initialize filter links with stored data
            $('.filter-link[data-filter="all"]').trigger('click');
        }
        
        /**
         * Initialize collapsible block groups
         */
        function initCollapseGroups() {
            // Add click handlers for collapsing block groups
            $('.block-group-title').on('click', function() {
                const $title = $(this);
                const groupId = $title.text().trim().toLowerCase().replace(/\s+/g, '-');
                const isCollapsed = !$title.hasClass('collapsed');
                
                // Toggle collapsed state
                $title.toggleClass('collapsed', isCollapsed);
                $title.closest('.block-group').find('tbody').toggleClass('collapsed', isCollapsed);
                
                // Save state to localStorage
                saveCollapsedState(groupId, isCollapsed);
            });
            
            // Apply saved collapsed states
            $('.block-group-title').each(function() {
                const $title = $(this);
                const groupId = $title.text().trim().toLowerCase().replace(/\s+/g, '-');
                const isCollapsed = getCollapsedState(groupId);
                
                if (isCollapsed) {
                    $title.addClass('collapsed');
                    $title.closest('.block-group').find('tbody').addClass('collapsed');
                }
            });
        }
        
        /**
         * Process a batch of blocks for the full scan
         */
        function processScanBatch(blocks, startIndex, batchSize, processedBlocks, totalBlocks) {
            if (startIndex >= blocks.length) {
                // All blocks processed, update the UI
                updateFilterCounts();
                
                // Record scan timestamp
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'block_usage_record_scan',
                        nonce: blockUsageData.nonce
                    }
                });
                
                // Show completion message
                const $scanContainer = $('.block-usage-scan');
                $scanContainer.removeClass('scan-needed');
                $scanContainer.html(`
                    <div class="scan-status-ok">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <span class="scan-last-run">
                            Statistics up to date. Last scan: just now
                        </span>
                        <button type="button" class="button button-secondary scan-toggle">Scan Again</button>
                        <div class="scan-again-container" style="display: none;">
                            <button type="button" class="button button-secondary scan-blocks">
                                Scan All Blocks
                            </button>
                            <p class="scan-description">
                                This will scan your content and calculate accurate usage statistics for all blocks.
                            </p>
                        </div>
                    </div>
                `);
                
                // Re-attach event handlers
                $('.scan-toggle').on('click', function(e) {
                    e.preventDefault();
                    $('.scan-again-container').slideToggle(200);
                });
                
                $('.scan-blocks').on('click', function() {
                    $(this).trigger('click');
                });
                
                return;
            }
            
            const endIndex = Math.min(startIndex + batchSize, blocks.length);
            const batch = blocks.slice(startIndex, endIndex);
            let batchCompleted = 0;
            
            // Process each block in the batch
            batch.forEach(function(block) {
                // Use the working method from the filter functionality
                checkBlockUsage(block.blockName, block.searchPattern, function(isUsed, usageCount) {
                    // Update the UI with the result
                    block.element.attr('data-usage-status', isUsed ? 'used' : 'unused');
                    
                    // Update usage count display
                    const $countCell = block.element.find('.usage-count-cell');
                    $countCell.html(`<span class="usage-count">${usageCount}</span>`);
                    
                    // Apply appropriate class based on count
                    const $count = $countCell.find('.usage-count');
                    if (usageCount > 10) {
                        $count.attr('data-count', 'high');
                    } else if (usageCount > 0) {
                        $count.attr('data-count', 'medium');
                    } else {
                        $count.attr('data-count', 'low');
                    }
                    
                    // Update completion status
                    processedBlocks++;
                    batchCompleted++;
                    
                    // Update progress bar
                    const percentage = Math.round((processedBlocks / totalBlocks) * 100);
                    $('.scan-percentage').text(percentage + '%');
                    $('.scan-progress-bar').css('width', percentage + '%');
                    
                    // If all in this batch are done, process next batch
                    if (batchCompleted === batch.length) {
                        setTimeout(function() {
                            processScanBatch(blocks, endIndex, batchSize, processedBlocks, totalBlocks);
                        }, 100); // Small delay to avoid overloading the server
                    }
                });
            });
        }
    });
})(jQuery);