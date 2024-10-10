<?php

class WP_Clean_Admin
{
  public function init()
  {
    add_action('admin_menu', array($this, 'add_menu_item'));
    add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
  }

  public function add_menu_item()
  {
    add_submenu_page(
      'tools.php',
      __('WP-Clean', 'wp-clean'),
      __('WP-Clean', 'wp-clean'),
      'manage_options',
      'wp-clean',
      array($this, 'render_admin_page')
    );
  }

  public function enqueue_admin_scripts($hook)
  {
    if ('tools_page_wp-clean' !== $hook) {
      return;
    }

    // Enqueue WordPress dashicons for our tooltip icons
    wp_enqueue_style('dashicons');

    // Enqueue our custom CSS
    wp_enqueue_style('wp-clean-admin', WP_CLEAN_PLUGIN_URL . 'assets/css/wp-clean-admin.css', array(), WP_CLEAN_VERSION);

    // Enqueue our custom JavaScript
    wp_enqueue_script('wp-clean-admin', WP_CLEAN_PLUGIN_URL . 'assets/js/wp-clean-admin.js', array('jquery'), WP_CLEAN_VERSION, true);

    // Localize script with our data
    wp_localize_script('wp-clean-admin', 'wpCleanAdmin', array(
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('wp_clean_nonce'),
      'confirm_deletion' => __('Are you sure you want to delete the selected content? This action cannot be undone.', 'wp-clean'),
      'ajax_error' => __('An error occurred. Please try again.', 'wp-clean'),
      'deletion_complete' => __('Deletion process completed.', 'wp-clean')
    ));
  }

  public function render_admin_page()
  {
    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions to access this page.', 'wp-clean'));
    }

    include WP_CLEAN_PLUGIN_DIR . 'views/admin-page.php';
  }
}
