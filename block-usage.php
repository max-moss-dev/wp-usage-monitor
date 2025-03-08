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

/**
 * Main Block Usage class
 */
class Block_Usage {
    /**
     * Constructor
     */
    public function __construct() {
        // Hook into WordPress
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Add filter for plugin action links
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_plugin_action_links'));
        
        // AJAX handlers
        add_action('wp_ajax_block_usage_find_posts', array($this, 'find_posts_with_block'));
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

        // Enqueue styles
        wp_enqueue_style(
            'block-usage-admin',
            plugin_dir_url(__FILE__) . 'assets/css/admin.css',
            array(),
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
        
        // Localize script with nonce and other data
        wp_localize_script(
            'block-usage-admin',
            'blockUsageData',
            array(
                'nonce' => wp_create_nonce('block_usage_nonce'),
                'ajaxUrl' => admin_url('admin-ajax.php')
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
     * Render admin page
     */
    public function render_admin_page()  {
        // Get all registered blocks
        $blocks = $this->get_registered_blocks();

        // Start output buffering
        ob_start();
        
        // Include the admin view
        include plugin_dir_path(__FILE__) . 'views/admin-page.php';
        
        // Get the buffered content and clean the buffer
        $output = ob_get_clean();
        
        // Echo the output
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
        
        // Construct the search pattern for the block comment
        $block_comment = '<!-- wp:' . $search_pattern;
        
        // Query posts containing this block
        $args = array(
            'posts_per_page' => 50,
            'post_type' => 'any',
            'post_status' => 'publish',
            's' => $block_comment,
            'orderby' => 'title',
            'order' => 'ASC'
        );
        
        $query = new WP_Query($args);
        $posts = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                
                // Get post type object to get nice label
                $post_type_obj = get_post_type_object(get_post_type());
                
                $posts[] = array(
                    'ID' => get_the_ID(),
                    'title' => get_the_title() ? get_the_title() : __('(No Title)', 'block-usage'),
                    'edit_url' => get_edit_post_link(),
                    'post_type' => get_post_type(),
                    'post_type_label' => $post_type_obj ? $post_type_obj->labels->singular_name : get_post_type()
                );
            }
            
            wp_reset_postdata();
        }
        
        wp_send_json_success(array(
            'posts' => $posts,
            'count' => count($posts),
            'search_pattern' => $block_comment
        ));
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
}

// Initialize the plugin
$block_usage = new Block_Usage();