<?php
/**
 * Plugin Name: Pie Calendar Enhancements
 * Description: Pie Calendar Customizations
 * Version: 1.0.0
 * Author: Dave
 * Requires Plugins: pie-calendar
 * GitHub Plugin URI: dandrzejewski/pie-calendar-enhancements
 */

// Enable auto-updates by default on activation
register_activation_hook(__FILE__, 'pce_enable_auto_updates');
function pce_enable_auto_updates() {
    $plugin = plugin_basename(__FILE__);
    $auto_updates = (array) get_site_option('auto_update_plugins', array());
    if (!in_array($plugin, $auto_updates, true)) {
        $auto_updates[] = $plugin;
        update_site_option('auto_update_plugins', $auto_updates);
    }
}

// GitHub auto-update functionality
add_filter('pre_set_site_transient_update_plugins', 'pce_check_for_update');
function pce_check_for_update($transient) {
    if (empty($transient->checked)) return $transient;
    
    $plugin_slug = plugin_basename(__FILE__);
    $plugin_data = get_file_data(__FILE__, ['Version' => 'Version']);
    $current_version = $plugin_data['Version'];
    $github_repo = 'dandrzejewski/pie-calendar-enhancements';
    
    $remote = wp_remote_get("https://api.github.com/repos/{$github_repo}/releases/latest");
    
    // DEBUG - remove this after testing
    error_log('GitHub API Response Code: ' . wp_remote_retrieve_response_code($remote));
    error_log('GitHub API Response Body: ' . wp_remote_retrieve_body($remote));
    
    if (is_wp_error($remote) || wp_remote_retrieve_response_code($remote) != 200) {
        error_log('GitHub API call failed or no releases found');
        return $transient;
    }
    
    $release = json_decode(wp_remote_retrieve_body($remote));
    $version = ltrim($release->tag_name, 'v');
    
    $plugin_info = (object) [
        'slug' => dirname($plugin_slug),
        'new_version' => $version,
        'url' => $release->html_url,
        'package' => $release->assets[0]->browser_download_url ?? '',
    ];
    
    if (version_compare($current_version, $version, '<')) {
        $transient->response[$plugin_slug] = $plugin_info;
        error_log('Update available: ' . $version);
    } else {
        $transient->no_update[$plugin_slug] = $plugin_info;
        error_log('No update needed, adding to no_update');
    }
    
    return $transient;
}

add_action( 'piecal_additional_event_click_js', 'piecal_skip_popover' );
function piecal_skip_popover() {
    ?>
    Alpine.store('calendarEngine').showPopover = false;
    window.location.href = Alpine.store('calendarEngine').eventUrl ?? Alpine.store('calendarEngine').permalink;
    <?php
}


// Shortcode to display formatted event date range
add_shortcode('gears_event_date', 'gears_event_date_shortcode');

function gears_event_date_shortcode($atts) {
    $start = get_post_meta(get_the_ID(), '_piecal_start_date', true);
    $end = get_post_meta(get_the_ID(), '_piecal_end_date', true);
    
    if (empty($start)) {
        return '';
    }
    
    // Parse dates
    $start_date = DateTime::createFromFormat('Y-m-d\TH:i:s', $start);
    if (!$start_date) {
        $start_date = DateTime::createFromFormat('Y-m-d\TH:i', $start);
    }
    
    if (!empty($end)) {
        $end_date = DateTime::createFromFormat('Y-m-d\TH:i:s', $end);
        if (!$end_date) {
            $end_date = DateTime::createFromFormat('Y-m-d\TH:i', $end);
        }
    }
    
    if (!$start_date) {
        return '';
    }
    
    $output = $start_date->format('l, F j, Y \a\t g:i A');
    
    if (!empty($end_date)) {
        // Check if same day
        if ($start_date->format('Y-m-d') === $end_date->format('Y-m-d')) {
            $output .= ' - ' . $end_date->format('g:i A');
        } else {
            $output .= ' - ' . $end_date->format('l, F j, Y \a\t g:i A');
        }
    }
    
    return $output;
}