<?php
namespace SupafayaTickets;

use SupafayaTickets\Controllers\EventController;
use SupafayaTickets\Controllers\TicketController;
use SupafayaTickets\Auth\FirebaseAuth;

class Plugin {
    private $event_controller;
    private $ticket_controller;
    private $firebase_auth;
    
    public function init() {
        // Initialize controllers
        $this->event_controller = new EventController();
        $this->ticket_controller = new TicketController();
        
        // Initialize Firebase Auth
        $this->firebase_auth = new FirebaseAuth();
        
        // Register assets
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        
        // Add admin menu
        add_action('admin_menu', [$this, 'add_admin_menu']);
        
        // Add settings
        add_action('admin_init', [$this, 'register_settings']);
        
        // Register shortcodes
        add_shortcode('supafaya_firebase_login', [$this->firebase_auth, 'firebase_login_shortcode']);
        add_shortcode('supafaya_firebase_logout', [$this->firebase_auth, 'firebase_logout_shortcode']);
        
        // Set up AJAX for all requests
        add_action('wp_ajax_nopriv_supafaya_purchase_ticket', [$this->ticket_controller, 'ajax_purchase_ticket']);
        add_action('wp_ajax_supafaya_purchase_ticket', [$this->ticket_controller, 'ajax_purchase_ticket']);
    }
    
    public function register_assets() {
        // Always enqueue the stylesheet on all pages
        wp_enqueue_style(
            'supafaya-tickets-style',
            SUPAFAYA_PLUGIN_URL . 'assets/css/supafaya-tickets.css',
            [],
            SUPAFAYA_VERSION
        );
        
        // Register the script
        wp_register_script(
            'supafaya-tickets-script',
            SUPAFAYA_PLUGIN_URL . 'assets/js/supafaya-tickets.js',
            ['jquery'],
            SUPAFAYA_VERSION,
            true
        );
        
        // Get login URL - first check if it's configured in settings
        $login_url = get_option('supafaya_login_page_url', '');
        
        // If not set in settings, look for a page with our login shortcode
        if (empty($login_url)) {
            $login_pages = get_posts([
                'post_type' => 'page',
                'posts_per_page' => 1,
                's' => '[supafaya_firebase_login]'
            ]);
            
            if (!empty($login_pages)) {
                $login_url = get_permalink($login_pages[0]->ID);
            } else {
                // Fallback to home page
                $login_url = home_url();
            }
        }
        
        // Get profile URL
        $profile_url = get_option('supafaya_profile_page_url', home_url());
        
        wp_localize_script('supafaya-tickets-script', 'supafayaTickets', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('supafaya-tickets-nonce'),
            'pluginUrl' => SUPAFAYA_PLUGIN_URL,
            'loginUrl' => $login_url,
            'profileUrl' => $profile_url
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
            has_shortcode($post->post_content, 'supafaya_firebase_login') ||
            has_shortcode($post->post_content, 'supafaya_firebase_logout') ||
            has_shortcode($post->post_content, 'supafaya_user_dropdown')
        )) {
            $load_script = true;
        }
        
        if ($load_script) {
            // Make sure supafaya-tickets.js depends on firebase
            $depends = ['jquery'];
            
            // Add supafaya-firebase as a dependency if it's been enqueued
            if (wp_script_is('supafaya-firebase', 'registered')) {
                $depends[] = 'supafaya-firebase';
            }
            
            wp_enqueue_script(
                'supafaya-tickets-script',
                SUPAFAYA_PLUGIN_URL . 'assets/js/supafaya-tickets.js',
                $depends,
                SUPAFAYA_VERSION,
                true
            );
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
        register_setting('supafaya_tickets_options', 'supafaya_login_page_url');
        register_setting('supafaya_tickets_options', 'supafaya_profile_page_url');
        
        // Firebase settings
        register_setting('supafaya_tickets_options', 'supafaya_firebase_api_key');
        register_setting('supafaya_tickets_options', 'supafaya_firebase_auth_domain');
        register_setting('supafaya_tickets_options', 'supafaya_firebase_project_id');
        
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
            'supafaya_login_page_url',
            'Login Page URL',
            function() {
                $value = get_option('supafaya_login_page_url', '');
                echo '<input type="text" name="supafaya_login_page_url" value="' . esc_attr($value) . '" class="regular-text">';
                echo '<p class="description">Enter the full URL of your login page (with Firebase auth). Leave empty to auto-detect.</p>';
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
        
        // Add the profile page URL field after the login page URL field
        add_settings_field(
            'supafaya_profile_page_url',
            'Profile Page URL',
            function() {
                $value = get_option('supafaya_profile_page_url', '');
                echo '<input type="text" name="supafaya_profile_page_url" value="' . esc_attr($value) . '" class="regular-text">';
                echo '<p class="description">Enter the full URL of your user profile page. Leave empty to use the home page.</p>';
            },
            'supafaya-tickets-settings',
            'supafaya_tickets_main'
        );
        
        // Add Firebase settings section
        add_settings_section(
            'supafaya_tickets_firebase',
            'Firebase Authentication Settings',
            function() {
                echo '<p>Configure Firebase authentication settings. Get these values from your <a href="https://console.firebase.google.com/" target="_blank">Firebase Console</a>.</p>';
            },
            'supafaya-tickets-settings'
        );
        
        add_settings_field(
            'supafaya_firebase_api_key',
            'Firebase API Key',
            function() {
                $value = get_option('supafaya_firebase_api_key', '');
                echo '<input type="text" name="supafaya_firebase_api_key" value="' . esc_attr($value) . '" class="regular-text">';
            },
            'supafaya-tickets-settings',
            'supafaya_tickets_firebase'
        );
        
        add_settings_field(
            'supafaya_firebase_auth_domain',
            'Firebase Auth Domain',
            function() {
                $value = get_option('supafaya_firebase_auth_domain', '');
                echo '<input type="text" name="supafaya_firebase_auth_domain" value="' . esc_attr($value) . '" class="regular-text">';
                echo '<p class="description">Example: your-app.firebaseapp.com</p>';
            },
            'supafaya-tickets-settings',
            'supafaya_tickets_firebase'
        );
        
        add_settings_field(
            'supafaya_firebase_project_id',
            'Firebase Project ID',
            function() {
                $value = get_option('supafaya_firebase_project_id', '');
                echo '<input type="text" name="supafaya_firebase_project_id" value="' . esc_attr($value) . '" class="regular-text">';
            },
            'supafaya-tickets-settings',
            'supafaya_tickets_firebase'
        );
    }
    
    public function render_settings_page() {
        include SUPAFAYA_PLUGIN_DIR . 'templates/settings.php';
    }
}