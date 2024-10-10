<div class="wrap wp-clean-admin">
  <h1><?php echo esc_html__('WP-Clean: WordPress and WooCommerce Content Cleanup', 'wp-clean'); ?></h1>

  <div class="notice notice-warning">
    <p><?php echo esc_html__('Warning: Deletions performed by this plugin are permanent and cannot be undone. Please backup your database before proceeding.', 'wp-clean'); ?></p>
  </div>

  <form method="post" action="" id="wp-clean-form">
    <?php wp_nonce_field('wp_clean_nonce', 'wp_clean_nonce'); ?>

    <div class="wp-clean-section">
      <h2><?php echo esc_html__('WordPress Content', 'wp-clean'); ?></h2>
      <div class="wp-clean-options">
        <label>
          <input type="checkbox" name="delete_posts" value="1">
          <?php echo esc_html__('Posts', 'wp-clean'); ?>
          <span class="tooltip" title="<?php echo esc_attr__('Delete all blog posts', 'wp-clean'); ?>">?</span>
        </label>
        <label>
          <input type="checkbox" name="delete_pages" value="1">
          <?php echo esc_html__('Pages', 'wp-clean'); ?>
          <span class="tooltip" title="<?php echo esc_attr__('Delete all pages', 'wp-clean'); ?>">?</span>
        </label>
        <label>
          <input type="checkbox" name="delete_comments" value="1">
          <?php echo esc_html__('Comments', 'wp-clean'); ?>
          <span class="tooltip" title="<?php echo esc_attr__('Delete all comments', 'wp-clean'); ?>">?</span>
        </label>
        <label>
          <input type="checkbox" name="delete_media" value="1">
          <?php echo esc_html__('Media', 'wp-clean'); ?>
          <span class="tooltip" title="<?php echo esc_attr__('Delete all uploaded media files', 'wp-clean'); ?>">?</span>
        </label>
      </div>
    </div>

    <div class="wp-clean-section">
      <h2><?php echo esc_html__('Users and Taxonomy', 'wp-clean'); ?></h2>
      <div class="wp-clean-options">
        <label>
          <input type="checkbox" name="delete_users" value="1">
          <?php echo esc_html__('Users (except admin)', 'wp-clean'); ?>
          <span class="tooltip" title="<?php echo esc_attr__('Delete all users except administrators', 'wp-clean'); ?>">?</span>
        </label>
        <label>
          <input type="checkbox" name="delete_terms" value="1">
          <?php echo esc_html__('Terms (categories, tags)', 'wp-clean'); ?>
          <span class="tooltip" title="<?php echo esc_attr__('Delete all categories, tags, and custom taxonomies', 'wp-clean'); ?>">?</span>
        </label>
      </div>
    </div>

    <div class="wp-clean-section">
      <h2><?php echo esc_html__('WooCommerce Data', 'wp-clean'); ?></h2>
      <div class="wp-clean-options">
        <label>
          <input type="checkbox" name="delete_wc_products" value="1">
          <?php echo esc_html__('Products', 'wp-clean'); ?>
          <span class="tooltip" title="<?php echo esc_attr__('Delete all WooCommerce products', 'wp-clean'); ?>">?</span>
        </label>
        <label>
          <input type="checkbox" name="delete_wc_orders" value="1">
          <?php echo esc_html__('Orders', 'wp-clean'); ?>
          <span class="tooltip" title="<?php echo esc_attr__('Delete all WooCommerce orders', 'wp-clean'); ?>">?</span>
        </label>
        <label>
          <input type="checkbox" name="delete_wc_coupons" value="1">
          <?php echo esc_html__('Coupons', 'wp-clean'); ?>
          <span class="tooltip" title="<?php echo esc_attr__('Delete all WooCommerce coupons', 'wp-clean'); ?>">?</span>
        </label>
      </div>
    </div>

    <div class="wp-clean-actions">
      <input type="submit" name="wp_clean_submit" id="wp-clean-submit" class="button button-primary" value="<?php echo esc_attr__('Review Selections', 'wp-clean'); ?>">
    </div>
  </form>

  <div id="wp-clean-confirmation" style="display: none;">
    <h2><?php echo esc_html__('Confirm Deletion', 'wp-clean'); ?></h2>
    <p><?php echo esc_html__('You are about to delete the following:', 'wp-clean'); ?></p>
    <ul id="wp-clean-summary"></ul>
    <p><?php echo esc_html__('This action cannot be undone. Are you sure you want to proceed?', 'wp-clean'); ?></p>
    <button id="wp-clean-confirm" class="button button-primary"><?php echo esc_html__('Confirm Deletion', 'wp-clean'); ?></button>
    <button id="wp-clean-cancel" class="button"><?php echo esc_html__('Cancel', 'wp-clean'); ?></button>
  </div>

  <div id="wp-clean-progress" style="display: none;">
    <h2><?php echo esc_html__('Deletion Progress', 'wp-clean'); ?></h2>
    <div class="wp-clean-progress-bar">
      <div id="wp-clean-progress-bar-inner" class="wp-clean-progress-bar-inner"></div>
    </div>
    <p id="wp-clean-progress-text"></p>
    <p id="wp-clean-estimate"></p>
  </div>

  <div id="wp-clean-log" style="display: none;">
    <h2><?php echo esc_html__('Deletion Log', 'wp-clean'); ?></h2>
    <textarea id="wp-clean-log-content" readonly></textarea>
    <button id="wp-clean-download-log" class="button"><?php echo esc_html__('Download Log', 'wp-clean'); ?></button>
  </div>
</div>