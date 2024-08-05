<?php
/*
 * Plugin Name: WP-Clean
 * Plugin URI: https://github.com/MrGKanev/wp-clean/
 * Description: s
 * Version: 0.0.1
 * Author: Gabriel Kanev
 * Author URI: https://gkanev.com
 * License: MIT
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

// Add menu item under Tools
function mcd_add_menu_item()
{
    add_submenu_page(
        'tools.php',
        'Mass Content Deleter',
        'Mass Content Deleter',
        'manage_options',
        'mass-content-deleter',
        'mcd_admin_page'
    );
}
add_action('admin_menu', 'mcd_add_menu_item');

// Admin page content
function mcd_admin_page()
{
?>
    <div class="wrap">
        <h1>Mass Content Deleter</h1>
        <form method="post" action="">
            <?php wp_nonce_field('mcd_delete_action', 'mcd_nonce'); ?>
            <p>Select the content types you want to delete:</p>
            <label><input type="checkbox" name="delete_posts" value="1"> Posts</label><br>
            <label><input type="checkbox" name="delete_pages" value="1"> Pages</label><br>
            <label><input type="checkbox" name="delete_comments" value="1"> Comments</label><br>
            <label><input type="checkbox" name="delete_users" value="1"> Users (except admin)</label><br>
            <label><input type="checkbox" name="delete_terms" value="1"> Terms (categories, tags)</label><br>
            <input type="submit" name="mcd_submit" class="button button-primary" value="Delete Selected Content">
        </form>
        <div id="mcd-loader" style="display: none;">
            <p>Deleting content... Please wait.</p>
            <div class="spinner is-active"></div>
        </div>
    </div>
    <script>
        jQuery(document).ready(function($) {
            $('form').on('submit', function() {
                $('#mcd-loader').show();
            });
        });
    </script>
<?php
}

// Process deletion
function mcd_process_deletion()
{
    if (isset($_POST['mcd_submit']) && check_admin_referer('mcd_delete_action', 'mcd_nonce')) {
        if (isset($_POST['delete_posts'])) {
            $posts = get_posts(array('numberposts' => -1));
            foreach ($posts as $post) {
                wp_delete_post($post->ID, true);
            }
        }
        if (isset($_POST['delete_pages'])) {
            $pages = get_pages();
            foreach ($pages as $page) {
                wp_delete_post($page->ID, true);
            }
        }
        if (isset($_POST['delete_comments'])) {
            $comments = get_comments();
            foreach ($comments as $comment) {
                wp_delete_comment($comment->comment_ID, true);
            }
        }
        if (isset($_POST['delete_users'])) {
            $users = get_users(array('role__not_in' => array('administrator')));
            foreach ($users as $user) {
                wp_delete_user($user->ID);
            }
        }
        if (isset($_POST['delete_terms'])) {
            $taxonomies = get_taxonomies();
            foreach ($taxonomies as $taxonomy) {
                $terms = get_terms(array('taxonomy' => $taxonomy, 'hide_empty' => false));
                foreach ($terms as $term) {
                    wp_delete_term($term->term_id, $taxonomy);
                }
            }
        }
        add_action('admin_notices', 'mcd_admin_notice');
    }
}
add_action('admin_init', 'mcd_process_deletion');

// Admin notice for successful deletion
function mcd_admin_notice()
{
?>
    <div class="notice notice-success is-dismissible">
        <p>Selected content has been deleted successfully.</p>
    </div>
<?php
}
