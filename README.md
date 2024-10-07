# WP-Clean

WP-Clean is a powerful WordPress plugin designed to help administrators clean up and optimize their WordPress sites. It provides tools for selectively deleting various types of content, exporting data before deletion, and optimizing the database.

## Features

- Selective content deletion (posts, pages, comments, users, terms, media)
- Date range filtering for content deletion
- Data export functionality before deletion
- Database optimization
- User-friendly interface with progress tracking
- Scheduled deletion option

## Installation

1. Download the `wp-clean.zip` file from the [releases page](https://github.com/open-wp-club/wp-clean/releases).
2. Log in to your WordPress admin panel.
3. Navigate to Plugins > Add New.
4. Click on the "Upload Plugin" button at the top of the page.
5. Choose the `wp-clean.zip` file you downloaded and click "Install Now".
6. After installation, click "Activate" to enable the plugin.

## Usage

1. In your WordPress admin panel, go to Tools > WP-Clean.
2. Select the types of content you want to delete.
3. (Optional) Set a date range for content deletion.
4. (Optional) Use the "Export Before Delete" button to backup your data before deletion.
5. Click "Delete Selected Content" to start the cleanup process.
6. Monitor the progress bar for deletion status.

## Frequently Asked Questions

### Is it safe to use this plugin?

While WP-Clean is designed with safety in mind, it's always recommended to backup your website before performing any bulk deletions or database optimizations.

### Can I undo the deletions?

No, the deletions performed by WP-Clean are permanent. Always use the export feature if you want to keep a backup of the data before deletion.

### How does the date range filter work?

The date range filter allows you to delete content created within a specific time period. This is useful for removing old or outdated content while keeping recent items.

### Does this plugin work with multisite installations?

Currently, WP-Clean is designed for single-site WordPress installations. Multisite support may be added in future versions.

## For Developers

### Hooks and Filters

WP-Clean provides several hooks and filters for developers to extend its functionality:

- `wp_clean_before_deletion`: Action hook that runs before content deletion starts.
- `wp_clean_after_deletion`: Action hook that runs after content deletion completes.
- `wp_clean_export_data`: Filter to modify export data before CSV creation.

Example usage:

```php
add_action('wp_clean_before_deletion', 'my_custom_function');
function my_custom_function() {
    // Your code here
}
```

### Contributing

We welcome contributions to WP-Clean! Please follow these steps to contribute:

1. Fork the repository.
2. Create a new branch for your feature or bug fix.
3. Write your code and tests.
4. Submit a pull request with a clear description of your changes.


## License

WP-Clean is licensed under the GPL v2 License. See the [LICENSE](LICENSE) file for details.
