<?php
// If uninstall.php is not called by WordPress, exit
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
$wpdb->query("DROP TABLE IF EXISTS $table_name");

// Delete all options
delete_option('block_usage_activation_time');
delete_option('block_usage_deactivation_time');
delete_option('block_usage_last_scan');
delete_option('block_usage_content_updated');
delete_option('usage_monitor_keep_data'); 