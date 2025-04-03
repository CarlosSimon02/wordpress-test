<?php
/**
 * Template Name: Supafaya Event Detail Page
 * Description: A template for displaying individual event details
 */

get_header();

// Get the event ID from URL parameter
$event_id = isset($_GET['event_id']) ? sanitize_text_field($_GET['event_id']) : '';

if (empty($event_id)) {
    echo '<div class="supafaya-event-error container">';
    echo '<p>No event ID was provided. Please go back to the events page and select an event.</p>';
    echo '</div>';
} else {
    // Use the event_shortcode from EventController to display the event
    if (function_exists('do_shortcode')) {
        echo do_shortcode('[supafaya_event event_id="' . esc_attr($event_id) . '" template="default"]');
    } else {
        echo '<p>Error: Shortcode functionality is not available.</p>';
    }
}

get_footer(); 