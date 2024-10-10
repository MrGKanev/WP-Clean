<?php
class WP_Clean_Database
{
  public function __construct()
  {
    add_action('wp_ajax_wp_clean_optimize_database', array($this, 'optimize_database'));
  }

  public function optimize_database()
  {
    check_ajax_referer('wp_clean_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error(__('You do not have sufficient permissions to perform this action.', 'wp-clean'));
    }

    global $wpdb;
    $tables = $wpdb->get_results("SHOW TABLES");
    foreach ($tables as $table) {
      $table_name = array_values(get_object_vars($table))[0];
      $wpdb->query("OPTIMIZE TABLE $table_name");
    }

    wp_send_json_success(__('Database optimization completed successfully.', 'wp-clean'));
  }
}
