<?php
/**
 * Plugin Name: Block Usage
 * Description: A plugin to list all registered Gutenberg blocks in the WordPress admin.
 * Version: 1.0.0
 * Author: Max Moss
 * Text Domain: usage-monitor
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'block_usage_activated');
register_deactivation_hook(__FILE__, 'block_usage_deactivated');

/**
 * Record plugin activation time
 */
function block_usage_activated() {
    // Store activation time
    update_option('block_usage_activation_time', time());
}

/**
 * Record plugin deactivation time
 */
function block_usage_deactivated() {
    // Store deactivation time
    update_option('block_usage_deactivation_time', time());
}

/**
 * Main Block Usage class
 */
class Block_Usage {
    /**
     * Database table name
     *
     * @var string
     */
    private $table_name;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        
        // Set table name
        $this->table_name = $wpdb->prefix . 'block_usage_stats';
        
        // Hook into WordPress
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Add filter for plugin action links
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_plugin_action_links'));
        
        // AJAX handlers
        add_action('wp_ajax_block_usage_find_posts', array($this, 'find_posts_with_block'));
        add_action('wp_ajax_block_usage_check_usage', array($this, 'check_block_usage'));
        add_action('wp_ajax_block_usage_record_scan', array($this, 'record_scan_timestamp'));
        
