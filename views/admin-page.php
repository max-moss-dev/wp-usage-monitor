<div class="wrap block-usage-container">
    <div class="block-usage-main">
        <h1><?php echo esc_html__('Block Usage', 'block-usage'); ?></h1>
        
        <?php if (empty($blocks)) : ?>
            <div class="notice notice-warning">
                <p><?php echo esc_html__('No Gutenberg blocks found.', 'block-usage'); ?></p>
            </div>
        <?php else : ?>
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
                // Format the prefix for display (capitalize first letter)
                $display_prefix = ucfirst($prefix);
                ?>
                <h2 class="block-group-title"><?php echo esc_html(sprintf(__('%s Blocks', 'block-usage'), $display_prefix)); ?></h2>
                <table class="wp-list-table widefat fixed striped block-group <?php echo esc_attr($prefix); ?>-blocks">
                    <thead>
                        <tr>
                            <th scope="col"><?php echo esc_html__('Block Title', 'block-usage'); ?></th>
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
                                
                                // Format search pattern for this block
                                $search_pattern = $block_name;
                                if (strpos($block_name, 'core/') === 0) {
                                    $search_pattern = substr($block_name, 5); // Remove 'core/' prefix
                                }
                                ?>
                            <tr>
                                <td>
                                    <a href="#" class="block-title-link" 
                                       data-block-name="<?php echo esc_attr($block_name); ?>" 
                                       data-search-pattern="<?php echo esc_attr($search_pattern); ?>">
                                        <?php echo esc_html($display_title); ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
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