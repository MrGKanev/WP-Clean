<div class="wrap">
  <h1><?php echo esc_html__('WP-Clean: WordPress Content Cleanup and Database Optimization', 'wp-clean'); ?></h1>

  <form method="post" action="" id="wp-clean-form">
    <?php wp_nonce_field('wp_clean_nonce', 'wp_clean_nonce'); ?>

    <div class="wp-clean-form-section">
      <h3><?php echo esc_html__('Select Content to Delete', 'wp-clean'); ?></h3>
      <div class="wp-clean-checkbox-list">
        <label><input type="checkbox" name="delete_posts" value="1"> <?php echo esc_html__('Posts', 'wp-clean'); ?></label>
        <label><input type="checkbox" name="delete_pages" value="1"> <?php echo esc_html__('Pages', 'wp-clean'); ?></label>
        <label><input type="checkbox" name="delete_comments" value="1"> <?php echo esc_html__('Comments', 'wp-clean'); ?></label>
        <label><input type="checkbox" name="delete_users" value="1"> <?php echo esc_html__('Users (except admin)', 'wp-clean'); ?></label>
        <label><input type="checkbox" name="delete_terms" value="1"> <?php echo esc_html__('Terms (categories, tags)', 'wp-clean'); ?></label>
        <label><input type="checkbox" name="delete_media" value="1"> <?php echo esc_html__('Media', 'wp-clean'); ?></label>
      </div>
    </div>

    <div class="wp-clean-form-section">
      <h3><?php echo esc_html__('Date Range (optional)', 'wp-clean'); ?></h3>
      <div class="wp-clean-date-range">
        <label><?php echo esc_html__('From:', 'wp-clean'); ?> <input type="date" name="date_from"></label>
        <label><?php echo esc_html__('To:', 'wp-clean'); ?> <input type="date" name="date_to"></label>
      </div>
    </div>

    <div class="wp-clean-form-section">
      <input type="submit" name="wp_clean_submit" id="wp-clean-submit" class="button button-primary" value="<?php echo esc_attr__('Delete Selected Content', 'wp-clean'); ?>">
      <input type="submit" name="wp_clean_export" id="wp-clean-export" class="button" value="<?php echo esc_attr__('Export Before Delete', 'wp-clean'); ?>">
    </div>
  </form>

  <div id="wp-clean-progress" style="display: none;">
    <h3><?php echo esc_html__('Deletion Progress', 'wp-clean'); ?></h3>
    <div class="wp-clean-progress-bar">
      <div id="wp-clean-progress-bar-inner" class="wp-clean-progress-bar-inner"></div>
    </div>
    <p id="wp-clean-progress-text"></p>
  </div>
</div>