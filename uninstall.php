<?php
/**
 * Uninstall script for Usage Monitor plugin
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check if user wants to keep data (default to yes if setting doesn't exist)
$keep_data = get_option('usage_monitor_keep_data', 'yes') === 'yes';

// If user wants to keep data, exit
if ($keep_data) {
    return;
}

// Delete custom database table
global $wpdb;
$table_name = $wpdb->prefix . 'block_usage_stats';

// Include WordPress database upgrade functionality
require_once ABSPATH . 'wp-admin/includes/upgrade.php';

// Direct DB operations are expected in uninstall scripts
// phpcs:disable WordPress.DB.DirectDatabaseQuery
// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query("DROP TABLE IF EXISTS " . esc_sql($table_name));
// phpcs:enable

// Delete all plugin-related options
$options = array(
    'block_usage_activation_time',
    'block_usage_deactivation_time',
    'block_usage_last_scan',
    'block_usage_content_updated',
    'usage_monitor_keep_data'
);

foreach ($options as $option) {
    delete_option($option);
} 