<?php
class WP_Clean_Deletion
{
  private $batch_size = 50; // Number of items to process per batch

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
    $total_items = $this->get_total_items($deletion_data);
    $deleted_items = 0;
    $current_offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;

    foreach ($deletion_data as $type => $should_delete) {
      if ($should_delete) {
        $method_name = 'delete_' . $type;
        if (method_exists($this, $method_name)) {
          $result = $this->$method_name($current_offset, $this->batch_size);
          $deleted_items += $result['deleted'];
          $current_offset = $result['new_offset'];
        }
      }
    }

    $progress = ($total_items > 0) ? round(($current_offset / $total_items) * 100) : 100;
    $message = sprintf(__('Processed %1$d out of %2$d items', 'wp-clean'), $current_offset, $total_items);

    $is_complete = $current_offset >= $total_items;

    wp_send_json_success(array(
      'progress' => $progress,
      'message' => $message,
      'offset' => $current_offset,
      'is_complete' => $is_complete
    ));
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

  private function delete_posts($offset, $batch_size)
  {
    $posts = get_posts(array(
      'post_type' => 'post',
      'post_status' => 'any',
      'posts_per_page' => $batch_size,
      'offset' => $offset,
      'fields' => 'ids',
    ));

    $deleted = 0;
    foreach ($posts as $post_id) {
      if (wp_delete_post($post_id, true)) {
        $deleted++;
      }
    }

    return array('deleted' => $deleted, 'new_offset' => $offset + $batch_size);
  }

  private function delete_pages($offset, $batch_size)
  {
    $pages = get_posts(array(
      'post_type' => 'page',
      'post_status' => 'any',
      'posts_per_page' => $batch_size,
      'offset' => $offset,
      'fields' => 'ids',
    ));

    $deleted = 0;
    foreach ($pages as $page_id) {
      if (wp_delete_post($page_id, true)) {
        $deleted++;
      }
    }

    return array('deleted' => $deleted, 'new_offset' => $offset + $batch_size);
  }

  private function delete_comments($offset, $batch_size)
  {
    $comments = get_comments(array(
      'status' => 'any',
      'number' => $batch_size,
      'offset' => $offset,
      'fields' => 'ids',
    ));

    $deleted = 0;
    foreach ($comments as $comment_id) {
      if (wp_delete_comment($comment_id, true)) {
        $deleted++;
      }
    }

    return array('deleted' => $deleted, 'new_offset' => $offset + $batch_size);
  }

  private function delete_users($offset, $batch_size)
  {
    $users = get_users(array(
      'role__not_in' => array('administrator'),
      'number' => $batch_size,
      'offset' => $offset,
      'fields' => array('ID'),
    ));

    $deleted = 0;
    foreach ($users as $user) {
      if (wp_delete_user($user->ID)) {
        $deleted++;
      }
    }

    return array('deleted' => $deleted, 'new_offset' => $offset + $batch_size);
  }

  private function delete_terms($offset, $batch_size)
  {
    $taxonomies = get_taxonomies();
    $deleted = 0;
    $processed = 0;

    foreach ($taxonomies as $taxonomy) {
      $terms = get_terms(array(
        'taxonomy' => $taxonomy,
        'hide_empty' => false,
        'number' => $batch_size - $processed,
        'offset' => $offset,
        'fields' => 'ids',
      ));

      if (!is_wp_error($terms)) {
        foreach ($terms as $term_id) {
          if (wp_delete_term($term_id, $taxonomy)) {
            $deleted++;
          }
          $processed++;
          if ($processed >= $batch_size) {
            break 2;
          }
        }
      }

      if ($processed >= $batch_size) {
        break;
      }
      $offset = 0; // Reset offset for the next taxonomy
    }

    return array('deleted' => $deleted, 'new_offset' => $offset + $batch_size);
  }

  private function delete_media($offset, $batch_size)
  {
    $attachments = get_posts(array(
      'post_type' => 'attachment',
      'post_status' => 'any',
      'posts_per_page' => $batch_size,
      'offset' => $offset,
      'fields' => 'ids',
    ));

    $deleted = 0;
    foreach ($attachments as $attachment_id) {
      if (wp_delete_attachment($attachment_id, true)) {
        $deleted++;
      }
    }

    return array('deleted' => $deleted, 'new_offset' => $offset + $batch_size);
  }

  private function delete_wc_products($offset, $batch_size)
  {
    $products = get_posts(array(
      'post_type' => 'product',
      'post_status' => 'any',
      'posts_per_page' => $batch_size,
      'offset' => $offset,
      'fields' => 'ids',
    ));

    $deleted = 0;
    foreach ($products as $product_id) {
      if (wp_delete_post($product_id, true)) {
        $deleted++;
      }
    }

    return array('deleted' => $deleted, 'new_offset' => $offset + $batch_size);
  }

  private function delete_wc_orders($offset, $batch_size)
  {
    $orders = get_posts(array(
      'post_type' => 'shop_order',
      'post_status' => 'any',
      'posts_per_page' => $batch_size,
      'offset' => $offset,
      'fields' => 'ids',
    ));

    $deleted = 0;
    foreach ($orders as $order_id) {
      if (wp_delete_post($order_id, true)) {
        $deleted++;
      }
    }

    return array('deleted' => $deleted, 'new_offset' => $offset + $batch_size);
  }

  private function delete_wc_coupons($offset, $batch_size)
  {
    $coupons = get_posts(array(
      'post_type' => 'shop_coupon',
      'post_status' => 'any',
      'posts_per_page' => $batch_size,
      'offset' => $offset,
      'fields' => 'ids',
    ));

    $deleted = 0;
    foreach ($coupons as $coupon_id) {
      if (wp_delete_post($coupon_id, true)) {
        $deleted++;
      }
    }

    return array('deleted' => $deleted, 'new_offset' => $offset + $batch_size);
  }
}
