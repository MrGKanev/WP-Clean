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
function emcd_add_menu_item()
{
    add_submenu_page(
        'tools.php',
        'WP-Clean',
        'WP-Clean',
        'manage_options',
        'WP-Clean',
        'emcd_admin_page'
    );
}
add_action('admin_menu', 'emcd_add_menu_item');

// Admin page content
function emcd_admin_page()
{
?>
    <div class="wrap">
        <h1>WP Clean</h1>
        <div id="emcd-main-form">
            <form method="post" action="" id="emcd-form">
                <?php wp_nonce_field('emcd_delete_action', 'emcd_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">WordPress Content</th>
                        <td>
                            <label><input type="checkbox" name="delete_posts" value="1"> Posts</label><br>
                            <label><input type="checkbox" name="delete_pages" value="1"> Pages</label><br>
                            <label><input type="checkbox" name="delete_comments" value="1"> Comments</label><br>
                            <label><input type="checkbox" name="delete_users" value="1"> Users (except admin)</label><br>
                            <label><input type="checkbox" name="delete_terms" value="1"> Terms (categories, tags)</label><br>
                        </td>
                    </tr>
                    <?php if (class_exists('WooCommerce')) : ?>
                        <tr>
                            <th scope="row">WooCommerce Content</th>
                            <td>
                                <label><input type="checkbox" name="delete_products" value="1"> Products</label><br>
                                <label><input type="checkbox" name="delete_orders" value="1"> Orders</label><br>
                                <label><input type="checkbox" name="delete_coupons" value="1"> Coupons</label><br>
                            </td>
                        </tr>
                    <?php endif; ?>
                </table>
                <p class="submit">
                    <input type="submit" name="emcd_submit" id="emcd-submit" class="button button-primary" value="Delete Selected Content">
                </p>
            </form>
        </div>
        <div id="emcd-progress" style="display: none;">
            <h2>Deletion Progress</h2>
            <div id="emcd-progress-bar">
                <div id="emcd-progress-bar-inner"></div>
            </div>
            <p id="emcd-progress-text"></p>
        </div>
    </div>
    <style>
        #emcd-progress-bar {
            width: 100%;
            background-color: #f0f0f0;
            padding: 3px;
            border-radius: 3px;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, .2);
        }

        #emcd-progress-bar-inner {
            width: 0;
            height: 20px;
            background-color: #0073aa;
            border-radius: 3px;
            transition: width 0.5s ease-in-out;
        }
    </style>
    <script>
        jQuery(document).ready(function($) {
            $('#emcd-form').on('submit', function(e) {
                e.preventDefault();
                if (!confirm('Are you sure you want to delete the selected content? This action cannot be undone.')) {
                    return;
                }
                $('#emcd-main-form').hide();
                $('#emcd-progress').show();
                processDelete();
            });

            function processDelete() {
                var data = $('#emcd-form').serialize();
                data += '&action=emcd_process_deletion';

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: data,
                    success: function(response) {
                        updateProgress(response.progress, response.message);
                        if (response.progress < 100) {
                            processDelete();
                        } else {
                            $('#emcd-progress-text').html('Deletion completed!');
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                        $('#emcd-main-form').show();
                        $('#emcd-progress').hide();
                    }
                });
            }

            function updateProgress(progress, message) {
                $('#emcd-progress-bar-inner').css('width', progress + '%');
                $('#emcd-progress-text').html(message);
            }
        });
    </script>
<?php
}

// AJAX handler for deletion process
function emcd_ajax_process_deletion()
{
    check_ajax_referer('emcd_delete_action', 'emcd_nonce');

    $total_items = 0;
    $deleted_items = 0;

    if (isset($_POST['delete_posts'])) {
        $posts = get_posts(array('numberposts' => -1, 'post_type' => 'post'));
        $total_items += count($posts);
        foreach ($posts as $post) {
            wp_delete_post($post->ID, true);
            $deleted_items++;
        }
    }

    if (isset($_POST['delete_pages'])) {
        $pages = get_pages();
        $total_items += count($pages);
        foreach ($pages as $page) {
            wp_delete_post($page->ID, true);
            $deleted_items++;
        }
    }

    if (isset($_POST['delete_comments'])) {
        $comments = get_comments();
        $total_items += count($comments);
        foreach ($comments as $comment) {
            wp_delete_comment($comment->comment_ID, true);
            $deleted_items++;
        }
    }

    if (isset($_POST['delete_users'])) {
        $users = get_users(array('role__not_in' => array('administrator')));
        $total_items += count($users);
        foreach ($users as $user) {
            wp_delete_user($user->ID);
            $deleted_items++;
        }
    }

    if (isset($_POST['delete_terms'])) {
        $taxonomies = get_taxonomies();
        foreach ($taxonomies as $taxonomy) {
            $terms = get_terms(array('taxonomy' => $taxonomy, 'hide_empty' => false));
            $total_items += count($terms);
            foreach ($terms as $term) {
                wp_delete_term($term->term_id, $taxonomy);
                $deleted_items++;
            }
        }
    }

    if (class_exists('WooCommerce')) {
        if (isset($_POST['delete_products'])) {
            $products = wc_get_products(array('limit' => -1));
            $total_items += count($products);
            foreach ($products as $product) {
                $product->delete(true);
                $deleted_items++;
            }
        }

        if (isset($_POST['delete_orders'])) {
            $orders = wc_get_orders(array('limit' => -1));
            $total_items += count($orders);
            foreach ($orders as $order) {
                $order->delete(true);
                $deleted_items++;
            }
        }

        if (isset($_POST['delete_coupons'])) {
            $coupons = get_posts(array('post_type' => 'shop_coupon', 'numberposts' => -1));
            $total_items += count($coupons);
            foreach ($coupons as $coupon) {
                wp_delete_post($coupon->ID, true);
                $deleted_items++;
            }
        }
    }

    $progress = ($total_items > 0) ? round(($deleted_items / $total_items) * 100) : 100;
    $message = "Deleted $deleted_items out of $total_items items";

    wp_send_json(array('progress' => $progress, 'message' => $message));
}
add_action('wp_ajax_emcd_process_deletion', 'emcd_ajax_process_deletion');
