<?php
/**
 * Plugin Name: Export all your WordPress URLs
 * Description: Export all posts, pages, and custom post types with their URLs to CSV.
 * Version: 1.3
 * Author: Mauricio Andres Rodriguez
 */

// Start session early before any output
add_action('init', function () {
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_start();
    }
}, 1);

// Debug function
function export_urls_debug_log($message)
{
    // Enable file logging (comment out if not needed)
    file_put_contents(WP_CONTENT_DIR . '/export-debug.log', date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);

    // Store in session for display
    if (!isset($_SESSION['export_debug'])) {
        $_SESSION['export_debug'] = [];
    }
    $_SESSION['export_debug'][] = $message;
}

// Add admin menu page
add_action('admin_menu', function () {
    add_menu_page('Export All URLs', 'Export All URLs', 'manage_options', 'export-all-urls', 'export_all_urls_page');
});

// Add AJAX handler for content download
add_action('wp_ajax_export_urls_ajax', 'export_urls_ajax_handler');
function export_urls_ajax_handler()
{
    check_admin_referer('export_urls_ajax_nonce', 'export_nonce');
    export_urls_debug_log("Starting AJAX export handler for content");
    export_all_urls_csv();
    exit;
}

// Add AJAX handler for media download
add_action('wp_ajax_export_media_ajax', 'export_media_ajax_handler');
function export_media_ajax_handler()
{
    check_admin_referer('export_media_ajax_nonce', 'media_nonce');
    export_urls_debug_log("Starting AJAX export handler for media");
    export_media_urls_csv();
    exit;
}

// Render the admin page
function export_all_urls_page()
{
    // Add admin notice if there was an error
    if (isset($_GET['export_error'])) {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($_GET['export_error']) . '</p></div>';
    }

    // Show success message
    if (isset($_GET['export_success'])) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($_GET['export_success']) . '</p></div>';
    }

    // Display debug messages if any
    if (isset($_SESSION['export_debug']) && !empty($_SESSION['export_debug'])) {
        echo '<div class="notice notice-info is-dismissible"><p><strong>Debug Log:</strong></p><pre>';
        foreach ($_SESSION['export_debug'] as $message) {
            echo esc_html($message) . "\n";
        }
        echo '</pre></div>';

        // Clear debug messages
        unset($_SESSION['export_debug']);
    }
    ?>
    <div class="wrap">
        <h1>Export All URLs</h1>
        <p>Click the button below to download a CSV of all posts, pages, and custom post types with URLs.</p>

        <form method="post" action="<?php echo admin_url('admin-ajax.php'); ?>">
            <input type="hidden" name="action" value="export_urls_ajax">
            <?php wp_nonce_field('export_urls_ajax_nonce', 'export_nonce'); ?>
            <input type="submit" class="button-primary" value="Download Content URLs">
        </form>

        <hr>
        <h3>Export Media URLs</h3>
        <p>Click the button below to download a CSV of media attachments only.</p>

        <form method="post" action="<?php echo admin_url('admin-ajax.php'); ?>">
            <input type="hidden" name="action" value="export_media_ajax">
            <?php wp_nonce_field('export_media_ajax_nonce', 'media_nonce'); ?>
            <input type="submit" class="button-primary" value="Download Media URLs">
        </form>

        <hr>
        <h3>Troubleshooting</h3>
        <p>Check the <code>wp-content/export-debug.log</code> file for detailed debug information.</p>
    </div>
    <?php
}

