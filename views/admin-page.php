<div class="wrap">
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
                            ?>
                        <tr>
                            <td><?php echo esc_html(isset($block->title) && !empty($block->title) ? $block->title : $formatted_name); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endforeach; ?>
    <?php endif; ?>
</div>