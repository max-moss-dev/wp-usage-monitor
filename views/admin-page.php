<div class="wrap block-usage-container">
    <div class="block-usage-main">
        <h1><?php echo esc_html__('Block Usage', 'block-usage'); ?></h1>
        
        <?php if (empty($blocks)) : ?>
            <div class="notice notice-warning">
                <p><?php echo esc_html__('No Gutenberg blocks found.', 'block-usage'); ?></p>
            </div>
        <?php else : ?>
            <div class="block-usage-filters">
                <div class="filter-blocks">
                    <span class="filter-label">Filter:</span>
                    <a href="#" class="filter-link active" data-filter="all">All</a> |
                    <a href="#" class="filter-link" data-filter="used">Used</a> |
                    <a href="#" class="filter-link" data-filter="unused">Unused</a>
                </div>
            </div>
            
            <?php 
            // Check if scan is needed (content updated since last scan, plugin reactivated, or no stats available)
            $no_stats = empty($block_usage_stats);
            $scan_needed = $needs_rescan || $no_stats || $was_reactivated;
            ?>
            
            <div class="block-usage-scan <?php echo $scan_needed ? 'scan-needed' : ''; ?>">
                <?php if ($scan_needed): ?>
                    <?php if ($needs_rescan): ?>
                    <div class="notice notice-warning inline">
                        <p>
                            <strong>Content has been updated since the last scan.</strong> 
                            The usage statistics may be outdated.
                        </p>
                    </div>
                    <?php elseif ($was_reactivated): ?>
                    <div class="notice notice-warning inline">
                        <p>
                            <strong>Plugin was deactivated and reactivated since the last scan.</strong> 
                            Content changes may have occurred while the plugin was inactive.
                        </p>
                    </div>
                    <?php elseif ($no_stats): ?>
                    <div class="notice notice-info inline">
                        <p>
                            <strong>No usage statistics available yet.</strong> 
                            Run a scan to analyze which blocks are being used.
                        </p>
                    </div>
                    <?php endif; ?>
                    
                    <button type="button" class="button button-primary scan-blocks">
                        Scan usage statistics
                    </button>
                    <p class="scan-description">
                        This will scan your content and calculate accurate usage statistics for all blocks.
                    </p>
                <?php else: ?>
                    <?php if (isset($last_scan) && $last_scan > 0): ?>
                    <div class="scan-status-ok">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <span class="scan-last-run">
                            Statistics up to date. Last scan: <?php echo esc_html(human_time_diff($last_scan, time())); ?> ago
                        </span>
                        <button type="button" class="button button-secondary scan-toggle">Scan Again</button>
                        <div class="scan-again-container" style="display: none;">
                            <button type="button" class="button button-secondary scan-blocks">
                                Scan usage statistics
                            </button>
                            <p class="scan-description">
                                This will scan your content and calculate accurate usage statistics for all blocks.
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <?php 
            // Group blocks by their prefix
            $block_groups = [];
            
            foreach ($blocks as $block_name => $block) {
                // Extract the prefix from the block name (everything before the first slash)
                $prefix_end = strpos($block_name, '/');
                if ($prefix_end !== false) {
                    $prefix = substr($block_name, 0, $prefix_end);
                } else {
                    // If no slash found, use 'other' as the prefix
                    $prefix = 'other';
                }
                
                // Add the block to its prefix group
                if (!isset($block_groups[$prefix])) {
                    $block_groups[$prefix] = [];
                }
                $block_groups[$prefix][$block_name] = $block;
            }
            
            // Sort the groups alphabetically by prefix
            ksort($block_groups);
            ?>
            
            <!-- Block Groups Sections -->
            <?php foreach ($block_groups as $prefix => $prefix_blocks) : ?>
                <?php $display_prefix = ucfirst($prefix); ?>
                <div class="block-group-container">
                    <h2 class="block-group-title"><?php echo esc_html(sprintf(__('%s Blocks', 'block-usage'), $display_prefix)); ?></h2>
                    <table class="wp-list-table widefat fixed striped block-group <?php echo esc_attr($prefix); ?>-blocks">
                        <thead>
                            <tr>
                                <th class="block-title-column">Block Title</th>
                                <th class="block-name-column">Block Name</th>
                                <th class="usage-count-column">Usage Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($prefix_blocks)) : ?>
                            <tr>
                                <td><?php echo esc_html(sprintf(__('No %s blocks found.', 'block-usage'), strtolower($display_prefix))); ?></td>
                            </tr>
                            <?php else : ?>
                                <?php foreach ($prefix_blocks as $block_name => $block) : ?>
                                    <?php
                                    // Format block name as fallback when title is not available
                                    $name_parts = explode('/', $block_name);
                                    $formatted_name = '';
                                    if (isset($name_parts[1])) {
                                        $formatted_name = ucfirst(str_replace('-', ' ', $name_parts[1]));
                                    } else {
                                        $formatted_name = ucfirst(str_replace('-', ' ', $block_name));
                                    }
                                    $display_title = isset($block->title) && !empty($block->title) ? $block->title : $formatted_name;
                                    
                                    // Get the search pattern for this block
                                    $search_pattern = $block_name;
                                    if (strpos($block_name, 'core/') === 0) {
                                        $search_pattern = substr($block_name, 5);
                                    }
                                    
                                    // Check if we have saved usage data
                                    $usage_count = isset($block_usage_stats[$block_name]) ? (int) $block_usage_stats[$block_name] : null;
                                    $usage_status = null;
                                    
                                    if ($usage_count !== null) {
                                        $usage_status = $usage_count > 0 ? 'used' : 'unused';
                                    }
                                    ?>
                                <tr class="block-row" data-block-name="<?php echo esc_attr($block_name); ?>" data-usage-status="<?php echo $usage_status ? esc_attr($usage_status) : 'loading'; ?>">
                                    <td class="block-title-cell">
                                        <a href="#" class="block-title-link" 
                                        data-block-name="<?php echo esc_attr($display_title); ?>" 
                                        data-search-pattern="<?php echo esc_attr($search_pattern); ?>">
                                            <?php echo esc_html($display_title); ?>
                                        </a>
                                    </td>
                                    <td class="block-name-cell">
                                        <code><?php echo esc_html($block_name); ?></code>
                                    </td>
                                    <td class="usage-count-cell">
                                        <?php if ($usage_count !== null): ?>
                                            <span class="usage-count" data-count="<?php echo $usage_count > 10 ? 'high' : ($usage_count > 0 ? 'medium' : 'low'); ?>"><?php echo $usage_count; ?></span>
                                        <?php else: ?>
                                            <span class="usage-count-placeholder">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div class="block-usage-sidebar" style="display: none;">
        <div class="sidebar-header">
            <h3 class="sidebar-title"><?php echo esc_html__('Posts with', 'block-usage'); ?> <span id="selected-block-title"></span></h3>
            <button class="close-sidebar button"><?php echo esc_html__('Close', 'block-usage'); ?></button>
        </div>
        <div class="sidebar-content">
            <div class="sidebar-loading" style="display: none;">
                <span class="spinner is-active"></span> <?php echo esc_html__('Loading...', 'block-usage'); ?>
            </div>
            <div class="sidebar-results"></div>
        </div>
    </div>
</div>