// Generate and download the CSV file for content (excludes attachments)
function export_all_urls_csv()
{
    export_urls_debug_log("Starting content export function");

    // Ensure user has permission
    if (!current_user_can('manage_options')) {
        export_urls_debug_log("Permission denied");
        wp_die('You do not have sufficient permissions to access this page.');
    }

    // Increase memory limit and execution time
    ini_set('memory_limit', '512M');
    set_time_limit(300); // 5 minutes
    export_urls_debug_log("Set memory limit to 512M and time limit to 300s");

    try {
        // Clear all output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        export_urls_debug_log("Cleared output buffers");

        // Create temporary file for CSV
        $temp_file = wp_tempnam('export-urls-');
        export_urls_debug_log("Created temp file: " . $temp_file);

        $output = fopen($temp_file, 'w');
        if ($output === false) {
            export_urls_debug_log("Failed to open temp file");
            throw new Exception('Failed to create temporary file for export.');
        }

        // Write CSV header
        fputcsv($output, ['ID', 'Title', 'Post Type', 'Status', 'URL']);
        export_urls_debug_log("CSV header written");

        // Get all public post types EXCEPT attachment
        $post_types = get_post_types(['public' => true], 'names');

        // Remove attachment post type
        if (isset($post_types['attachment'])) {
            unset($post_types['attachment']);
        }

        export_urls_debug_log("Found " . count($post_types) . " content post types: " . implode(', ', $post_types));

        $total_posts = 0;

        // Loop through each post type
        foreach ($post_types as $post_type) {
            $args = [
                'post_type' => $post_type,
                'post_status' => 'any',
                'posts_per_page' => -1,
                'fields' => 'ids',
            ];

            $posts = get_posts($args);
            export_urls_debug_log("Found " . count($posts) . " posts for post type: " . $post_type);

            if (is_wp_error($posts)) {
                export_urls_debug_log("Error getting posts for " . $post_type . ": " . $posts->get_error_message());
                continue; // Skip if there's an error
            }

            foreach ($posts as $post_id) {
                $title = html_entity_decode(get_the_title($post_id));
                $status = get_post_status($post_id);
                $url = get_permalink($post_id);

                fputcsv($output, [
                    $post_id,
                    $title,
                    $post_type,
                    $status,
                    $url
                ]);
                $total_posts++;
            }
        }

        fclose($output);
        export_urls_debug_log("Total content posts exported: " . $total_posts);
        export_urls_debug_log("Content CSV file created successfully");

        // Set headers for CSV download
        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="content-urls-' . date('Y-m-d-H-i') . '.csv"');
        header('Content-Length: ' . filesize($temp_file));
        export_urls_debug_log("Headers set for CSV download");

        // Output the file contents
        readfile($temp_file);
        export_urls_debug_log("File content sent to browser");

        // Delete the temporary file
        @unlink($temp_file);
        export_urls_debug_log("Temp file deleted");

        exit; // Stop execution after sending the file
    } catch (Exception $e) {
        export_urls_debug_log("Exception: " . $e->getMessage());
        throw $e;
    }
}

// Generate and download the CSV file for media attachments only
function export_media_urls_csv()
{
    export_urls_debug_log("Starting media export function");

    // Ensure user has permission
    if (!current_user_can('manage_options')) {
        export_urls_debug_log("Permission denied");
        wp_die('You do not have sufficient permissions to access this page.');
    }

    // Increase memory limit and execution time
    ini_set('memory_limit', '512M');
    set_time_limit(300); // 5 minutes
    export_urls_debug_log("Set memory limit to 512M and time limit to 300s");

    try {
        // Clear all output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        export_urls_debug_log("Cleared output buffers");

        // Create temporary file for CSV
        $temp_file = wp_tempnam('export-media-');
        export_urls_debug_log("Created temp file: " . $temp_file);

        $output = fopen($temp_file, 'w');
        if ($output === false) {
            export_urls_debug_log("Failed to open temp file");
            throw new Exception('Failed to create temporary file for export.');
        }

        // Write CSV header with additional media-specific fields
        fputcsv($output, ['ID', 'Title', 'Post Type', 'Status', 'URL', 'File URL', 'File Type', 'File Size']);
        export_urls_debug_log("CSV header written");

        $total_media = 0;

        // Get media attachments
        $args = [
            'post_type' => 'attachment',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ];

        $media_items = get_posts($args);
        export_urls_debug_log("Found " . count($media_items) . " media attachments");

        if (is_wp_error($media_items)) {
            export_urls_debug_log("Error getting media: " . $media_items->get_error_message());
            throw new Exception('Error retrieving media attachments: ' . $media_items->get_error_message());
        }

        foreach ($media_items as $media_id) {
            $title = html_entity_decode(get_the_title($media_id));
            $status = get_post_status($media_id);
            $url = get_permalink($media_id);

            // Get additional media details
            $file_url = wp_get_attachment_url($media_id);
            $file_type = get_post_mime_type($media_id);

            // Get file size if possible
            $file_path = get_attached_file($media_id);
            $file_size = file_exists($file_path) ? size_format(filesize($file_path), 2) : 'Unknown';

            fputcsv($output, [
                $media_id,
                $title,
                'attachment',
                $status,
                $url,
                $file_url,
                $file_type,
                $file_size
            ]);
            $total_media++;
        }

        fclose($output);
        export_urls_debug_log("Total media items exported: " . $total_media);
        export_urls_debug_log("Media CSV file created successfully");

        // Set headers for CSV download
        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="media-urls-' . date('Y-m-d-H-i') . '.csv"');
        header('Content-Length: ' . filesize($temp_file));
        export_urls_debug_log("Headers set for CSV download");

        // Output the file contents
        readfile($temp_file);
        export_urls_debug_log("File content sent to browser");

        // Delete the temporary file
        @unlink($temp_file);
        export_urls_debug_log("Temp file deleted");

        exit; // Stop execution after sending the file
    } catch (Exception $e) {
        export_urls_debug_log("Exception: " . $e->getMessage());
        throw $e;
    }
}