<?php
/**
 * Plugin Name: Pie Calendar Enhancements
 * Description: Pie Calendar Customizations
 * Version: 1.0
 * Author: Dave
 */

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