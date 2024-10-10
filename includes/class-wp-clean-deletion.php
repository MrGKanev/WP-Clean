<?php
class WP_Clean_Deletion
{
  public function __construct()
  {
    add_action('wp_ajax_wp_clean_process_deletion', array($this, 'process_deletion'));
  }

  public function process_deletion()
  {
    check_ajax_referer('wp_clean_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error('Insufficient permissions', 400);
      return;
    }

    $deletion_data = $this->get_deletion_data();
    $deleted_items = 0;
    $total_items = $this->get_total_items($deletion_data);

    if ($deletion_data['posts']) {
      $deleted_items += $this->delete_posts();
    }

    if ($deletion_data['pages']) {
      $deleted_items += $this->delete_pages();
    }

    if ($deletion_data['comments']) {
      $deleted_items += $this->delete_comments();
    }

    if ($deletion_data['users']) {
      $deleted_items += $this->delete_users();
    }

    if ($deletion_data['terms']) {
      $deleted_items += $this->delete_terms();
    }

    if ($deletion_data['media']) {
      $deleted_items += $this->delete_media();
    }

    if ($deletion_data['wc_products']) {
      $deleted_items += $this->delete_wc_products();
    }

    if ($deletion_data['wc_orders']) {
      $deleted_items += $this->delete_wc_orders();
    }

    if ($deletion_data['wc_coupons']) {
      $deleted_items += $this->delete_wc_coupons();
    }

    $progress = ($total_items > 0) ? round(($deleted_items / $total_items) * 100) : 100;
    $message = sprintf(__('Deleted %1$d out of %2$d items', 'wp-clean'), $deleted_items, $total_items);

    wp_send_json_success(array('progress' => $progress, 'message' => $message));
  }

  private function get_deletion_data()
  {
    return array(
      'posts' => isset($_POST['delete_posts']) && $_POST['delete_posts'] === '1',
      'pages' => isset($_POST['delete_pages']) && $_POST['delete_pages'] === '1',
      'comments' => isset($_POST['delete_comments']) && $_POST['delete_comments'] === '1',
      'users' => isset($_POST['delete_users']) && $_POST['delete_users'] === '1',
      'terms' => isset($_POST['delete_terms']) && $_POST['delete_terms'] === '1',
      'media' => isset($_POST['delete_media']) && $_POST['delete_media'] === '1',
      'wc_products' => isset($_POST['delete_wc_products']) && $_POST['delete_wc_products'] === '1',
      'wc_orders' => isset($_POST['delete_wc_orders']) && $_POST['delete_wc_orders'] === '1',
      'wc_coupons' => isset($_POST['delete_wc_coupons']) && $_POST['delete_wc_coupons'] === '1',
    );
  }

  private function get_total_items($data)
  {
    $total = 0;

    if ($data['posts']) {
      $total += wp_count_posts()->publish;
    }

    if ($data['pages']) {
      $total += wp_count_posts('page')->publish;
    }

    if ($data['comments']) {
      $total += wp_count_comments()->total_comments;
    }

    if ($data['users']) {
      $total += count_users()['total_users'] - 1; // Subtract 1 for admin
    }

    if ($data['terms']) {
      $taxonomies = get_taxonomies();
      foreach ($taxonomies as $taxonomy) {
        $total += wp_count_terms($taxonomy);
      }
    }

    if ($data['media']) {
      $total += wp_count_attachments()->inherit;
    }

    if ($data['wc_products']) {
      $total += wp_count_posts('product')->publish;
    }

    if ($data['wc_orders']) {
      $total += wp_count_posts('shop_order')->publish;
    }

    if ($data['wc_coupons']) {
      $total += wp_count_posts('shop_coupon')->publish;
    }

    return $total;
  }

  // Existing methods (delete_posts, delete_pages, delete_comments, delete_users, delete_terms, delete_media) remain unchanged

  private function delete_wc_products()
  {
    $products = get_posts(array(
      'post_type' => 'product',
      'post_status' => 'any',
      'posts_per_page' => -1,
      'fields' => 'ids',
    ));

    $deleted = 0;
    foreach ($products as $product_id) {
      if (wp_delete_post($product_id, true)) {
        $deleted++;
      }
    }

    return $deleted;
  }

  private function delete_wc_orders()
  {
    $orders = get_posts(array(
      'post_type' => 'shop_order',
      'post_status' => 'any',
      'posts_per_page' => -1,
      'fields' => 'ids',
    ));

    $deleted = 0;
    foreach ($orders as $order_id) {
      if (wp_delete_post($order_id, true)) {
        $deleted++;
      }
    }

    return $deleted;
  }

  private function delete_wc_coupons()
  {
    $coupons = get_posts(array(
      'post_type' => 'shop_coupon',
      'post_status' => 'any',
      'posts_per_page' => -1,
      'fields' => 'ids',
    ));

    $deleted = 0;
    foreach ($coupons as $coupon_id) {
      if (wp_delete_post($coupon_id, true)) {
        $deleted++;
      }
    }

    return $deleted;
  }
}
