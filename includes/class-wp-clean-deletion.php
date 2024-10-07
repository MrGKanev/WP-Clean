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
      wp_send_json_error(__('You do not have sufficient permissions to perform this action.', 'wp-clean'));
    }

    error_log('WP-Clean: Starting deletion process');
    error_log('WP-Clean: POST data: ' . print_r($_POST, true));

    $deletion_data = $this->get_deletion_data();
    error_log('WP-Clean: Deletion data: ' . print_r($deletion_data, true));

    $deleted_items = 0;
    $total_items = $this->get_total_items($deletion_data);
    error_log('WP-Clean: Total items to delete: ' . $total_items);

    // Process posts
    if ($deletion_data['posts']) {
      $deleted_posts = $this->delete_posts($deletion_data);
      error_log('WP-Clean: Deleted posts: ' . $deleted_posts);
      $deleted_items += $deleted_posts;
    }

    // Process pages
    if ($deletion_data['pages']) {
      $deleted_pages = $this->delete_pages($deletion_data);
      error_log('WP-Clean: Deleted pages: ' . $deleted_pages);
      $deleted_items += $deleted_pages;
    }

    // Process comments
    if ($deletion_data['comments']) {
      $deleted_comments = $this->delete_comments($deletion_data);
      error_log('WP-Clean: Deleted comments: ' . $deleted_comments);
      $deleted_items += $deleted_comments;
    }

    // Process users
    if ($deletion_data['users']) {
      $deleted_users = $this->delete_users();
      error_log('WP-Clean: Deleted users: ' . $deleted_users);
      $deleted_items += $deleted_users;
    }

    // Process terms
    if ($deletion_data['terms']) {
      $deleted_terms = $this->delete_terms();
      error_log('WP-Clean: Deleted terms: ' . $deleted_terms);
      $deleted_items += $deleted_terms;
    }

    // Process media
    if ($deletion_data['media']) {
      $deleted_media = $this->delete_media($deletion_data);
      error_log('WP-Clean: Deleted media: ' . $deleted_media);
      $deleted_items += $deleted_media;
    }

    $progress = ($total_items > 0) ? round(($deleted_items / $total_items) * 100) : 100;
    $message = sprintf(__('Deleted %1$d out of %2$d items', 'wp-clean'), $deleted_items, $total_items);

    error_log('WP-Clean: Deletion process completed. Progress: ' . $progress . '%, Message: ' . $message);

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
      'date_from' => sanitize_text_field($_POST['date_from'] ?? ''),
      'date_to' => sanitize_text_field($_POST['date_to'] ?? ''),
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

    return $total;
  }

  private function delete_posts($data)
  {
    $args = array(
      'post_type' => 'post',
      'post_status' => 'any',
      'posts_per_page' => -1,
      'fields' => 'ids',
    );

    if (!empty($data['date_from']) || !empty($data['date_to'])) {
      $args['date_query'] = array();
      if (!empty($data['date_from'])) {
        $args['date_query']['after'] = $data['date_from'];
      }
      if (!empty($data['date_to'])) {
        $args['date_query']['before'] = $data['date_to'];
      }
    }

    $posts = get_posts($args);
    $deleted = 0;

    foreach ($posts as $post_id) {
      if (wp_delete_post($post_id, true)) {
        $deleted++;
      }
    }

    return $deleted;
  }

  private function delete_pages($data)
  {
    $args = array(
      'post_type' => 'page',
      'post_status' => 'any',
      'posts_per_page' => -1,
      'fields' => 'ids',
    );

    if (!empty($data['date_from']) || !empty($data['date_to'])) {
      $args['date_query'] = array();
      if (!empty($data['date_from'])) {
        $args['date_query']['after'] = $data['date_from'];
      }
      if (!empty($data['date_to'])) {
        $args['date_query']['before'] = $data['date_to'];
      }
    }

    $pages = get_posts($args);
    $deleted = 0;

    foreach ($pages as $page_id) {
      if (wp_delete_post($page_id, true)) {
        $deleted++;
      }
    }

    return $deleted;
  }

  private function delete_comments($data)
  {
    $args = array(
      'status' => 'any',
      'fields' => 'ids',
    );

    if (!empty($data['date_from']) || !empty($data['date_to'])) {
      $args['date_query'] = array();
      if (!empty($data['date_from'])) {
        $args['date_query']['after'] = $data['date_from'];
      }
      if (!empty($data['date_to'])) {
        $args['date_query']['before'] = $data['date_to'];
      }
    }

    $comments = get_comments($args);
    $deleted = 0;

    foreach ($comments as $comment_id) {
      if (wp_delete_comment($comment_id, true)) {
        $deleted++;
      }
    }

    return $deleted;
  }

  private function delete_users()
  {
    $args = array(
      'role__not_in' => array('administrator'),
      'fields' => array('ID'),
    );

    $users = get_users($args);
    $deleted = 0;

    foreach ($users as $user) {
      if (wp_delete_user($user->ID)) {
        $deleted++;
      }
    }

    return $deleted;
  }

  private function delete_terms()
  {
    $taxonomies = get_taxonomies();
    $deleted = 0;

    foreach ($taxonomies as $taxonomy) {
      $terms = get_terms(array(
        'taxonomy' => $taxonomy,
        'hide_empty' => false,
        'fields' => 'ids',
      ));

      if (!is_wp_error($terms)) {
        foreach ($terms as $term_id) {
          if (wp_delete_term($term_id, $taxonomy)) {
            $deleted++;
          }
        }
      }
    }

    return $deleted;
  }

  private function delete_media($data)
  {
    $args = array(
      'post_type' => 'attachment',
      'post_status' => 'any',
      'posts_per_page' => -1,
      'fields' => 'ids',
    );

    if (!empty($data['date_from']) || !empty($data['date_to'])) {
      $args['date_query'] = array();
      if (!empty($data['date_from'])) {
        $args['date_query']['after'] = $data['date_from'];
      }
      if (!empty($data['date_to'])) {
        $args['date_query']['before'] = $data['date_to'];
      }
    }

    $attachments = get_posts($args);
    $deleted = 0;

    foreach ($attachments as $attachment_id) {
      if (wp_delete_attachment($attachment_id, true)) {
        $deleted++;
      }
    }

    return $deleted;
  }
}
