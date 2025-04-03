<?php
/*
Plugin Name: Simple API Fetcher
Description: Fetches and displays data from JSONPlaceholder API.
Version: 1.0
Author: Your Name
*/

// Shortcode to display fetched data: [fetch_api_data]
add_shortcode('fetch_api_data', 'fetch_api_data_func');

function fetch_api_data_func() {
    // API URL (we'll fetch posts from JSONPlaceholder)
    $api_url = 'https://jsonplaceholder.typicode.com/posts';

    // Fetch data
    $response = wp_remote_get($api_url);

    // Check for errors
    if (is_wp_error($response)) {
        return "Failed to fetch data: " . $response->get_error_message();
    }

    // Decode JSON response
    $posts = json_decode(wp_remote_retrieve_body($response), true);

    // Prepare output
    $output = '<h3>Latest Posts from API:</h3><ul>';

    // Loop through posts and display titles
    foreach ($posts as $post) {
        $output .= '<li>' . esc_html($post['title']) . '</li>';
    }

    $output .= '</ul>';

    return $output;
}