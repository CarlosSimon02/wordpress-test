<?php
namespace SupafayaTickets;

use SupafayaTickets\Controllers\AuthController;
use SupafayaTickets\Controllers\EventController;
use SupafayaTickets\Controllers\TicketController;

class Plugin {
    private $auth_controller;
    private $event_controller;
    private $ticket_controller;
    
    public function init() {
        // Initialize controllers
        $this->auth_controller = new AuthController();
        $this->event_controller = new EventController();
        $this->ticket_controller = new TicketController();
        
        // Register assets
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        
        // Add admin menu
        add_action('admin_menu', [$this, 'add_admin_menu']);
        
        // Add settings
        add_action('admin_init', [$this, 'register_settings']);
    }
    
    public function register_assets() {
        // Always enqueue the stylesheet on all pages
        wp_enqueue_style(
            'supafaya-tickets-style',
            SUPAFAYA_PLUGIN_URL . 'assets/css/supafaya-tickets.css',
            [],
            SUPAFAYA_VERSION
        );
        
        // Register the script - fixed extension from .php to .js
        wp_register_script(
            'supafaya-tickets-script',
            SUPAFAYA_PLUGIN_URL . 'assets/js/supafaya-tickets.js',
            ['jquery'],
            SUPAFAYA_VERSION,
            true
        );
        
        wp_localize_script('supafaya-tickets-script', 'supafayaTickets', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('supafaya-tickets-nonce'),
            'pluginUrl' => SUPAFAYA_PLUGIN_URL 
        ]);
        
        // Enqueue the script whenever the shortcode is used
        add_action('wp_footer', [$this, 'maybe_enqueue_script']);
    }
    
    /**
     * Enqueue JavaScript if our shortcodes are used on the page
     */
    public function maybe_enqueue_script() {
        global $post;
        
        // Check if we need to load our assets
        $load_script = false;
        
        // If we have event_id in the URL
        if (isset($_GET['event_id'])) {
            $load_script = true;
        }
        
        // Check if the content has any of our shortcodes
        if ($post && (
            has_shortcode($post->post_content, 'supafaya_events') || 
            has_shortcode($post->post_content, 'supafaya_event') || 
            has_shortcode($post->post_content, 'supafaya_ticket_checkout') || 
            has_shortcode($post->post_content, 'supafaya_my_tickets') || 
            has_shortcode($post->post_content, 'supafaya_login_form')
        )) {
            $load_script = true;
        }
        
        if ($load_script) {
            wp_enqueue_script('supafaya-tickets-script');
        }
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Supafaya Tickets',
            'Supafaya Tickets',
            'manage_options',
            'supafaya-tickets',
            [$this, 'render_admin_page'],
            'dashicons-tickets',
            20
        );
        
        add_submenu_page(
            'supafaya-tickets',
            'Settings',
            'Settings',
            'manage_options',
            'supafaya-tickets-settings',
            [$this, 'render_settings_page']
        );
    }
    
    public function register_settings() {
        register_setting('supafaya_tickets_options', 'supafaya_api_url');
        register_setting('supafaya_tickets_options', 'supafaya_organization_id');
        register_setting('supafaya_tickets_options', 'supafaya_event_page_url');
        
        add_settings_section(
            'supafaya_tickets_main',
            'Main Settings',
            function() {
                echo '<p>Configure the connection to your Supafaya Ticketing API</p>';
            },
            'supafaya-tickets-settings'
        );
        
        add_settings_field(
            'supafaya_api_url',
            'API URL',
            function() {
                $value = get_option('supafaya_api_url', SUPAFAYA_API_URL);
                echo '<input type="text" name="supafaya_api_url" value="' . esc_attr($value) . '" class="regular-text">';
            },
            'supafaya-tickets-settings',
            'supafaya_tickets_main'
        );
        
        add_settings_field(
            'supafaya_organization_id',
            'Default Organization ID',
            function() {
                $value = get_option('supafaya_organization_id', '');
                echo '<input type="text" name="supafaya_organization_id" value="' . esc_attr($value) . '" class="regular-text">';
            },
            'supafaya-tickets-settings',
            'supafaya_tickets_main'
        );
        
        add_settings_field(
            'supafaya_event_page_url',
            'Event Details Page URL',
            function() {
                $value = get_option('supafaya_event_page_url', '');
                echo '<input type="text" name="supafaya_event_page_url" value="' . esc_attr($value) . '" class="regular-text">';
                echo '<p class="description">Enter the full URL of your event details page. This is where users will be directed when clicking "View Details".</p>';
            },
            'supafaya-tickets-settings',
            'supafaya_tickets_main'
        );
    }
    
    public function render_settings_page() {
        include SUPAFAYA_PLUGIN_DIR . 'templates/settings.php';
    }
}