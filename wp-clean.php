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
function amcd_add_menu_item()
{
    add_submenu_page(
        'tools.php',
        'WP-Clean',
        'WP-Clean',
        'manage_options',
        'WP-Clean',
        'amcd_admin_page'
    );
}
add_action('admin_menu', 'amcd_add_menu_item');

// Admin page content
function amcd_admin_page()
{
?>
    <div class="wrap">
        <h1>WP-Clean</h1>
        <div id="amcd-main-form">
            <form method="post" action="" id="amcd-form">
                <?php wp_nonce_field('amcd_delete_action', 'amcd_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">WordPress Content</th>
                        <td>
                            <label><input type="checkbox" name="delete_posts" value="1"> Posts</label><br>
                            <label><input type="checkbox" name="delete_pages" value="1"> Pages</label><br>
                            <label><input type="checkbox" name="delete_comments" value="1"> Comments</label><br>
                            <label><input type="checkbox" name="delete_users" value="1"> Users (except admin)</label><br>
                            <label><input type="checkbox" name="delete_terms" value="1"> Terms (categories, tags)</label><br>
                            <label><input type="checkbox" name="delete_media" value="1"> Media</label><br>
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
                    <tr>
                        <th scope="row">Custom Post Types</th>
                        <td>
                            <?php
                            $custom_post_types = get_post_types(array('_builtin' => false), 'objects');
                            foreach ($custom_post_types as $pt) {
                                echo '<label><input type="checkbox" name="delete_cpt_' . esc_attr($pt->name) . '" value="1"> ' . esc_html($pt->label) . '</label><br>';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Date Range (optional)</th>
                        <td>
                            <label>From: <input type="date" name="date_from"></label>
                            <label>To: <input type="date" name="date_to"></label>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="amcd_submit" id="amcd-submit" class="button button-primary" value="Delete Selected Content">
                    <input type="submit" name="amcd_export" id="amcd-export" class="button" value="Export Before Delete">
                </p>
            </form>
        </div>
        <div id="amcd-progress" style="display: none;">
            <h2>Deletion Progress</h2>
            <div id="amcd-progress-bar">
                <div id="amcd-progress-bar-inner"></div>
            </div>
            <p id="amcd-progress-text"></p>
        </div>
    </div>
    <style>
        #amcd-progress-bar {
            width: 100%;
            background-color: #f0f0f0;
            padding: 3px;
            border-radius: 3px;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, .2);
        }

        #amcd-progress-bar-inner {
            width: 0;
            height: 20px;
            background-color: #0073aa;
            border-radius: 3px;
            transition: width 0.5s ease-in-out;
        }
    </style>
    <script>
        jQuery(document).ready(function($) {
            $('#amcd-form').on('submit', function(e) {
                e.preventDefault();
                if (!confirm('Are you sure you want to delete the selected content? This action cannot be undone.')) {
                    return;
                }
                $('#amcd-main-form').hide();
                $('#amcd-progress').show();
                processDelete();
            });

            $('#amcd-export').on('click', function(e) {
                e.preventDefault();
                exportData();
            });

            function processDelete() {
                var data = $('#amcd-form').serialize();
                data += '&action=amcd_process_deletion';

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: data,
                    success: function(response) {
                        updateProgress(response.progress, response.message);
                        if (response.progress < 100) {
                            processDelete();
                        } else {
                            $('#amcd-progress-text').html('Deletion completed! Optimizing database...');
                            optimizeDatabase();
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                        $('#amcd-main-form').show();
                        $('#amcd-progress').hide();
                    }
                });
            }

            function updateProgress(progress, message) {
                $('#amcd-progress-bar-inner').css('width', progress + '%');
                $('#amcd-progress-text').html(message);
            }

            function optimizeDatabase() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'amcd_optimize_database'
                    },
                    success: function(response) {
                        $('#amcd-progress-text').html('Process completed. Database optimized.');
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    }
                });
            }

            function exportData() {
                var data = $('#amcd-form').serialize();
                data += '&action=amcd_export_data';

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: data,
                    success: function(response) {
                        if (response.success) {
                            window.location.href = response.data.download_url;
                        } else {
                            alert('Export failed. Please try again.');
                        }
                    }
                });
            }
        });
    </script>
<?php
}