        // Track content updates
        add_action('save_post', array($this, 'track_content_update'), 10, 3);
    }

    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_management_page(
            __('Block Usage', 'block-usage'),
            __('Block Usage', 'block-usage'),
            'manage_options',
            'block-usage',
            array($this, 'render_admin_page')
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on our admin page
        if ('tools_page_block-usage' !== $hook) {
            return;
        }

        // Ensure dashicons are loaded
        wp_enqueue_style('dashicons');
        
        // Enqueue styles
        wp_enqueue_style(
            'block-usage-admin',
            plugin_dir_url(__FILE__) . 'assets/css/admin.css',
            array('dashicons'),
            '1.0.0'
        );

        // Enqueue scripts
        wp_enqueue_script(
            'block-usage-admin',
            plugin_dir_url(__FILE__) . 'assets/js/admin.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        // Get stored usage stats
        $block_usage_stats = $this->get_block_usage_stats();
        
        // Localize script with nonce and other data
        wp_localize_script(
            'block-usage-admin',
            'blockUsageData',
            array(
                'nonce' => wp_create_nonce('block_usage_nonce'),
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'usageStats' => $block_usage_stats
            )
        );
    }

    /**
     * Get all registered blocks
     * 
     * @return array Array of registered blocks
     */
    public function get_registered_blocks()  {
        // Check if WP_Block_Type_Registry class exists
        if (!class_exists('WP_Block_Type_Registry')) {
            return array();
        }

        // Get all registered blocks
        $registry = WP_Block_Type_Registry::get_instance();
        $blocks = $registry->get_all_registered();

        return $blocks;
    }

    /**
     * Get all stored block usage statistics
     * 
     * @return array Block usage statistics
     */
    public function get_block_usage_stats() {
        global $wpdb;
        
        // Make sure table exists
        $this->maybe_create_table();
        
        // Get all stats from database
        $stats = array();
        $results = $wpdb->get_results("SELECT block_name, usage_count FROM {$this->table_name}");
        
        if ($results) {
            foreach ($results as $row) {
                $stats[$row->block_name] = (int) $row->usage_count;
            }
        }
        
        return $stats;
    }

    /**
     * Track when content is updated to notify about potential outdated stats
     *
     * @param int    $post_id The ID of the post being saved
     * @param object $post    The post object
     * @param bool   $update  Whether this is an existing post being updated
     */
    public function track_content_update($post_id, $post, $update) {
        // Skip if autosave, revision, or certain post types
        if (wp_is_post_autosave($post_id) || 
            wp_is_post_revision($post_id) || 
            in_array($post->post_type, array('attachment', 'nav_menu_item', 'custom_css', 'customize_changeset'))) {
            return;
        }
        
        // Store the timestamp of the last content update
        update_option('block_usage_content_updated', time());
    }

    /**
     * Render admin page
     */
    public function render_admin_page() {
        // Get all registered blocks
        $blocks = $this->get_registered_blocks();
        
        // Get stored usage stats
        $block_usage_stats = $this->get_block_usage_stats();
        
        // Check if content has been updated since last scan
        $last_scan = get_option('block_usage_last_scan', 0);
        $last_update = get_option('block_usage_content_updated', 0);
        $needs_rescan = ($last_update > $last_scan);
        
        // Check if plugin was deactivated and reactivated since last scan
        $activation_time = get_option('block_usage_activation_time', 0);
        $deactivation_time = get_option('block_usage_deactivation_time', 0);
        $was_reactivated = ($activation_time > $last_scan && $deactivation_time > 0);
        
        // Start output buffering
        ob_start();
        
        // Include the admin view
        include plugin_dir_path(__FILE__) . 'views/admin-page.php';
        
        // Get the buffered content and clean the buffer
        $output = ob_get_clean();
        
        // Output the admin page
        echo $output;
    }
    
    /**
     * Find posts containing a specific block
     */
    public function find_posts_with_block() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'block_usage_nonce')) {
            wp_send_json_error(array('message' => 'Invalid security token.'));
            return;
        }
        
        // Get parameters
        $block_name = isset($_POST['block_name']) ? sanitize_text_field($_POST['block_name']) : '';
        $search_pattern = isset($_POST['search_pattern']) ? sanitize_text_field($_POST['search_pattern']) : '';
        
        if (empty($block_name) || empty($search_pattern)) {
            wp_send_json_error(array('message' => 'Missing required parameters.'));
            return;
        }
        
        // Get posts with this block
        $posts = $this->get_posts_with_block($search_pattern);
        
        wp_send_json_success(array(
            'posts' => $posts,
            'count' => count($posts),
            'search_pattern' => $search_pattern
        ));
    }
    
    /**
     * Check if a block is used in any posts
     */
    public function check_block_usage() {
        global $wpdb;
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'block_usage_nonce')) {
            wp_send_json_error(array('message' => 'Invalid security token.'));
            return;
        }
        
        // Get parameters
        $block_name = isset($_POST['block_name']) ? sanitize_text_field($_POST['block_name']) : '';
        $search_pattern = isset($_POST['search_pattern']) ? sanitize_text_field($_POST['search_pattern']) : '';
        
        if (empty($block_name)) {
            wp_send_json_error(array('message' => 'Missing required parameters.'));
            return;
        }
        
        // Use search pattern if available or create one from block name
        if (empty($search_pattern)) {
            $search_pattern = $block_name;
            if (strpos($block_name, 'core/') === 0) {
                $search_pattern = substr($block_name, 5); // Remove 'core/' prefix
            }
        }
        
        // Build the block pattern
        $block_pattern = '<!-- wp:' . $search_pattern;
        $like_pattern = '%' . $wpdb->esc_like($block_pattern) . '%';
        
        // Direct database query to count all instances (posts and templates)
        $query = $wpdb->prepare(
            "SELECT COUNT(ID) FROM {$wpdb->posts} 
            WHERE post_status = 'publish' 
            AND post_content LIKE %s",
            $like_pattern
        );
        
        $usage_count = (int) $wpdb->get_var($query);
        
        // Get a breakdown of where the block is used
        $breakdown_query = $wpdb->prepare(
            "SELECT post_type, COUNT(*) as count 
            FROM {$wpdb->posts} 
            WHERE post_status = 'publish' 
            AND post_content LIKE %s 
            GROUP BY post_type",
            $like_pattern
        );
        
        $usage_breakdown = $wpdb->get_results($breakdown_query);
        $breakdown_data = array();
        
        if ($usage_breakdown) {
            foreach ($usage_breakdown as $item) {
                $post_type_obj = get_post_type_object($item->post_type);
                $type_label = $post_type_obj ? $post_type_obj->labels->singular_name : $item->post_type;
                
                // Special handling for site editor content
                if ($item->post_type === 'wp_template') {
                    $type_label = 'Templates';
                } else if ($item->post_type === 'wp_template_part') {
                    $type_label = 'Template Parts';
                }
                
                $breakdown_data[$item->post_type] = array(
                    'count' => (int) $item->count,
                    'label' => $type_label
                );
            }
        }
        
        // Update database with usage count
        $this->update_block_usage_stats($block_name, $usage_count);
        
        wp_send_json_success(array(
            'is_used' => $usage_count > 0,
            'usage_count' => $usage_count,
            'block_name' => $block_name,
            'breakdown' => $breakdown_data
        ));
    }
    
    /**
     * Create database table if it doesn't exist
     */
    private function maybe_create_table() {
        global $wpdb;
        
        // Check if table exists
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $this->table_name
        )) === $this->table_name;
        
        if (!$table_exists) {
            // Table doesn't exist, create it
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $this->table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                block_name varchar(255) NOT NULL,
                usage_count int(11) NOT NULL DEFAULT 0,
                last_updated datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                UNIQUE KEY block_name (block_name)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }
    
    /**
     * Update block usage statistics in the database
     * 
     * @param string $block_name The block name
     * @param int $usage_count The usage count
     */
    private function update_block_usage_stats($block_name, $usage_count) {
        global $wpdb;
        
        // Update or insert the block usage data
        $wpdb->replace(
            $this->table_name,
            array(
                'block_name' => $block_name,
                'usage_count' => $usage_count,
                'last_updated' => current_time('mysql')
            ),
            array('%s', '%d', '%s')
        );
    }

    /**
     * Get posts containing a specific block
     * 
     * @param string $search_pattern Block search pattern
     * @param int $limit Maximum number of posts to return
     * @return array Array of post data
     */
    private function get_posts_with_block($search_pattern, $limit = 50) {
        global $wpdb;
        
        // Construct the search pattern for the block comment
        $block_comment = '<!-- wp:' . $search_pattern;
        $like_pattern = '%' . $wpdb->esc_like($block_comment) . '%';
        
        // Direct database query to search posts and templates
        $query = $wpdb->prepare(
            "SELECT ID, post_title, post_type 
            FROM {$wpdb->posts} 
            WHERE post_status = 'publish' 
            AND post_content LIKE %s
            ORDER BY post_type, post_title
            LIMIT %d",
            $like_pattern,
            $limit
        );
        
        $results = $wpdb->get_results($query);
        $posts = array();
        
        if ($results) {
            foreach ($results as $result) {
                // Get post type object to get nice label
                $post_type_obj = get_post_type_object($result->post_type);
                
                // Set label based on post type
                $type_label = $post_type_obj ? $post_type_obj->labels->singular_name : $result->post_type;
                
                // Add special handling for site editor templates
                if ($result->post_type === 'wp_template') {
                    $type_label = 'Template';
                } else if ($result->post_type === 'wp_template_part') {
                    $type_label = 'Template Part';
                }
                
                $title = $result->post_title ? $result->post_title : __('(No Title)', 'block-usage');
                
                // Get correct edit URL based on post type
                $edit_url = get_edit_post_link($result->ID);
                
                // Special handling for site editor URLs
                if ($result->post_type === 'wp_template' || $result->post_type === 'wp_template_part') {
                    // For site editor, we need to extract the template slug parts
                    
                    // Get raw data from database to ensure we get the real slug
                    $template_data = $wpdb->get_row(
                        $wpdb->prepare(
                            "SELECT post_name, post_content FROM {$wpdb->posts} WHERE ID = %d",
                            $result->ID
                        )
                    );
                    
                    // First approach: extract theme from template slug
                    $slug = $template_data ? $template_data->post_name : $result->post_name;
                    
                    // Default theme
                    $theme_slug = wp_get_theme()->get_stylesheet();
                    
                    // Check if slug already contains theme info (theme//template)
                    if (strpos($slug, '//') !== false) {
                        list($theme_part, $template_part) = explode('//', $slug, 2);
                        // Use the theme part if it exists
                        if (!empty($theme_part)) {
                            $theme_slug = $theme_part;
                        }
                    } else {
                        // Template name without theme
                        $template_part = $slug;
                    }
                    
                    // Create properly formatted template ID for site editor
                    $template_id = $theme_slug . '//' . $template_part;
                    
                    // URL encode for query parameter
                    $encoded_id = str_replace('//', '%2F%2F', $template_id);
                    
                    // Set the site editor URL with the correct format
                    $edit_url = admin_url('site-editor.php?postId=' . $encoded_id . '&postType=' . $result->post_type . '&canvas=edit');
                }
                
                $posts[] = array(
                    'ID' => $result->ID,
                    'title' => $title,
                    'edit_url' => $edit_url,
                    'post_type' => $result->post_type,
                    'post_type_label' => $type_label
                );
            }
        }
        
        return $posts;
    }

    /**
     * Add plugin action links
     * 
     * @param array $links Array of plugin action links
     * @return array Modified array of plugin action links
     */
    public function add_plugin_action_links($links) {
        // Add a link to the block usage page
        $block_usage_link = '<a href="' . admin_url('tools.php?page=block-usage') . '">' . __('View Blocks', 'block-usage') . '</a>';
        
        // Add the link at the beginning of the array
        array_unshift($links, $block_usage_link);
        
        return $links;
    }

    /**
     * Record when a scan was completed
     */
    public function record_scan_timestamp() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'block_usage_nonce')) {
            wp_send_json_error(array('message' => 'Invalid security token.'));
            return;
        }
        
        // Update the scan timestamp
        update_option('block_usage_last_scan', time());
        
        // Clear activation notice after a successful scan
        // We keep the deactivation time for future comparisons
        update_option('block_usage_activation_time', 0);
        
        wp_send_json_success(array('message' => 'Scan timestamp recorded.'));
    }
}

// Initialize the plugin
$block_usage = new Block_Usage();