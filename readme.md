# Export-all-your-WordPress-URLs - WordPress Plugin

A simple yet powerful WordPress plugin that exports all posts, pages, custom post types, and media URLs to CSV files.

## Description

Export All URLs allows WordPress administrators to easily generate comprehensive CSV exports of all content and media URLs on their sites. This is particularly useful for:

- Site migrations and content audits
- SEO analysis and optimization
- Documentation and backup purposes
- Content inventory management

The plugin provides separate exports for content (posts, pages, custom post types) and media attachments, making it easy to work with different aspects of your site.

## Installation

1. Download the contents of this repo, select 'Download ZIP'
2. Upload the `export-all-urls` folder to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Access the tool via the new "Export All URLs" item in your admin menu

## Features

- **Content URL Export**: Generate CSV files containing IDs, titles, post types, statuses, and URLs for all content (excluding media attachments)
- **Media URL Export**: Create separate CSVs with detailed information about media files including direct file URLs, file types, and sizes
- **High Performance**: Designed to handle large sites with memory optimization and timeout prevention
- **Detailed Logging**: Comprehensive debug logging to help troubleshoot any issues

## Usage

### Exporting Content URLs

1. Navigate to the "Export All URLs" page in your WordPress admin menu
2. Click the "Download Content URLs" button
3. A CSV file will be downloaded containing information about all posts, pages, and custom post types

### Exporting Media URLs

1. Navigate to the "Export All URLs" page in your WordPress admin menu
2. Scroll down to the "Export Media URLs" section
3. Click the "Download Media URLs" button
4. A CSV file will be downloaded with detailed information about all media attachments

### CSV Contents

#### Content Export Contains:

- ID
- Title
- Post Type
- Status
- URL

#### Media Export Contains:

- ID
- Title
- Post Type (always "attachment")
- Status
- URL
- File URL (direct link to the file)
- File Type (MIME type)
- File Size

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- Sufficient memory allocation for sites with large numbers of posts

## Troubleshooting

If you encounter any issues:

1. Check the debug log at `wp-content/export-debug.log`
2. Ensure your server has sufficient memory and execution time limits
3. For very large sites, consider increasing PHP memory limits and execution time

## Support

For support or feature requests, please contact the plugin author or open an issue on the plugin repository.

## License

This plugin is licensed under the GPL v2 or later.