// AJAX handler for deletion process (update with batch processing)
function amcd_ajax_process_deletion()
{
    check_ajax_referer('amcd_delete_action', 'amcd_nonce');

    $batch_size = 50; // Adjust based on your needs
    $deleted_items = 0;
    $total_items = amcd_get_total_items();

    // Process posts (including custom post types)
    $post_types = get_post_types(array('public' => true));
    foreach ($post_types as $post_type) {
        if (isset($_POST["delete_{$post_type}"]) || isset($_POST["delete_cpt_{$post_type}"])) {
            $args = array(
                'post_type' => $post_type,
                'posts_per_page' => $batch_size,
                'post_status' => 'any',
            );
            if (!empty($_POST['date_from'])) {
                $args['date_query'][0]['after'] = sanitize_text_field($_POST['date_from']);
            }
            if (!empty($_POST['date_to'])) {
                $args['date_query'][0]['before'] = sanitize_text_field($_POST['date_to']);
            }
            $posts = get_posts($args);
            foreach ($posts as $post) {
                wp_delete_post($post->ID, true);
                $deleted_items++;
                amcd_log_deletion('post', $post->ID);
            }
        }
    }

    // Process comments
    if (isset($_POST['delete_comments'])) {
        $args = array(
            'number' => $batch_size,
            'status' => 'any',
        );
        if (!empty($_POST['date_from'])) {
            $args['date_query'][0]['after'] = sanitize_text_field($_POST['date_from']);
        }
        if (!empty($_POST['date_to'])) {
            $args['date_query'][0]['before'] = sanitize_text_field($_POST['date_to']);
        }
        $comments = get_comments($args);
        foreach ($comments as $comment) {
            wp_delete_comment($comment->comment_ID, true);
            $deleted_items++;
            amcd_log_deletion('comment', $comment->comment_ID);
        }
    }

    // Process users
    if (isset($_POST['delete_users'])) {
        $args = array(
            'number' => $batch_size,
            'role__not_in' => array('administrator'),
        );
        $users = get_users($args);
        foreach ($users as $user) {
            wp_delete_user($user->ID);
            $deleted_items++;
            amcd_log_deletion('user', $user->ID);
        }
    }

    // Process terms (categories, tags)
    if (isset($_POST['delete_terms'])) {
        $taxonomies = get_taxonomies();
        foreach ($taxonomies as $taxonomy) {
            $args = array(
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
                'number' => $batch_size,
            );
            $terms = get_terms($args);
            foreach ($terms as $term) {
                wp_delete_term($term->term_id, $taxonomy);
                $deleted_items++;
                amcd_log_deletion('term', $term->term_id);
            }
        }
    }

    // Process media
    if (isset($_POST['delete_media'])) {
        $args = array(
            'post_type' => 'attachment',
            'posts_per_page' => $batch_size,
            'post_status' => 'any',
        );
        if (!empty($_POST['date_from'])) {
            $args['date_query'][0]['after'] = sanitize_text_field($_POST['date_from']);
        }
        if (!empty($_POST['date_to'])) {
            $args['date_query'][0]['before'] = sanitize_text_field($_POST['date_to']);
        }
        $attachments = get_posts($args);
        foreach ($attachments as $attachment) {
            wp_delete_attachment($attachment->ID, true);
            $deleted_items++;
            amcd_log_deletion('attachment', $attachment->ID);
        }
    }

    // Process WooCommerce content
    if (class_exists('WooCommerce')) {
        // Process products
        if (isset($_POST['delete_products'])) {
            $args = array(
                'status' => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash'),
                'limit' => $batch_size,
            );
            if (!empty($_POST['date_from'])) {
                $args['date_created'] = '>' . sanitize_text_field($_POST['date_from']);
            }
            if (!empty($_POST['date_to'])) {
                $args['date_created'] = '<' . sanitize_text_field($_POST['date_to']);
            }
            $products = wc_get_products($args);
            foreach ($products as $product) {
                $product->delete(true);
                $deleted_items++;
                amcd_log_deletion('product', $product->get_id());
            }
        }

        // Process orders
        if (isset($_POST['delete_orders'])) {
            $args = array(
                'limit' => $batch_size,
                'status' => array_keys(wc_get_order_statuses()),
            );
            if (!empty($_POST['date_from'])) {
                $args['date_created'] = '>' . sanitize_text_field($_POST['date_from']);
            }
            if (!empty($_POST['date_to'])) {
                $args['date_created'] = '<' . sanitize_text_field($_POST['date_to']);
            }
            $orders = wc_get_orders($args);
            foreach ($orders as $order) {
                $order->delete(true);
                $deleted_items++;
                amcd_log_deletion('order', $order->get_id());
            }
        }

        // Process coupons
        if (isset($_POST['delete_coupons'])) {
            $args = array(
                'posts_per_page' => $batch_size,
                'post_type' => 'shop_coupon',
                'post_status' => 'any',
            );
            if (!empty($_POST['date_from'])) {
                $args['date_query'][0]['after'] = sanitize_text_field($_POST['date_from']);
            }
            if (!empty($_POST['date_to'])) {
                $args['date_query'][0]['before'] = sanitize_text_field($_POST['date_to']);
            }
            $coupons = get_posts($args);
            foreach ($coupons as $coupon) {
                wp_delete_post($coupon->ID, true);
                $deleted_items++;
                amcd_log_deletion('coupon', $coupon->ID);
            }
        }
    }

    $progress = ($total_items > 0) ? round(($deleted_items / $total_items) * 100) : 100;
    $message = "Deleted $deleted_items out of $total_items items";

    wp_send_json(array('progress' => $progress, 'message' => $message));
}
add_action('wp_ajax_amcd_process_deletion', 'amcd_ajax_process_deletion');

