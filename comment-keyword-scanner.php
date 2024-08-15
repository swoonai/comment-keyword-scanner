<?php
/*
Plugin Name: Comment Keyword Scanner
Description: Scans comments for specific keywords and provides a report. Allows messaging users via Intercom.
Version: 1.25
Author: Made by Nat Grey
*/

// Define the log file path
define('CKW_LOG_FILE', WP_CONTENT_DIR . '/ckw-plugin.log');

// Function to log messages
function ckw_log($message) {
    $timestamp = date('[Y-m-d H:i:s]');
    $log_message = $timestamp . ' ' . $message . "\n";
    error_log($log_message, 3, CKW_LOG_FILE);
}
function ckw_init_log() {
    if (!file_exists(CKW_LOG_FILE)) {
        touch(CKW_LOG_FILE);
        chmod(CKW_LOG_FILE, 0666);
    }
}
// Define core keywords
function ckw_core_keywords() {
    ckw_log('Entering ckw_core_keywords function');
    $default_keywords = [
        'problem', 'issue', 'error', 'help', 'trouble', "can't", 'unable', 'fail', 'incorrect', 'broken', 
        'stuck', 'bug', 'glitch', 'crash', 'freeze', 'unresponsive', 'slow', 'lag', 'loading', 'timeout'
    ];
    
    $keywords = get_option('ckw_core_keywords', $default_keywords);
    
    // Ensure $keywords is an array
    if (!is_array($keywords)) {
        $keywords = explode(',', $keywords);
        $keywords = array_map('trim', $keywords);
    }
    
    ckw_log('Exiting ckw_core_keywords function');
    return $keywords;
}
// Define excluded keywords
function ckw_get_all_keywords() {
    $core_keywords = ckw_core_keywords();
    $excluded_keywords = get_option('ckw_excluded_keywords', '');
    
    // Ensure $excluded_keywords is an array
    if (!is_array($excluded_keywords)) {
        $excluded_keywords = explode(',', $excluded_keywords);
        $excluded_keywords = array_map('trim', $excluded_keywords);
    }
    
    return array(
        'core' => $core_keywords,
        'excluded' => $excluded_keywords
    );
}
// Add admin menu under Video Posts
add_action('admin_menu', 'ckw_admin_menu');
function ckw_admin_menu() {
    ckw_log('Entering ckw_admin_menu function');
    add_submenu_page('edit.php?post_type=video', 'CKW Admin', 'CKW Admin', 'manage_options', 'comment-keyword-scanner', 'ckw_settings_page');
    add_submenu_page('edit.php?post_type=video', 'Keyword Reports', 'Keyword Reports', 'manage_options', 'ckw-reports', 'ckw_reports_page');
    add_submenu_page('edit.php?post_type=video', 'Moderated Reports', 'Moderated Reports', 'manage_options', 'ckw-moderated-reports', 'ckw_moderated_reports_page');
    ckw_log('Exiting ckw_admin_menu function');
}
// Admin settings page
function ckw_settings_page() {
    ckw_log('Entering ckw_settings_page function');
    if (isset($_GET['settings-updated'])) {
        add_settings_error('ckw_messages', 'ckw_message', __('Settings Saved', 'comment-keyword-scanner'), 'updated');
    }
    
    settings_errors('ckw_messages');
    ?>
    <div class="wrap">
        <h1>Comment Keyword Scanner</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('ckw_settings_group');
            do_settings_sections('comment-keyword-scanner');
            submit_button();
            ?>
        </form>
    </div>
    <?php
    ckw_log('Exiting ckw_settings_page function');
}
// Register settings
add_action('admin_init', 'ckw_register_settings');
function ckw_register_settings() {
    register_setting('ckw_settings_group', 'ckw_keywords', 'ckw_sanitize_keywords');
    register_setting('ckw_settings_group', 'ckw_intercom_token', 'ckw_sanitize_intercom_token');
    register_setting('ckw_settings_group', 'ckw_intercom_admin_id', 'ckw_sanitize_admin_id');
    register_setting('ckw_settings_group', 'ckw_core_keywords', 'ckw_sanitize_core_keywords');
    register_setting('ckw_settings_group', 'ckw_excluded_keywords', 'ckw_sanitize_excluded_keywords');
    
    add_settings_section('ckw_main_section', 'Keywords and Intercom Settings', 'ckw_section_text', 'comment-keyword-scanner');
    
    add_settings_field('ckw_core_keywords', 'Core Keywords', 'ckw_core_keywords_field', 'comment-keyword-scanner', 'ckw_main_section');
    add_settings_field('ckw_intercom_token', 'Intercom Access Token', 'ckw_intercom_token_field', 'comment-keyword-scanner', 'ckw_main_section');
    add_settings_field('ckw_intercom_admin_id', 'Intercom Admin ID', 'ckw_admin_id_field', 'comment-keyword-scanner', 'ckw_main_section');
    add_settings_field('ckw_excluded_keywords', 'Excluded Keywords', 'ckw_excluded_keywords_field', 'comment-keyword-scanner', 'ckw_main_section');


}
function ckw_section_text() {
    ckw_log('Entering ckw_section_text function');
    echo '<p>Enter additional keywords to scan for in comments, separated by commas, and your Intercom settings below.</p>';
    ckw_log('Exiting ckw_section_text function');
}
function ckw_sanitize_keywords($input) {
    ckw_log('Entering ckw_sanitize_keywords function');
    $sanitized = sanitize_textarea_field($input);
    ckw_log('Exiting ckw_sanitize_keywords function');
    return $sanitized;
}
function ckw_sanitize_intercom_token($input) {
    ckw_log('Entering ckw_sanitize_intercom_token function');
    $sanitized = sanitize_text_field($input);
    ckw_log('Exiting ckw_sanitize_intercom_token function');
    return $sanitized;
}
function ckw_sanitize_admin_id($input) {
    ckw_log('Entering ckw_sanitize_admin_id function');
    $sanitized = sanitize_text_field($input);
    ckw_log('Exiting ckw_sanitize_admin_id function');
    return $sanitized;
}
function ckw_keywords_field() {
    ckw_log('Entering ckw_keywords_field function');
    $keywords = get_option('ckw_keywords', '');
    echo "<textarea name='ckw_keywords' rows='10' style='width:100%;'>" . esc_textarea($keywords) . "</textarea>";
    ckw_log('Exiting ckw_keywords_field function');
}
function ckw_intercom_token_field() {
    ckw_log('Entering ckw_intercom_token_field function');
    $token = get_option('ckw_intercom_token', '');
    echo "<input type='text' name='ckw_intercom_token' value='" . esc_attr($token) . "' style='width:100%;' />";
    ckw_log('Exiting ckw_intercom_token_field function');
}
function ckw_admin_id_field() {
    // ckw_log('Entering ckw_admin_id_field function');
    $admin_id = get_option('ckw_intercom_admin_id', '');
    echo "<input type='text' name='ckw_intercom_admin_id' value='" . esc_attr($admin_id) . "' style='width:100%;' />";
    // ckw_log('Exiting ckw_admin_id_field function');
}
// New excluded keywords section
function ckw_excluded_keywords_field() {
    $excluded_keywords = get_option('ckw_excluded_keywords', '');
    echo "<textarea name='ckw_excluded_keywords' rows='5' style='width:100%;'>" . esc_textarea($excluded_keywords) . "</textarea>";
    echo "<p class='description'>Enter keywords to exclude, separated by commas. Comments containing these keywords will not be reported, even if they contain core keywords.</p>";
}
function ckw_sanitize_excluded_keywords($input) {
    if (is_array($input)) {
        return array_map('sanitize_text_field', $input);
    }
    return sanitize_textarea_field($input);
}
// Ending excluded keywords section
// Create table on plugin activation
register_activation_hook(__FILE__, 'ckw_activate');
function ckw_activate() {
    ckw_init_log(); // Initialize the log file
    ckw_log('Entering ckw_activate function');
    global $wpdb;
    $table_name = $wpdb->prefix . 'ckw_comments';
    $charset_collate = $wpdb->get_charset_collate();
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $sql = "CREATE TABLE $table_name (
            comment_id BIGINT(20) UNSIGNED NOT NULL,
            post_id BIGINT(20) UNSIGNED NOT NULL,
            keywords TEXT NOT NULL,
            date DATETIME NOT NULL,
            email VARCHAR(100) NOT NULL,
            moderated BOOLEAN DEFAULT FALSE,
            deleted BOOLEAN DEFAULT FALSE,
            PRIMARY KEY (comment_id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    } else {
        // Add 'deleted' column if it doesn't exist
        $column = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'deleted'",
            DB_NAME, $table_name
        ));
        if (empty($column)) {
            $wpdb->query("ALTER TABLE $table_name ADD deleted BOOLEAN DEFAULT FALSE");
        }
    }
    ckw_log('Exiting ckw_activate function');
}
// Scan comments when a new comment is posted
add_action('comment_post', 'ckw_scan_comment', 10, 2);
function ckw_scan_comment($comment_id, $comment_approved) {
    ckw_log("Entering ckw_scan_comment function for comment ID: $comment_id");


    $comment = get_comment($comment_id);
    $post_type = get_post_type($comment->comment_post_ID);
    $user = get_user_by('email', $comment->comment_author_email);


    ckw_log("Comment ID: $comment_id, Post Type: $post_type, Approval Status: $comment_approved");


    // Check if the user is an administrator or shop manager
    if ($user && (in_array('administrator', $user->roles) || in_array('shop_manager', $user->roles))) {
        ckw_log("Skipping comment ID: $comment_id (admin or shop manager)");
        return;
    }


    if ($comment_approved == '1' && $post_type === 'video') {
        ckw_check_comment_for_keywords($comment_id);
    } else {
        ckw_log("Skipping comment ID: $comment_id (not approved or not a video post)");
    }


    ckw_log("Exiting ckw_scan_comment function for comment ID: $comment_id");
}
function ckw_add_comment_to_report($comment_id, $keywords, $email, $post_id) {
    ckw_log("Entering ckw_add_comment_to_report function for comment ID: $comment_id");


    if (empty($keywords) || !is_array($keywords)) {
        ckw_log("Warning: Invalid or empty keywords array passed for comment ID: $comment_id");
        return false;
    }


    $keywords_string = implode(', ', $keywords);
    ckw_log("Keywords to store: " . $keywords_string);
    ckw_log("Length of keywords string: " . strlen($keywords_string));


    global $wpdb;
    $table_name = $wpdb->prefix . 'ckw_comments';


    // Check if the comment already exists in the report
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT comment_id FROM $table_name WHERE comment_id = %d",
        $comment_id
    ));


    if ($existing) {
        ckw_log("Comment ID: $comment_id already exists in the report. Skipping insertion.");
        return false;
    }


    $comment = get_comment($comment_id);
    if (!$comment) {
        ckw_log("Comment not found for ID: $comment_id");
        return false;
    }


    $data = [
        'comment_id' => $comment_id,
        'post_id' => $post_id,
        'keywords' => $keywords_string,
        'date' => $comment->comment_date,
        'email' => $email,
        'moderated' => false
    ];


    $format = [
        '%d', // comment_id
        '%d', // post_id
        '%s', // keywords
        '%s', // date
        '%s', // email
        '%d'  // moderated
    ];


    $result = $wpdb->insert($table_name, $data, $format);


    if ($result === false) {
        ckw_log("Failed to insert comment into ckw_comments table: " . $wpdb->last_error);
        return false;
    } else {
        ckw_log("Successfully added comment ID: $comment_id to report with keywords: " . $keywords_string);
        
        // Verify the insertion
        $inserted_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE comment_id = %d", $comment_id), ARRAY_A);
        if ($inserted_data) {
            ckw_log("Verification: Data in database for comment ID $comment_id: " . print_r($inserted_data, true));
        } else {
            ckw_log("Warning: Unable to verify inserted data for comment ID $comment_id");
        }
        
        return true;
    }
}
// Display reports in the admin menu
function ckw_reports_page() {
    ckw_log('Entering ckw_reports_page function');


    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }


    // Handle manual cron execution
    if (isset($_POST['run_cron']) && check_admin_referer('run_cron_action', 'run_cron_nonce')) {
        $cron_result = ckw_run_manual_cron();
        echo '<div class="updated"><p>Manual cron job executed. ' . 
             'Total video posts scanned: ' . $cron_result['total_posts'] . '. ' . 
             'New comments found and processed: ' . $cron_result['processed'] . '. ' . 
             'Comments matching keywords: ' . $cron_result['comments_found'] . '. ' . 
             'New rows added to report: ' . $cron_result['new_comments_added'] . '. ' . 
             'Errors encountered: ' . $cron_result['errors'] . '.</p></div>';
    }


    // Handle moderation actions
    if (isset($_POST['ckw_moderate']) && wp_verify_nonce($_POST['ckw_nonce'], 'ckw_moderate_comment')) {
        $moderated = ckw_moderate_comment(intval($_POST['ckw_comment_id']));
        if ($moderated) {
            echo '<div class="updated"><p>Comment moderated successfully.</p></div>';
        } else {
            echo '<div class="error"><p>Failed to moderate comment.</p></div>';
        }
    }
    if (isset($_POST['ckw_reopen']) && wp_verify_nonce($_POST['ckw_nonce'], 'ckw_reopen_comment')) {
        $reopened = ckw_reopen_comment(intval($_POST['ckw_comment_id']));
        if ($reopened) {
            echo '<div class="updated"><p>Comment reopened successfully.</p></div>';
        } else {
            echo '<div class="error"><p>Failed to reopen comment.</p></div>';
        }
    }


    // Handle clear table action
    if (isset($_POST['clear_table']) && check_admin_referer('clear_table_action', 'clear_table_nonce')) {
        $clear_result = ckw_clear_table();
        if ($clear_result) {
            echo '<div class="updated"><p>Table cleared successfully.</p></div>';
        } else {
            echo '<div class="error"><p>Failed to clear table. Check the error log for details.</p></div>';
        }
    }


    // Handle CSV download
    if (isset($_POST['download_csv'])) {
        ckw_generate_csv_report();
    }


    // Pagination setup
    $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $per_page = 20; // You can make this configurable if you want


    $result = ckw_get_recent_comments($page, $per_page);


    if ($result === false) {
        echo '<div class="error"><p>Error retrieving comments. Please check the error log.</p></div>';
        return;
    }


    $reports = $result['comments'];
    $total_pages = $result['total_pages'];


    ?>
    <div class="wrap">
        <h1>Keyword Reports</h1>
        
        <!-- Add manual cron execution button -->
        <form method="post" action="" class="ckw-admin-buttons">
            <?php wp_nonce_field('run_cron_action', 'run_cron_nonce'); ?>
            <input type="submit" name="run_cron" class="button button-primary" value="Run Cron Manually">
        </form>
        
        <!-- Add clear table button -->
        <form method="post" action="" class="ckw-admin-buttons">
            <?php wp_nonce_field('clear_table_action', 'clear_table_nonce'); ?>
            <input type="submit" name="clear_table" class="button button-secondary" value="Clear Table" onclick="return confirm('Are you sure you want to clear the table? This action cannot be undone.');">
        </form>
        
        <!-- Add CSV download button -->
        <form method="post" action="" class="ckw-admin-buttons">
            <input type="submit" name="download_csv" class="button button-secondary" value="Download CSV">
        </form>


        <?php
        if (empty($reports)) {
            echo '<p>No matching comments found.</p>';
        } else {
        ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Comment ID</th>
                        <th>Keywords</th>
                        <th>Date</th>
                        <th>Post Title</th>
                        <th>Comment Link</th>
                        <th>User Email</th>
                        <th>Moderated</th>
                        <th>Comment Content</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($reports as $report) {
                        ckw_display_report_row($report);
                    }
                    ?>
                </tbody>
            </table>


            <?php
            // Pagination links
            echo '<div class="tablenav bottom">';
            echo '<div class="tablenav-pages">';
            echo paginate_links(array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => __('&laquo; Previous'),
                'next_text' => __('Next &raquo;'),
                'total' => $total_pages,
                'current' => $page
            ));
            echo '</div>';
            echo '</div>';
        }
        ?>
    </div>
    <?php


    ckw_log('Exiting ckw_reports_page function');
}
function ckw_get_recent_comments($page = 1, $per_page = 20) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ckw_comments';
    $offset = ($page - 1) * $per_page;
    
    ckw_log("Entering ckw_get_recent_comments function. Page: $page, Per Page: $per_page");
    
    $query = $wpdb->prepare(
        "SELECT *, intercom_conversation_id FROM $table_name WHERE moderated = 0 AND deleted = 0 AND keywords != '' ORDER BY date DESC LIMIT %d OFFSET %d",
        $per_page, $offset
    );
    
    ckw_log("Executing query: $query");
    
    $results = $wpdb->get_results($query, ARRAY_A);
    
    if ($results === null) {
        ckw_log("Database error in ckw_get_recent_comments: " . $wpdb->last_error);
        return false;
    }
    
    ckw_log("Number of results retrieved: " . count($results));
    
    $total_comments = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE moderated = 0 AND deleted = 0 AND keywords != ''");
    $total_pages = ceil($total_comments / $per_page);
    
    ckw_log("Total comments: $total_comments, Total pages: $total_pages");
    
    $return_array = array(
        'comments' => $results,
        'total_pages' => $total_pages,
        'current_page' => $page,
        'per_page' => $per_page
    );
    
    ckw_log("Exiting ckw_get_recent_comments function");
    
    return $return_array;
}
function ckw_display_report_row($report) {
    ckw_log("Entering ckw_display_report_row function for comment ID: {$report['comment_id']}");
    
    $comment = get_comment($report['comment_id']);
    $username = $comment ? $comment->comment_author : 'Unknown User';
    $post_title = get_the_title($report['post_id']);
    $post_url = get_permalink($report['post_id']);
    $comment_url = get_comment_link($report['comment_id']);
    $comment_content = $comment ? $comment->comment_content : 'Comment content not found';
    
    // Highlight keywords in comment content
    if (!empty($report['keywords'])) {
        $keywords = explode(',', $report['keywords']);
        foreach ($keywords as $keyword) {
            $keyword = trim($keyword);
            $comment_content = preg_replace(
                '/(' . preg_quote($keyword, '/') . ')/i',
                '<span style="background-color: yellow;">$1</span>',
                $comment_content
            );
        }
    }
    
    echo '<tr>';
    echo '<td>' . esc_html($report['comment_id']) . '</td>';
    
    // Display keywords or 'No keywords found'
    if (!empty($report['keywords'])) {
        echo '<td>' . esc_html($report['keywords']) . '</td>';
        ckw_log("Keywords for comment ID {$report['comment_id']}: {$report['keywords']}");
    } else {
        echo '<td>No keywords found</td>';
        ckw_log("No keywords found for comment ID {$report['comment_id']}");
    }
    
    echo '<td>' . esc_html($report['date']) . '</td>';
    
    // Display post title and link, or error message if not found
    if ($post_title && $post_url) {
        echo '<td><a href="' . esc_url($post_url) . '" target="_blank">' . esc_html($post_title) . '</a></td>';
    } else {
        echo '<td>Post not found</td>';
        ckw_log("Post not found for comment ID {$report['comment_id']}");
    }
    
    // Display comment link, or error message if not found
    if ($comment_url) {
        echo '<td><a href="' . esc_url($comment_url) . '" target="_blank">View Comment</a></td>';
    } else {
        echo '<td>Comment link not found</td>';
        ckw_log("Comment link not found for comment ID {$report['comment_id']}");
    }
    
    echo '<td>' . esc_html($report['email']) . '</td>';
    
    // Display checkmark for moderated comments
    echo '<td>' . ($report['moderated'] ? '✅' : 'No') . '</td>';
    
    // Display the actual comment content with highlighted keywords
    echo '<td>' . wp_kses_post($comment_content) . '</td>';
    
    // Display Intercom button, moderated status, and Remove button
    echo '<td>';
    if ($report['moderated']) {
        echo 'Moderated ✅';
    } else {
        if (!isset($report['deleted']) || !$report['deleted']) {
            echo '<button class="button send-intercom-message" data-email="' . esc_attr($report['email']) . '" data-comment-id="' . esc_attr($report['comment_id']) . '" data-username="' . esc_attr($username) . '" title="Send an in-app message via Intercom">Message User</button>';
            echo ' <button class="button ckw-exclude-button" data-comment-id="' . esc_attr($report['comment_id']) . '" title="Remove this comment from reports">Remove</button>';
        } else {
            echo '(Removed)';
        }
    }
    echo '</td>';
    
    echo '</tr>';
    
    ckw_log("Exiting ckw_display_report_row function for comment ID: {$report['comment_id']}");
}
// Add Intercom Script and Custom Launcher
add_action('admin_footer', 'ckw_add_intercom_script');
function ckw_add_intercom_script() {
    ckw_log('Entering ckw_add_intercom_script function');
    ?>
    <script>
function launchIntercom(email, commentId, username) {
    console.log("Sending Intercom in-app message for email: " + email);
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'ckw_create_intercom_message',
            email: email,
            comment_id: commentId,
            username: username,
            nonce: '<?php echo wp_create_nonce('ckw_create_intercom_message'); ?>'
        },
        success: function(response) {
            console.log("AJAX response:", response);
            if (response.success) {
                console.log("Intercom in-app message sent successfully.");
                alert('In-app message sent successfully via Intercom and comment auto-replied.');
                
                // Refresh the specific row
                var $row = jQuery('tr').filter(function() {
                    return jQuery(this).find('td:first').text() === commentId.toString();
                });
                
                if ($row.length) {
                    jQuery.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'ckw_refresh_report_row',
                            comment_id: commentId,
                            nonce: '<?php echo wp_create_nonce('ckw_refresh_report_row'); ?>'
                        },
                        success: function(rowHtml) {
                            $row.replaceWith(rowHtml);
                        },
                        error: function() {
                            console.error("Failed to refresh the row.");
                        }
                    });
                } else {
                    console.error("Row not found for comment ID: " + commentId);
                }
            } else {
                console.error("Failed to send Intercom in-app message:", response);
                alert('Failed to send in-app message. Please try again.');
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error("AJAX error:", textStatus, errorThrown);
            alert('An error occurred. Please try again.');
        }
    });
}
</script>
    <?php
    ckw_log('Exiting ckw_add_intercom_script function');
}
function ckw_create_intercom_message($email, $comment_id, $username) {
    ckw_log("Entering ckw_create_intercom_message function for email: $email, comment ID: $comment_id, username: $username");
    $token = get_option('ckw_intercom_token', '');
    $admin_id = get_option('ckw_intercom_admin_id', '');
    if (empty($token) || empty($admin_id)) {
        ckw_log("Intercom token or admin ID is missing");
        return false;
    }


    $comment = get_comment($comment_id);
    if (!$comment) {
        ckw_log("Comment not found for ID: $comment_id");
        return false;
    }


    // Create contact if not found
    $user_id = ckw_get_or_create_intercom_contact($email, $username, $token);
    if (!$user_id) {
        ckw_log("Failed to find or create contact in Intercom for email: $email");
        return false;
    }


    $post_title = get_the_title($comment->comment_post_ID);
    $comment_content = wp_strip_all_tags($comment->comment_content);


    $message_body = "Hello {$username} 👋,\n\n" .
                "I noticed your comment on Elizabeth's video, \"{$post_title}\": \"{$comment_content}.\"\n\n" .
                "I'm reaching out to see how I might assist you further. Could you please respond to this message? We're here to support you.\n\n" .
                "Thank you! ❤️\n\n" .
                "Elizabeth April Support Team";


    $payload = array(
        "message_type" => "inapp",
        "subject" => "Elizabeth April Support Follow-Up",
        "create_conversation_without_contact_reply" => true,
        "body" => $message_body,
        "from" => array(
            "type" => "admin",
            "id" => $admin_id
        ),
        "to" => array(
            "type" => "user",
            "id" => $user_id // Use the ID of the newly created or found user
        )
    );


    ckw_log("Sending request to Intercom API with payload: " . json_encode($payload));


    $response = wp_remote_post("https://api.intercom.io/messages", [
        'headers' => [
            "Authorization" => "Bearer " . $token,
            "Accept" => "application/json",
            "Content-Type" => "application/json"
        ],
        'body' => json_encode($payload),
        'method' => 'POST',
        'data_format' => 'body',
    ]);


    if (is_wp_error($response)) {
        ckw_log('Intercom API Error: ' . $response->get_error_message());
        return false;
    }


    $body = json_decode(wp_remote_retrieve_body($response), true);
    
    ckw_log("Intercom API response: " . json_encode($body));
    
    if (isset($body['conversation_id'])) {
        ckw_log("Successfully created Intercom in-app message. Conversation ID: {$body['conversation_id']}");
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'ckw_comments';
        $update_result = $wpdb->update(
            $table_name,
            array('intercom_conversation_id' => $body['conversation_id']),
            array('comment_id' => $comment_id),
            array('%s'),
            array('%d')
        );
        
        if ($update_result === false) {
            ckw_log("Failed to update conversation ID for comment ID: $comment_id. Database error: " . $wpdb->last_error);
        } else {
            ckw_log("Successfully updated conversation ID for comment ID: $comment_id");
        }
        
        return true;
    }


    ckw_log("Failed to create Intercom in-app message. Response does not contain conversation_id.");
    return false;
}
function ckw_get_or_create_intercom_contact($email, $username, $token) {
    ckw_log("Checking if contact exists in Intercom for email: $email");


    // Attempt to find the contact first
    $find_response = wp_remote_get("https://api.intercom.io/contacts?email=" . urlencode($email), [
        'headers' => [
            "Authorization" => "Bearer " . $token,
            "Accept" => "application/json",
        ],
    ]);


    if (is_wp_error($find_response)) {
        ckw_log('Intercom API Error (find contact): ' . $find_response->get_error_message());
        return false;
    }


    $find_body = json_decode(wp_remote_retrieve_body($find_response), true);


    if (!empty($find_body['data']) && isset($find_body['data'][0]['id'])) {
        ckw_log("Contact found in Intercom for email: $email");
        return $find_body['data'][0]['id'];
    }


    // If not found, create a new contact
    ckw_log("Contact not found, creating a new contact for email: $email");


    $create_payload = array(
        "role" => "user",
        "email" => $email,
        "name" => $username
    );


    $create_response = wp_remote_post("https://api.intercom.io/contacts", [
        'headers' => [
            "Authorization" => "Bearer " . $token,
            "Accept" => "application/json",
            "Content-Type" => "application/json"
        ],
        'body' => json_encode($create_payload),
        'method' => 'POST',
        'data_format' => 'body',
    ]);


    if (is_wp_error($create_response)) {
        ckw_log('Intercom API Error (create contact): ' . $create_response->get_error_message());
        return false;
    }


    $create_body = json_decode(wp_remote_retrieve_body($create_response), true);


    if (isset($create_body['id'])) {
        ckw_log("Successfully created a new contact in Intercom with ID: {$create_body['id']}");
        return $create_body['id'];
    }


    ckw_log("Failed to create contact in Intercom.");
    return false;
}
add_action('wp_ajax_ckw_create_intercom_message', 'ckw_ajax_create_intercom_message');
function ckw_ajax_create_intercom_message() {
    check_ajax_referer('ckw_create_intercom_message', 'nonce');
    
    $email = sanitize_email($_POST['email']);
    $comment_id = intval($_POST['comment_id']);
    $username = sanitize_text_field($_POST['username']);
    
    $result = ckw_create_intercom_message($email, $comment_id, $username);
    
    if ($result) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ckw_comments';
        
        // Update the moderated status
        $update_result = $wpdb->update(
            $table_name,
            array('moderated' => 1),
            array('comment_id' => $comment_id),
            array('%d'),
            array('%d')
        );
        
        if ($update_result === false) {
            ckw_log("Failed to update moderated status for comment ID: $comment_id. Database error: " . $wpdb->last_error);
            wp_send_json_error('Failed to update moderated status');
            return;
        }
        
        ckw_log("Successfully updated moderated status for comment ID: $comment_id");
        
        // Schedule the auto-reply function to run after the response is sent
        add_action('shutdown', function() use ($comment_id, $username) {
            sleep(2); // Add a 2-second delay
            $comment_result = ckw_auto_reply_to_comment($comment_id, $username);
            if ($comment_result) {
                ckw_log("Auto-reply comment added successfully for comment ID: $comment_id");
            } else {
                ckw_log("Failed to add auto-reply comment for comment ID: $comment_id");
            }
        });


        wp_send_json_success('Message sent successfully and comment moderated');
    } else {
        wp_send_json_error('Failed to send message');
    }
}
// Run Manual Cron
function ckw_run_manual_cron() {
    ckw_log('Manual cron job executed');


    global $wpdb;
    $table_name = $wpdb->prefix . 'ckw_comments';


    // Get all video posts
    $video_posts = get_posts(array(
        'post_type' => 'video',
        'posts_per_page' => -1,
        'fields' => 'ids'
    ));


    $total_posts = count($video_posts);
    ckw_log("Number of video posts to check: $total_posts");


    $processed = 0;
    $errors = 0;
    $comments_found = 0;
    $new_comments_added = 0;


    foreach ($video_posts as $post_id) {
        $comments = get_comments(array(
            'post_id' => $post_id,
            'status' => 'approve',
            'type' => 'comment',
            'date_query' => array(
                'after' => '90 days ago',
                'before' => 'today'
            )
        ));


        foreach ($comments as $comment) {
            // Check if the comment is already in the report
            $existing_comment = $wpdb->get_var($wpdb->prepare(
                "SELECT comment_id FROM $table_name WHERE comment_id = %d",
                $comment->comment_ID
            ));


            if (!$existing_comment) {
                $result = ckw_check_comment_for_keywords($comment->comment_ID);
                if ($result === true) {
                    $comments_found++;
                    $new_comments_added++;
                } elseif ($result === false) {
                    $errors++;
                }
                $processed++;
            }
        }
    }


    ckw_log("Manual cron job completed. Processed: $processed, Matching comments: $comments_found, New comments added: $new_comments_added, Errors: $errors");


    return array(
        'total_posts' => $total_posts,
        'processed' => $processed,
        'comments_found' => $comments_found,
        'new_comments_added' => $new_comments_added,
        'errors' => $errors
    );
}
add_action('comment_post', 'ckw_scan_comment', 10, 2);
// Add these functions to your codebase
function ckw_moderate_comment($comment_id) {
    ckw_log("Entering ckw_moderate_comment function for comment ID: $comment_id");
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'ckw_comments';
    
    $result = $wpdb->update(
        $table_name,
        array('moderated' => 1),
        array('comment_id' => $comment_id),
        array('%d'),
        array('%d')
    );
    
    if ($result === false) {
        ckw_log("Failed to moderate comment ID: $comment_id. Database error: " . $wpdb->last_error);
        return false;
    } else {
        ckw_log("Successfully moderated comment ID: $comment_id");
        return true;
    }
}
function ckw_reopen_comment($comment_id) {
    ckw_log("Entering ckw_reopen_comment function for comment ID: $comment_id");
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'ckw_comments';
    
    $result = $wpdb->update(
        $table_name,
        array('moderated' => 0),
        array('comment_id' => $comment_id),
        array('%d'),
        array('%d')
    );
    
    if ($result === false) {
        ckw_log("Failed to reopen comment ID: $comment_id. Database error: " . $wpdb->last_error);
        return false;
    } else {
        ckw_log("Successfully reopened comment ID: $comment_id");
        return true;
    }
}
function ckw_cleanup_no_keywords_records() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ckw_comments';


    ckw_log("Starting cleanup of 'no keyword found' records");


    // Count total records before cleanup
    $total_before = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    ckw_log("Total records before cleanup: $total_before");


    // Delete records with empty keywords
    $result = $wpdb->query("DELETE FROM $table_name WHERE keywords = ''");


    if ($result === false) {
        ckw_log("Error during cleanup: " . $wpdb->last_error);
        return false;
    }


    // Count total records after cleanup
    $total_after = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    $removed = $total_before - $total_after;


    ckw_log("Cleanup completed. Removed $removed records.");
    ckw_log("Total records after cleanup: $total_after");


    return true;
}
add_action('admin_menu', 'ckw_add_cleanup_menu');
function ckw_add_cleanup_menu() {
    add_submenu_page(
        'tools.php',
        'CKW Cleanup',
        'CKW Cleanup',
        'manage_options',
        'ckw-cleanup',
        'ckw_cleanup_page'
    );
}
function ckw_cleanup_page() {
    if (!current_user_can('manage_options')) {
        return;
    }


    if (isset($_POST['ckw_cleanup']) && check_admin_referer('ckw_cleanup_action')) {
        $result = ckw_cleanup_no_keywords_records();
        if ($result) {
            echo '<div class="notice notice-success"><p>Cleanup completed successfully. Check the error log for details.</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Cleanup failed. Check the error log for details.</p></div>';
        }
    }


    ?>
    <div class="wrap">
        <h1>CKW Cleanup</h1>
        <form method="post" action="">
            <?php wp_nonce_field('ckw_cleanup_action'); ?>
            <p>Click the button below to remove all records with no keywords found.</p>
            <p><strong>Warning:</strong> This action cannot be undone. Please backup your database before proceeding.</p>
            <input type="submit" name="ckw_cleanup" class="button button-primary" value="Run Cleanup">
        </form>
    </div>
    <?php
}
function ckw_check_comment_for_keywords($comment_id) {
    ckw_log("Entering ckw_check_comment_for_keywords function for comment ID: $comment_id");


    global $wpdb;
    $table_name = $wpdb->prefix . 'ckw_comments';


    $comment = get_comment($comment_id);
    if (!$comment) {
        ckw_log("Comment not found for ID: $comment_id");
        return false;
    }


    // Ignore comments from specific email addresses
$ignored_emails = array(
    'support@elizabethapril.com',
    'agnesweinschellemanalo@gmail.com',
    'carole.tchayem@gmail.com',
    'ea.christinac@gmail.com',
    'collin@emereald.com',
    'elizabeth@elizabethapril.com',
    'james@taylordcomputernetworks.com',
    'jetengine@elizabethapril.com',
    'Joinalabedin.my@gmail.com',
    'joinalabedin0709@gmail.com',
    'lauren@elizabethapril.com',
    'babylynpornela@gmail.com',
    'marketing@elizabethapril.com',
    'melodygay7@gmail.com',
    'pradeep04web@gmail.com',
    'rahulgargmzn@gmail.com',
    'rankmath@rankmath.com',
    'wordfence@elizabethapril.com',
    'zapier@elizabethapril.com'
);


if (in_array(strtolower($comment->comment_author_email), array_map('strtolower', $ignored_emails))) {
    ckw_log("Skipping comment ID: $comment_id from ignored email: " . $comment->comment_author_email);
    return null;
}


    // Check if the comment is already in the table
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE comment_id = %d",
        $comment_id
    ));


    if ($existing) {
        if ($existing->deleted) {
            ckw_log("Comment ID: $comment_id is marked as deleted. Skipping keyword check.");
            return null;
        }
        // If the comment exists and is not deleted, we'll update it
    }


    $comment_content = strtolower($comment->comment_content);
    $core_keywords = array_map('strtolower', ckw_core_keywords());
    $excluded_keywords = array_map('strtolower', explode(',', get_option('ckw_excluded_keywords', '')));


    // Check for excluded keywords first
    foreach ($excluded_keywords as $excluded_keyword) {
        $excluded_keyword = trim($excluded_keyword);
        if (!empty($excluded_keyword) && strpos($comment_content, $excluded_keyword) !== false) {
            ckw_log("Comment ID: $comment_id contains excluded keyword: $excluded_keyword. Skipping.");
            return null;
        }
    }


    $matched_keywords = array();


    // Function to check for keyword matches
    $check_keyword = function($keyword, $content) {
        // Exact phrase matching
        if (strpos($content, $keyword) !== false) {
            return true;
        }


        // Phrase variations (handling apostrophes and word boundaries)
        $variation_pattern = '/\b' . preg_quote(str_replace("'", "[']?", $keyword), '/') . '\b/';
        if (preg_match($variation_pattern, $content)) {
            return true;
        }


        // Boolean search logic (AND operation)
        if (strpos($keyword, ' AND ') !== false) {
            $boolean_terms = explode(' AND ', $keyword);
            foreach ($boolean_terms as $term) {
                if (strpos($content, trim($term)) === false) {
                    return false;
                }
            }
            return true;
        }


        return false;
    };


    // Check for core keywords
    foreach ($core_keywords as $keyword) {
        if ($check_keyword($keyword, $comment_content)) {
            $matched_keywords[] = $keyword;
        }
    }


    // Proceed if we have any matched keywords
    if (!empty($matched_keywords)) {
        $matched_keywords = array_unique($matched_keywords); // Remove duplicates
        $result = ckw_add_comment_to_report($comment_id, $matched_keywords, $comment->comment_author_email, $comment->comment_post_ID);
        if ($result) {
            ckw_log("Comment ID: $comment_id added/updated in report with keywords: " . implode(', ', $matched_keywords));
            return true;
        } else {
            ckw_log("Failed to add/update comment ID: $comment_id in report");
            return false;
        }
    } else {
        ckw_log("No matching keywords found for comment ID: $comment_id. Not adding to report.");
        return null;
    }
}
// When comment gets approved it checks if it matches keywords and adds to report
add_action('transition_comment_status', 'ckw_check_approved_comment', 10, 3);
function ckw_check_approved_comment($new_status, $old_status, $comment) {
    if ($new_status == 'approved' && $old_status != 'approved' && get_post_type($comment->comment_post_ID) === 'video') {
        ckw_check_comment_for_keywords($comment->comment_ID);
    }
}
function ckw_auto_reply_to_comment($comment_id, $username) {
    ckw_log("Entering ckw_auto_reply_to_comment function for comment ID: $comment_id");


    $original_comment = get_comment($comment_id);
    if (!$original_comment) {
        ckw_log("Original comment not found for ID: $comment_id");
        return false;
    }


    $first_name = explode(' ', $username)[0]; // Get the first name


    $reply_content = "Hello {$first_name},


We have sent an email to assist you with your issue. Could you please check your inbox and respond so that we can provide further support?


Thank you!


Elizabeth April Support Team";


    // Get the admin user by email
    $admin_user = get_user_by('email', 'support@elizabethapril.com');


    $reply_data = array(
        'comment_post_ID'      => $original_comment->comment_post_ID,
        'comment_author'       => $admin_user ? $admin_user->display_name : 'Elizabeth April Support Team',
        'comment_author_email' => $admin_user ? $admin_user->user_email : 'support@elizabethapril.com',
        'comment_author_url'   => '',
        'comment_content'      => $reply_content,
        'comment_type'         => '',
        'comment_parent'       => $comment_id,
        'user_id'              => $admin_user ? $admin_user->ID : 0,
        'comment_approved'     => 1,
    );


    $new_comment_id = wp_insert_comment($reply_data);


    if ($new_comment_id) {
        ckw_log("Auto-reply comment added successfully. New comment ID: $new_comment_id");
        return true;
    } else {
        ckw_log("Failed to add auto-reply comment");
        return false;
    }
}
function ckw_exclude_comment() {
    check_ajax_referer('ckw_exclude_comment', 'ckw_exclude_nonce');
    
    $comment_id = intval($_POST['ckw_comment_id']);
    global $wpdb;
    $table_name = $wpdb->prefix . 'ckw_comments';
    $result = $wpdb->update(
        $table_name,
        array('deleted' => 1),
        array('comment_id' => $comment_id),
        array('%d'),
        array('%d')
    );
    
    if ($result !== false) {
        ckw_log("Comment ID: $comment_id successfully excluded from future reports.");
        wp_send_json_success('Comment successfully excluded from future reports.');
    } else {
        ckw_log("Failed to exclude comment ID: $comment_id. Database error: " . $wpdb->last_error);
        wp_send_json_error('Failed to exclude comment. Please try again.');
    }
}
add_action('wp_ajax_ckw_exclude_comment', 'ckw_exclude_comment');
function ckw_add_admin_scripts() {
    $screen = get_current_screen();
    if ($screen->id != 'video_page_ckw-reports') {
        return;
    }


    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('.send-intercom-message').on('click', function() {
            var button = $(this);
            var email = button.data('email');
            var commentId = button.data('comment-id');
            var username = button.data('username');
            launchIntercom(email, commentId, username);
        });


        $(document).on('click', '.ckw-exclude-button', function() {
            var button = $(this);
            var commentId = button.data('comment-id');
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ckw_exclude_comment',
                    ckw_comment_id: commentId,
                    ckw_exclude_nonce: '<?php echo wp_create_nonce("ckw_exclude_comment"); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        button.closest('td').html('(Excluded from Moderation)');
                    } else {
                        alert(response.data);
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                }
            });
        });
    });
    </script>
    <?php
}
add_action('admin_footer', 'ckw_add_admin_scripts');
function ckw_sanitize_core_keywords($input) {
    ckw_log('Entering ckw_sanitize_core_keywords function');
    if (is_array($input)) {
        $sanitized = array_map('sanitize_text_field', $input);
    } else {
        $sanitized = sanitize_textarea_field($input);
    }
    ckw_log('Exiting ckw_sanitize_core_keywords function');
    return $sanitized;
}
function ckw_core_keywords_field() {
    ckw_log('Entering ckw_core_keywords_field function');
    $keywords = ckw_core_keywords();
    echo "<textarea name='ckw_core_keywords' rows='10' style='width:100%;'>" . esc_textarea(implode(', ', $keywords)) . "</textarea>";
    echo "<p class='description'>Enter core keywords, separated by commas. These keywords will be used in addition to any keywords entered in the 'Additional Keywords' field.</p>";
    ckw_log('Exiting ckw_core_keywords_field function');
}
function ckw_clear_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ckw_comments';
    
    $result = $wpdb->query("TRUNCATE TABLE $table_name");
    
    if ($result !== false) {
        ckw_log("Table $table_name has been cleared successfully.");
        return true;
    } else {
        ckw_log("Failed to clear table $table_name. Error: " . $wpdb->last_error);
        return false;
    }
}
function ckw_generate_csv_report() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ckw_comments';


    // Fetch the necessary data
    $comments = $wpdb->get_results("
        SELECT 
            ckw.comment_id,
            ckw.keywords,
            ckw.date,
            p.post_title,
            CONCAT('" . get_site_url() . "/wp-admin/comment.php?action=editcomment&c=', ckw.comment_id) as comment_link,
            ckw.email as user_email,
            IF(ckw.intercom_conversation_id IS NOT NULL, CONCAT('https://app.intercom.com/a/inbox/uk54y72o/inbox/conversation/', ckw.intercom_conversation_id), 'N/A') as intercom_link,
            IF(ckw.moderated = 1, 'Yes', 'No') as moderated,
            c.comment_content,
            'Send Message / Exclude' as actions
        FROM $table_name ckw
        JOIN {$wpdb->comments} c ON ckw.comment_id = c.comment_ID
        JOIN {$wpdb->posts} p ON ckw.post_id = p.ID
        ORDER BY ckw.comment_id DESC
    ", ARRAY_A);


    if (!$comments) {
        ckw_log('No comments found for export.');
        return false;
    }


    $filename = 'ckw_report_' . date('Y-m-d_H-i-s') . '.csv';


    // Clear output buffer before outputting the file
    if (ob_get_length()) {
        ob_end_clean();
    }


    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '";');


    $file = fopen('php://output', 'w');


    if (!$file) {
        ckw_log('Failed to open php://output stream.');
        return false;
    }


    // Define the CSV headers
    $header = [
        'Comment ID', 
        'Keywords', 
        'Date', 
        'Post Title', 
        'Comment Link', 
        'User Email', 
        'Intercom Link', 
        'Moderated', 
        'Comment Content', 
        'Actions'
    ];
    fputcsv($file, $header);


    // Output the rows
    foreach ($comments as $comment) {
        $row = [
            $comment['comment_id'],
            $comment['keywords'],
            $comment['date'],
            $comment['post_title'],
            $comment['comment_link'],
            $comment['user_email'],
            $comment['intercom_link'],
            $comment['moderated'],
            $comment['comment_content'],
            $comment['actions']
        ];
        fputcsv($file, $row);
    }


    fclose($file);


    // Ensure the script exits after output
    exit;
}
function ckw_add_admin_styles() {
    $screen = get_current_screen();
    if ($screen->id != 'video_page_ckw-reports') {
        return;
    }


    ?>
    <style type="text/css">
        .ckw-admin-buttons form {
            display: inline-block;
            margin-right: 10px;
        }
    </style>
    <?php
}
add_action('admin_head', 'ckw_add_admin_styles');
function ckw_moderated_reports_page() {
    ckw_log('Entering ckw_moderated_reports_page function');


    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }


    // Handle CSV download
    if (isset($_POST['download_csv'])) {
        ckw_generate_csv_report(true);
    }


    // Pagination setup
    $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $per_page = 20; // You can make this configurable if you want


    $result = ckw_get_moderated_comments($page, $per_page);


    if ($result === false) {
        echo '<div class="error"><p>Error retrieving comments. Please check the error log.</p></div>';
        return;
    }


    $reports = $result['comments'];
    $total_pages = $result['total_pages'];


    ?>
    <div class="wrap">
        <h1>Moderated Reports</h1>
        
        <!-- Add CSV download button -->
        <form method="post" action="" class="ckw-admin-buttons">
            <input type="submit" name="download_csv" class="button button-secondary" value="Download CSV">
        </form>


        <?php
        if (empty($reports)) {
            echo '<p>No moderated comments found.</p>';
        } else {
        ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Comment ID</th>
                        <th>Keywords</th>
                        <th>Date</th>
                        <th>Post Title</th>
                        <th>Comment Link</th>
                        <th>User Email</th>
                        <th>Intercom Link</th>
                        <th>Moderated</th>
                        <th>Comment Content</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($reports as $report) {
                        ckw_display_moderated_report_row($report);
                    }
                    ?>
                </tbody>
            </table>


            <?php
            // Pagination links
            echo '<div class="tablenav bottom">';
            echo '<div class="tablenav-pages">';
            echo paginate_links(array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => __('&laquo; Previous'),
                'next_text' => __('Next &raquo;'),
                'total' => $total_pages,
                'current' => $page
            ));
            echo '</div>';
            echo '</div>';
        }
        ?>
    </div>
    <?php


    ckw_log('Exiting ckw_moderated_reports_page function');
}
function ckw_display_moderated_report_row($report) {
    $comment = get_comment($report['comment_id']);
    $username = $comment ? $comment->comment_author : 'Unknown User';
    $post_title = get_the_title($report['post_id']);
    $post_url = get_permalink($report['post_id']);
    $comment_url = get_comment_link($report['comment_id']);
    $comment_content = $comment ? $comment->comment_content : 'Comment content not found';
    
    echo '<tr>';
    echo '<td>' . esc_html($report['comment_id']) . '</td>';
    
    if (!empty($report['keywords'])) {
        echo '<td>' . esc_html($report['keywords']) . '</td>';
    } else {
        echo '<td>No keywords found</td>';
    }
    
    echo '<td>' . esc_html($report['date']) . '</td>';
    
    if ($post_title && $post_url) {
        echo '<td><a href="' . esc_url($post_url) . '" target="_blank">' . esc_html($post_title) . '</a></td>';
    } else {
        echo '<td>Post not found</td>';
    }
    
    if ($comment_url) {
        echo '<td><a href="' . esc_url($comment_url) . '" target="_blank">View Comment</a></td>';
    } else {
        echo '<td>Comment link not found</td>';
    }
    
    echo '<td>' . esc_html($report['email']) . '</td>';
    
    // Display Intercom Link
    $intercom_base_url = 'https://app.intercom.com/a/inbox/uk54y72o/inbox/conversation/';
    $intercom_conversation_id = isset($report['intercom_conversation_id']) ? $report['intercom_conversation_id'] : '';
    echo '<td>';
    if (!empty($intercom_conversation_id)) {
        $intercom_full_url = $intercom_base_url . $intercom_conversation_id;
        echo '<a href="' . esc_url($intercom_full_url) . '" target="_blank" rel="noopener noreferrer">View in Intercom</a>';
    } else {
        echo 'N/A';
    }
    echo '</td>';
    
    echo '<td>' . ($report['moderated'] ? '✅' : 'No') . '</td>';
    
    echo '<td>' . esc_html($comment_content) . '</td>';
    echo '</tr>';
}
function ckw_get_moderated_comments($page = 1, $per_page = 20) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ckw_comments';
    $offset = ($page - 1) * $per_page;
    
    ckw_log("Entering ckw_get_moderated_comments function. Page: $page, Per Page: $per_page");
    
    $query = $wpdb->prepare(
        "SELECT * FROM $table_name WHERE moderated = 1 AND deleted = 0 ORDER BY date DESC LIMIT %d OFFSET %d",
        $per_page, $offset
    );
    
    $results = $wpdb->get_results($query, ARRAY_A);
    
    if ($results === null) {
        ckw_log("Database error in ckw_get_moderated_comments: " . $wpdb->last_error);
        return false;
    }
    
    $total_comments = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE moderated = 1 AND deleted = 0");
    $total_pages = ceil($total_comments / $per_page);
    
    return array(
        'comments' => $results,
        'total_pages' => $total_pages,
        'current_page' => $page,
        'per_page' => $per_page
    );
}
function ckw_exclude_from_moderation() {
    check_ajax_referer('ckw_exclude_from_moderation', 'nonce');
    
    $comment_id = intval($_POST['comment_id']);
    $keyword = sanitize_text_field($_POST['keyword']);
    
    // Add keyword to excluded keywords
    $excluded_keywords = get_option('ckw_excluded_keywords', '');
    $excluded_keywords_array = explode(',', $excluded_keywords);
    $excluded_keywords_array[] = $keyword;
    $excluded_keywords_array = array_unique(array_map('trim', $excluded_keywords_array));
    update_option('ckw_excluded_keywords', implode(',', $excluded_keywords_array));
    
    // Flag comment as excluded
    global $wpdb;
    $table_name = $wpdb->prefix . 'ckw_comments';
    $result = $wpdb->update(
        $table_name,
        array('deleted' => 1),
        array('comment_id' => $comment_id),
        array('%d'),
        array('%d')
    );
    
    if ($result !== false) {
        wp_send_json_success('Comment excluded from moderation successfully.');
    } else {
        wp_send_json_error('Failed to exclude comment from moderation.');
    }
}
add_action('wp_ajax_ckw_exclude_from_moderation', 'ckw_exclude_from_moderation');