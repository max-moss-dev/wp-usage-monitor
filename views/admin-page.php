<div class="wrap usage-monitor-container">
    <div class="usage-monitor-main">
        <h1><?php echo esc_html__('Block Usage', 'usage-monitor'); ?></h1>
        
        <?php if (empty($blocks)) : ?>
            <div class="notice notice-warning">
                <p><?php echo esc_html__('No Gutenberg blocks found.', 'usage-monitor'); ?></p>
            </div>
        <?php else : ?>
            <div class="usage-monitor-filters">
                <div class="filter-blocks">
                    <span class="filter-label"><?php echo esc_html__('Filter:', 'usage-monitor'); ?></span>
                    <a href="#" class="filter-link active" data-filter="all"><?php echo esc_html__('All', 'usage-monitor'); ?></a> |
                    <a href="#" class="filter-link" data-filter="used"><?php echo esc_html__('Used', 'usage-monitor'); ?></a> |
                    <a href="#" class="filter-link" data-filter="unused"><?php echo esc_html__('Unused', 'usage-monitor'); ?></a>
                    <span class="separator">|</span>
                    <a href="#" class="settings-link" data-tab="settings"><?php echo esc_html__('Settings', 'usage-monitor'); ?></a>
                </div>
            </div>
            
            <?php 
                $no_stats = empty($block_usage_stats);
                $scan_needed = $needs_rescan || $no_stats || $was_reactivated;
            ?>
            
            <div class="usage-monitor-scan <?php echo $scan_needed ? 'scan-needed' : ''; ?>">
                <?php if ($scan_needed): ?>
                    <?php if ($needs_rescan): ?>
                    <div class="notice notice-warning inline">
                        <p>
                            <strong><?php echo esc_html__('Content has been updated since the last scan.', 'usage-monitor'); ?></strong> 
                            <?php echo esc_html__('The usage statistics may be outdated.', 'usage-monitor'); ?>
                        </p>
                    </div>
                    <?php elseif ($was_reactivated): ?>
                    <div class="notice notice-warning inline">
                        <p>
                            <strong><?php echo esc_html__('Plugin was deactivated and reactivated since the last scan.', 'usage-monitor'); ?></strong> 
                            <?php echo esc_html__('Content changes may have occurred while the plugin was inactive.', 'usage-monitor'); ?>
                        </p>
                    </div>
                    <?php elseif ($no_stats): ?>
                    <div class="notice notice-info inline">
                        <p>
                            <strong><?php echo esc_html__('No usage statistics available yet.', 'usage-monitor'); ?></strong> 
                            <?php echo esc_html__('Run a scan to analyze which blocks are being used.', 'usage-monitor'); ?>
                        </p>
                    </div>
                    <?php endif; ?>
                    
                    <button type="button" class="button button-primary scan-blocks">
                        <?php echo esc_html__('Scan usage statistics', 'usage-monitor'); ?>
                    </button>
                    <p class="scan-description">
                        <?php echo esc_html__('This will scan your content and calculate accurate usage statistics for all blocks.', 'usage-monitor'); ?>
                    </p>
                <?php else: ?>
                    <?php if (isset($last_scan) && $last_scan > 0): ?>
                    <div class="scan-status-ok">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <span class="scan-last-run">
                            <?php echo esc_html__('Statistics up to date. Last scan:', 'usage-monitor'); ?> <?php echo esc_html(human_time_diff($last_scan, time())); ?> <?php echo esc_html__('ago', 'usage-monitor'); ?>
                        </span>
                        <button type="button" class="button button-secondary scan-toggle"><?php echo esc_html__('Scan Again', 'usage-monitor'); ?></button>
                        <div class="scan-again-container" style="display: none;">
                            <button type="button" class="button button-secondary scan-blocks">
                                <?php echo esc_html__('Scan usage statistics', 'usage-monitor'); ?>
                            </button>
                            <p class="scan-description">
                                <?php echo esc_html__('This will scan your content and calculate accurate usage statistics for all blocks.', 'usage-monitor'); ?>
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
                <?php 
                    // Count used and total blocks in this section
                    $section_total = count($prefix_blocks);
                    $section_used = 0;
                    
                    foreach ($prefix_blocks as $block_name => $block) {
                        $usage_count = isset($block_usage_stats[$block_name]) ? $block_usage_stats[$block_name] : 0;
                        if ($usage_count > 0) {
                            $section_used++;
                        }
                    }
                    
                    $display_prefix = ucfirst($prefix); 
                ?>
                <div class="block-group-container">
                    <h2 class="block-group-title">
                        <?php 
                            /* translators: %s: Block category or group name */
                            echo esc_html(sprintf(__('%s Blocks', 'usage-monitor'), $display_prefix)); 
                        ?>
                        <span class="block-count">
                            <span class="used-count"><?php echo esc_attr($section_used); ?></span> / <span class="total-count"><?php echo esc_attr($section_total); ?></span> <?php echo esc_html__('used', 'usage-monitor'); ?>
                        </span>
                    </h2>
                    <table class="wp-list-table widefat fixed striped block-group <?php echo esc_attr($prefix); ?>-blocks">
                        <thead>
                            <tr>
                                <th class="block-title-column"><?php echo esc_html__('Block Title', 'usage-monitor'); ?></th>
                                <th class="block-name-column"><?php echo esc_html__('Block Name', 'usage-monitor'); ?></th>
                                <th class="usage-count-column"><?php echo esc_html__('Usage Count', 'usage-monitor'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($prefix_blocks)) : ?>
                            <tr>
                                <td><?php 
                                    /* translators: %s: Block category or group name in lowercase */
                                    echo esc_html(sprintf(__('No %s blocks found.', 'usage-monitor'), strtolower($display_prefix))); 
                                ?></td>
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
                                            <span class="usage-count" data-count="<?php echo esc_attr($usage_count > 10 ? 'high' : ($usage_count > 0 ? 'medium' : 'low')); ?>"><?php echo esc_attr($usage_count); ?></span>
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
        
        <!-- Settings section hidden by default -->
        <div class="settings-panel">
            <h3><?php echo __('Plugin Settings', 'usage-monitor'); ?></h3>
            <form id="usage-monitor-settings-form">
                <div class="form-field">
                    <input type="radio" name="keep_data" value="yes" <?php checked($keep_data, 'yes'); ?>>
                    <label>
                        <?php echo __('Keep data when plugin is uninstalled (recommended)', 'usage-monitor'); ?>
                    </label>
                    <p class="description"><?php echo esc_html__('Your block usage data will be preserved if you uninstall the plugin.', 'usage-monitor'); ?></p>
                </div>
                <div class="form-field">
                    <label>
                        <input type="radio" name="keep_data" value="no" <?php checked($keep_data, 'no'); ?>>
                        <?php echo esc_html__('Remove all data when plugin is uninstalled', 'usage-monitor'); ?>
                    </label>
                    <p class="description"><?php echo esc_html__('All block usage data will be permanently deleted when the plugin is uninstalled.', 'usage-monitor'); ?></p>
                </div>
                <div class="form-field">
                    <button type="submit" class="button button-primary"><?php echo esc_html__('Save Settings', 'usage-monitor'); ?></button>
                    <span class="settings-saved"><?php echo esc_html__('Settings saved!', 'usage-monitor'); ?></span>
                </div>
            </form>
        </div>
    </div>
    
    <div class="usage-monitor-sidebar" style="display: none;">
        <div class="sidebar-header">
            <h3 class="sidebar-title"><?php echo esc_html__('Posts with', 'usage-monitor'); ?> <span id="selected-block-title"></span></h3>
            <button class="close-sidebar button"><?php echo esc_html__('Close', 'usage-monitor'); ?></button>
        </div>
        <div class="sidebar-content">
            <div class="sidebar-loading" style="display: none;">
                <span class="spinner is-active"></span> <?php echo esc_html__('Loading...', 'usage-monitor'); ?>
            </div>
            <div class="sidebar-results"></div>
        </div>
    </div>
</div>