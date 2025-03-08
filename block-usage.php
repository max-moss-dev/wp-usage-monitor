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