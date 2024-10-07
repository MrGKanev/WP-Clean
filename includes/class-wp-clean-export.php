<?php
class WP_Clean_Export
{
  private $filename;
  private $file_path;
  private $file_url;

  public function __construct()
  {
    add_action('wp_ajax_wp_clean_export_data', array($this, 'export_data'));
    add_action('wp_clean_delete_export_file', array($this, 'delete_export_file'));
  }

  public function export_data()
  {
    check_ajax_referer('wp_clean_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error(__('You do not have sufficient permissions to perform this action.', 'wp-clean'));
    }

    $this->setup_file_details();
    $export_data = $this->collect_export_data();

    if ($this->create_csv_file($export_data)) {
      $this->schedule_file_deletion();
      wp_send_json_success(array('download_url' => $this->file_url));
    } else {
      wp_send_json_error(__('Failed to create export file.', 'wp-clean'));
    }
  }

  private function setup_file_details()
  {
    $this->filename = 'wp_clean_export_' . date('Y-m-d_H-i-s') . '.csv';
    $upload_dir = wp_upload_dir();
    $this->file_path = $upload_dir['path'] . '/' . $this->filename;
    $this->file_url = $upload_dir['url'] . '/' . $this->filename;
  }

  private function collect_export_data()
  {
    $export_data = array();

    if (isset($_POST['delete_posts'])) {
      $export_data = array_merge($export_data, $this->get_posts_data());
    }
    if (isset($_POST['delete_pages'])) {
      $export_data = array_merge($export_data, $this->get_pages_data());
    }
    if (isset($_POST['delete_comments'])) {
      $export_data = array_merge($export_data, $this->get_comments_data());
    }
    if (isset($_POST['delete_users'])) {
      $export_data = array_merge($export_data, $this->get_users_data());
    }
    if (isset($_POST['delete_terms'])) {
      $export_data = array_merge($export_data, $this->get_terms_data());
    }
    if (isset($_POST['delete_media'])) {
      $export_data = array_merge($export_data, $this->get_media_data());
    }

    return $export_data;
  }

  private function create_csv_file($export_data)
  {
    $fp = fopen($this->file_path, 'w');
    if ($fp === false) {
      return false;
    }

    fputcsv($fp, array('Type', 'ID', 'Title', 'Content', 'Author', 'Date', 'Additional Info'));
    foreach ($export_data as $row) {
      fputcsv($fp, $row);
    }
    fclose($fp);

    return true;
  }

  private function schedule_file_deletion()
  {
    wp_schedule_single_event(time() + 3600, 'wp_clean_delete_export_file', array($this->file_path));
  }

  public function delete_export_file($file_path)
  {
    if (file_exists($file_path)) {
      unlink($file_path);
    }
  }

  private function get_posts_data()
  {
    $posts = get_posts(array(
      'post_type' => 'post',
      'posts_per_page' => -1,
    ));

    return $this->format_post_data($posts, 'Post');
  }

  private function get_pages_data()
  {
    $pages = get_posts(array(
      'post_type' => 'page',
      'posts_per_page' => -1,
    ));

    return $this->format_post_data($pages, 'Page');
  }

  private function format_post_data($posts, $type)
  {
    $data = array();
    foreach ($posts as $post) {
      $data[] = array(
        $type,
        $post->ID,
        $post->post_title,
        wp_strip_all_tags($post->post_content),
        get_the_author_meta('display_name', $post->post_author),
        $post->post_date,
        'Status: ' . $post->post_status . ', Comment Count: ' . $post->comment_count
      );
    }
    return $data;
  }

  private function get_comments_data()
  {
    $comments = get_comments(array(
      'status' => 'all',
    ));

    $data = array();
    foreach ($comments as $comment) {
      $data[] = array(
        'Comment',
        $comment->comment_ID,
        wp_strip_all_tags($comment->comment_content),
        '',
        $comment->comment_author,
        $comment->comment_date,
        'Status: ' . $comment->comment_approved . ', Post ID: ' . $comment->comment_post_ID
      );
    }
    return $data;
  }

  private function get_users_data()
  {
    $users = get_users(array(
      'role__not_in' => array('administrator'),
    ));

    $data = array();
    foreach ($users as $user) {
      $data[] = array(
        'User',
        $user->ID,
        $user->display_name,
        '',
        '',
        $user->user_registered,
        'Email: ' . $user->user_email . ', Role: ' . implode(', ', $user->roles)
      );
    }
    return $data;
  }

  private function get_terms_data()
  {
    $taxonomies = get_taxonomies();
    $data = array();

    foreach ($taxonomies as $taxonomy) {
      $terms = get_terms(array(
        'taxonomy' => $taxonomy,
        'hide_empty' => false,
      ));

      if (!is_wp_error($terms)) {
        foreach ($terms as $term) {
          $data[] = array(
            'Term',
            $term->term_id,
            $term->name,
            $term->description,
            '',
            '',
            'Taxonomy: ' . $taxonomy . ', Count: ' . $term->count . ', Slug: ' . $term->slug
          );
        }
      }
    }

    return $data;
  }

  private function get_media_data()
  {
    $attachments = get_posts(array(
      'post_type' => 'attachment',
      'posts_per_page' => -1,
    ));

    $data = array();
    foreach ($attachments as $attachment) {
      $file_url = wp_get_attachment_url($attachment->ID);
      $file_path = get_attached_file($attachment->ID);
      $file_size = file_exists($file_path) ? size_format(filesize($file_path), 2) : 'N/A';

      $data[] = array(
        'Media',
        $attachment->ID,
        $attachment->post_title,
        wp_strip_all_tags($attachment->post_content),
        get_the_author_meta('display_name', $attachment->post_author),
        $attachment->post_date,
        'File URL: ' . $file_url . ', File Size: ' . $file_size . ', MIME Type: ' . $attachment->post_mime_type
      );
    }
    return $data;
  }
}