// Function to get total items to be deleted
function amcd_get_total_items()
{
    $total = 0;

    // Count posts (including custom post types)
    $post_types = get_post_types(array('public' => true));
    foreach ($post_types as $post_type) {
        if (isset($_POST["delete_{$post_type}"]) || isset($_POST["delete_cpt_{$post_type}"])) {
            $total += wp_count_posts($post_type)->publish;
        }
    }

    // Count comments
    if (isset($_POST['delete_comments'])) {
        $total += wp_count_comments()->total_comments;
    }

    // Count users
    if (isset($_POST['delete_users'])) {
        $total += count_users()['total_users'] - 1; // Subtract 1 for admin
    }

    // Count terms
    if (isset($_POST['delete_terms'])) {
        $taxonomies = get_taxonomies();
        foreach ($taxonomies as $taxonomy) {
            $total += wp_count_terms($taxonomy);
        }
    }

    // Count media
    if (isset($_POST['delete_media'])) {
        $total += wp_count_posts('attachment')->inherit;
    }

    // Count WooCommerce items
    if (class_exists('WooCommerce')) {
        if (isset($_POST['delete_products'])) {
            $total += wp_count_posts('product')->publish;
        }
        if (isset($_POST['delete_orders'])) {
            $total += wp_count_posts('shop_order')->publish;
        }
        if (isset($_POST['delete_coupons'])) {
            $total += wp_count_posts('shop_coupon')->publish;
        }
    }

    return $total;
}

// Function to log deletions
function amcd_log_deletion($type, $id)
{
    $current_user = wp_get_current_user();
    $log_entry = array(
        'type' => $type,
        'id' => $id,
        'user' => $current_user->user_login,
        'time' => current_time('mysql')
    );
    $logs = get_option('amcd_deletion_logs', array());
    $logs[] = $log_entry;
    update_option('amcd_deletion_logs', $logs);
}

// AJAX handler for database optimization
function amcd_ajax_optimize_database()
{
    global $wpdb;
    $tables = $wpdb->get_results("SHOW TABLES");
    foreach ($tables as $table) {
        $table_name = array_values(get_object_vars($table))[0];
        $wpdb->query("OPTIMIZE TABLE $table_name");
    }
    wp_send_json_success();
}
add_action('wp_ajax_amcd_optimize_database', 'amcd_ajax_optimize_database');

