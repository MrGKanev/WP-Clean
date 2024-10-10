<?php

/**
 * Plugin Name: WP-Clean
 * Plugin URI: https://github.com/open-wp-club/wp-clean/
 * Description: A plugin to clean up WordPress content and WooCommerce data.
 * Version: 1.0.0
 * Author: Gabriel Kanev from Open WP Club
 * Author URI: https://openwpclub.com
 * License: MIT
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: wp-clean
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

define('WP_CLEAN_VERSION', '1.2.0');
define('WP_CLEAN_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_CLEAN_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once WP_CLEAN_PLUGIN_DIR . 'includes/class-wp-clean-admin.php';
require_once WP_CLEAN_PLUGIN_DIR . 'includes/class-wp-clean-deletion.php';
require_once WP_CLEAN_PLUGIN_DIR . 'includes/class-wp-clean-database.php';

// Initialize the plugin
function wp_clean_init()
{
    $wp_clean_admin = new WP_Clean_Admin();
    $wp_clean_admin->init();
}
add_action('plugins_loaded', 'wp_clean_init');