// AJAX handler for data export
function amcd_ajax_export_data()
{
    check_ajax_referer('amcd_delete_action', 'amcd_nonce');

    $export_data = array();
    $filename = 'content_export_' . date('Y-m-d_H-i-s') . '.csv';
    $upload_dir = wp_upload_dir();
    $file_path = $upload_dir['path'] . '/' . $filename;

    // Helper function to add data to export array
    function add_to_export(&$export_data, $type, $items)
    {
        foreach ($items as $item) {
            $export_data[] = array(
                'Type' => $type,
                'ID' => $item->ID,
                'Title' => $item->post_title,
                'Date' => $item->post_date
            );
        }
    }

    // Export posts
    if (isset($_POST['delete_posts'])) {
        $posts = get_posts(array('posts_per_page' => -1));
        add_to_export($export_data, 'Post', $posts);
    }

    // Export pages
    if (isset($_POST['delete_pages'])) {
        $pages = get_pages();
        add_to_export($export_data, 'Page', $pages);
    }

    // Export comments
    if (isset($_POST['delete_comments'])) {
        $comments = get_comments(array('status' => 'all'));
        foreach ($comments as $comment) {
            $export_data[] = array(
                'Type' => 'Comment',
                'ID' => $comment->comment_ID,
                'Content' => wp_trim_words($comment->comment_content, 10),
                'Date' => $comment->comment_date
            );
        }
    }

    // Export users
    if (isset($_POST['delete_users'])) {
        $users = get_users(array('role__not_in' => array('administrator')));
        foreach ($users as $user) {
            $export_data[] = array(
                'Type' => 'User',
                'ID' => $user->ID,
                'Username' => $user->user_login,
                'Email' => $user->user_email
            );
        }
    }

    // Export terms
    if (isset($_POST['delete_terms'])) {
        $taxonomies = get_taxonomies();
        foreach ($taxonomies as $taxonomy) {
            $terms = get_terms(array('taxonomy' => $taxonomy, 'hide_empty' => false));
            foreach ($terms as $term) {
                $export_data[] = array(
                    'Type' => 'Term',
                    'ID' => $term->term_id,
                    'Name' => $term->name,
                    'Taxonomy' => $taxonomy
                );
            }
        }
    }

    // Export WooCommerce data if WooCommerce is active
    if (class_exists('WooCommerce')) {
        // Export products
        if (isset($_POST['delete_products'])) {
            $products = wc_get_products(array('limit' => -1));
            foreach ($products as $product) {
                $product_data = array(
                    'Type' => $product->get_type(),
                    'ID' => $product->get_id(),
                    'Name' => $product->get_name(),
                    'SKU' => $product->get_sku(),
                    'Price' => $product->get_price(),
                    'Regular Price' => $product->get_regular_price(),
                    'Sale Price' => $product->get_sale_price(),
                    'Stock Status' => $product->get_stock_status(),
                    'Stock Quantity' => $product->get_stock_quantity(),
                    'Weight' => $product->get_weight(),
                    'Length' => $product->get_length(),
                    'Width' => $product->get_width(),
                    'Height' => $product->get_height(),
                    'Categories' => strip_tags(wc_get_product_category_list($product->get_id())),
                    'Tags' => strip_tags(wc_get_product_tag_list($product->get_id())),
                    'Date Created' => $product->get_date_created()->date('Y-m-d H:i:s'),
                    'Date Modified' => $product->get_date_modified()->date('Y-m-d H:i:s'),
                );

                // Handle variable products
                if ($product->is_type('variable')) {
                    $variations = $product->get_available_variations();
                    $variation_data = '';
                    foreach ($variations as $variation) {
                        $variation_product = wc_get_product($variation['variation_id']);
                        $variation_data .= sprintf(
                            "Variation ID: %s, SKU: %s, Price: %s, Stock: %s | ",
                            $variation['variation_id'],
                            $variation_product->get_sku(),
                            $variation_product->get_price(),
                            $variation_product->get_stock_quantity()
                        );
                    }
                    $product_data['Variations'] = rtrim($variation_data, ' | ');
                }

                $export_data[] = $product_data;
            }
        }

        // Export orders
        if (isset($_POST['delete_orders'])) {
            $orders = wc_get_orders(array('limit' => -1));
            foreach ($orders as $order) {
                $export_data[] = array(
                    'Type' => 'Order',
                    'ID' => $order->get_id(),
                    'Status' => $order->get_status(),
                    'Total' => $order->get_total()
                );
            }
        }

        // Export coupons
        if (isset($_POST['delete_coupons'])) {
            $coupons = get_posts(array('post_type' => 'shop_coupon', 'posts_per_page' => -1));
            add_to_export($export_data, 'Coupon', $coupons);
        }
    }

    $csv_header = array(
        'Type', 'ID', 'Name', 'SKU', 'Price', 'Regular Price', 'Sale Price',
        'Stock Status', 'Stock Quantity', 'Weight', 'Length', 'Width', 'Height',
        'Categories', 'Tags', 'Date Created', 'Date Modified', 'Variations'
    );

    // Create CSV file
    $fp = fopen($file_path, 'w');
    fputcsv($fp, $csv_header);
    foreach ($export_data as $row) {
        // Ensure all columns are present, even if empty
        $csv_row = array();
        foreach ($csv_header as $header) {
            $csv_row[] = isset($row[$header]) ? $row[$header] : '';
        }
        fputcsv($fp, $csv_row);
    }
    fclose($fp);
    // Generate download URL
    $file_url = $upload_dir['url'] . '/' . $filename;

    // Schedule file deletion after 1 hour
    wp_schedule_single_event(time() + 3600, 'amcd_delete_export_file', array($file_path));

    wp_send_json_success(array('download_url' => $file_url));
}
add_action('wp_ajax_amcd_export_data', 'amcd_ajax_export_data');

// Function to delete the export file
function amcd_delete_export_file($file_path)
{
    if (file_exists($file_path)) {
        unlink($file_path);
    }
}
add_action('amcd_delete_export_file', 'amcd_delete_export_file');

// Add a scheduled deletion feature
function amcd_schedule_deletion($timestamp, $data)
{
    wp_schedule_single_event($timestamp, 'amcd_scheduled_deletion', array($data));
}

function amcd_process_scheduled_deletion($data)
{
    // Process deletion based on $data
    // This function will be called by WordPress cron
}
add_action('amcd_scheduled_deletion', 'amcd_process_scheduled_deletion');

// Add role-based access control
function amcd_user_can_access()
{
    return current_user_can('manage_options'); // Adjust as needed
}
