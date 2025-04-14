<?php
namespace SupafayaTickets;

use SupafayaTickets\Controllers\EventController;
use SupafayaTickets\Controllers\TicketController;
use SupafayaTickets\Controllers\PaymentProofController;
use SupafayaTickets\Controllers\AnalyticsController;
use SupafayaTickets\Auth\FirebaseAuth;

class Plugin {
    private $event_controller;
    private $ticket_controller;
    private $payment_proof_controller;
    private $analytics_controller;
    private $firebase_auth;
    
    public function init() {
        $this->event_controller = new EventController();
        $this->ticket_controller = new TicketController();
        $this->payment_proof_controller = new PaymentProofController();
        $this->analytics_controller = new AnalyticsController();
        
        $this->firebase_auth = new FirebaseAuth();
        
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        
        add_shortcode('supafaya_events', array($this, 'events_shortcode'));
        add_shortcode('supafaya_event', array($this, 'event_shortcode'));
        add_shortcode('supafaya_login_form', array($this, 'login_form_shortcode'));
        add_shortcode('supafaya_user_dropdown', array($this, 'user_dropdown_shortcode'));
        add_shortcode('supafaya_payment_result', array($this, 'payment_result_shortcode'));
        add_shortcode('supafaya_payment_history', array($this, 'payment_history_shortcode'));
        
        add_action('wp_ajax_supafaya_get_events', array($this->event_controller, 'ajax_get_events'));
        add_action('wp_ajax_nopriv_supafaya_get_events', array($this->event_controller, 'ajax_get_events'));
        
        add_action('wp_ajax_supafaya_get_event', array($this->event_controller, 'ajax_get_event'));
        add_action('wp_ajax_nopriv_supafaya_get_event', array($this->event_controller, 'ajax_get_event'));

        add_action('wp_ajax_supafaya_purchase_ticket', array($this->event_controller, 'ajax_purchase_ticket'));
        add_action('wp_ajax_nopriv_supafaya_purchase_ticket', array($this->event_controller, 'ajax_purchase_ticket'));

        add_action('wp_ajax_supafaya_get_user_items', array($this->event_controller, 'ajax_get_user_items'));
        add_action('wp_ajax_nopriv_supafaya_get_user_items', array($this->event_controller, 'ajax_get_user_items'));
        
        add_action('wp_ajax_supafaya_proof_of_payment', array($this->payment_proof_controller, 'ajax_submit_proof_of_payment'));
        add_action('wp_ajax_nopriv_supafaya_proof_of_payment', array($this->payment_proof_controller, 'ajax_submit_proof_of_payment'));
    }
    
    public function register_assets() {
        wp_enqueue_style(
            'supafaya-tickets-style',
            SUPAFAYA_PLUGIN_URL . 'assets/css/supafaya-tickets.css',
            [],
            SUPAFAYA_VERSION
        );
        
        wp_register_style(
            'supafaya-proof-of-payment-style',
            SUPAFAYA_PLUGIN_URL . 'assets/css/proof-of-payment.css',
            ['supafaya-tickets-style'],
            SUPAFAYA_VERSION
        );
        
        wp_register_script(
            'supafaya-tickets-script',
            SUPAFAYA_PLUGIN_URL . 'assets/js/supafaya-tickets.js',
            ['jquery'],
            SUPAFAYA_VERSION,
            true
        );
        
        wp_register_script(
            'supafaya-analytics',
            SUPAFAYA_PLUGIN_URL . 'assets/js/supafaya-analytics.js',
            ['jquery', 'supafaya-tickets-script'],
            SUPAFAYA_VERSION,
            true
        );
        
        wp_register_script(
            'supafaya-purchased-items',
            SUPAFAYA_PLUGIN_URL . 'assets/js/purchased-items.js',
            ['jquery', 'supafaya-tickets-script'],
            SUPAFAYA_VERSION,
            true
        );
        
        wp_register_script(
            'supafaya-proof-of-payment',
            SUPAFAYA_PLUGIN_URL . 'assets/js/proof-of-payment.js',
            ['jquery', 'supafaya-tickets-script'],
            SUPAFAYA_VERSION,
            true
        );
        
        $login_url = get_option('supafaya_login_page_url', '');
        
        if (empty($login_url)) {
            $login_pages = get_posts([
                'post_type' => 'page',
                'posts_per_page' => 1,
                's' => '[supafaya_firebase_login]'
            ]);
            
            if (!empty($login_pages)) {
                $login_url = get_permalink($login_pages[0]->ID);
            } else {
                $login_url = home_url();
            }
        }
        
        $profile_url = get_option('supafaya_profile_page_url', home_url());
        
        $payment_result_url = get_option('supafaya_payment_result_page_url', '');
        if (empty($payment_result_url)) {
            $result_pages = get_posts([
                'post_type' => 'page',
                'posts_per_page' => 1,
                's' => '[supafaya_payment_result]'
            ]);
            
            if (!empty($result_pages)) {
                $payment_result_url = get_permalink($result_pages[0]->ID);
            } else {
                $payment_result_url = home_url();
            }
        }
        
        $default_organization_id = get_option('supafaya_organization_id', '');
        
        $rest_url = rest_url('supafaya/v1/');
        
        wp_localize_script('supafaya-tickets-script', 'supafayaTickets', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('supafaya-tickets-nonce'),
            'pluginUrl' => SUPAFAYA_PLUGIN_URL,
            'loginUrl' => $login_url,
            'profileUrl' => $profile_url,
            'paymentResultUrl' => $payment_result_url,
            'restUrl' => $rest_url,
            'defaultOrganizationId' => $default_organization_id
        ]);
        
        add_action('wp_footer', [$this, 'maybe_enqueue_script']);
    }
    
    public function maybe_enqueue_script() {
        global $post;
        
        $load_script = false;
        
        if (isset($_GET['event_id'])) {
            $load_script = true;
        }
        
        if ($post && (
            has_shortcode($post->post_content, 'supafaya_events') || 
            has_shortcode($post->post_content, 'supafaya_event') || 
            has_shortcode($post->post_content, 'supafaya_ticket_checkout') || 
            has_shortcode($post->post_content, 'supafaya_my_tickets') ||
            has_shortcode($post->post_content, 'supafaya_firebase_login') ||
            has_shortcode($post->post_content, 'supafaya_firebase_logout') ||
            has_shortcode($post->post_content, 'supafaya_user_dropdown') ||
            has_shortcode($post->post_content, 'supafaya_payment_result') ||
            has_shortcode($post->post_content, 'supafaya_payment_history')
        )) {
            $load_script = true;
        }
        
        if ($load_script) {
            if (method_exists($this->firebase_auth, 'force_load_firebase_scripts')) {
                $this->firebase_auth->force_load_firebase_scripts();
            }
            
            $depends = ['jquery'];
            
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
            
            wp_enqueue_script('supafaya-analytics');
            
            if (isset($_GET['event_id'])) {
                wp_enqueue_script(
                    'supafaya-purchased-items',
                    SUPAFAYA_PLUGIN_URL . 'assets/js/purchased-items.js',
                    ['jquery', 'supafaya-tickets-script'],
                    SUPAFAYA_VERSION,
                    true
                );
                
                wp_enqueue_script(
                    'supafaya-proof-of-payment',
                    SUPAFAYA_PLUGIN_URL . 'assets/js/proof-of-payment.js',
                    ['jquery', 'supafaya-tickets-script'],
                    SUPAFAYA_VERSION,
                    true
                );
                
                wp_enqueue_style('supafaya-proof-of-payment-style');
            }
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
        register_setting('supafaya_tickets_options', 'supafaya_payment_result_page_url');
        
        // Firebase settings
        register_setting('supafaya_tickets_options', 'supafaya_firebase_api_key');
        register_setting('supafaya_tickets_options', 'supafaya_firebase_auth_domain');
        register_setting('supafaya_tickets_options', 'supafaya_firebase_project_id');
        register_setting('supafaya_tickets_options', 'supafaya_firebase_storage_bucket');
        
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
        
        // Add payment result page URL field
        add_settings_field(
            'supafaya_payment_result_page_url',
            'Payment Result Page URL',
            function() {
                $value = get_option('supafaya_payment_result_page_url', '');
                echo '<input type="text" name="supafaya_payment_result_page_url" value="' . esc_attr($value) . '" class="regular-text">';
                echo '<p class="description">URL where users will be redirected after payment (success/failure). Status will be added as query parameter.</p>';
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
        
        add_settings_field(
            'supafaya_firebase_storage_bucket',
            'Firebase Storage Bucket',
            function() {
                $value = get_option('supafaya_firebase_storage_bucket', '');
                echo '<input type="text" name="supafaya_firebase_storage_bucket" value="' . esc_attr($value) . '" class="regular-text">';
            },
            'supafaya-tickets-settings',
            'supafaya_tickets_firebase'
        );
    }
    
    public function render_settings_page() {
        include SUPAFAYA_PLUGIN_DIR . 'templates/settings.php';
    }
    
    /**
     * Events shortcode
     */
    public function events_shortcode($atts) {
        return $this->event_controller->events_shortcode($atts);
    }
    
    /**
     * Event shortcode
     */
    public function event_shortcode($atts) {
        return $this->event_controller->event_shortcode($atts);
    }
    
    /**
     * Login form shortcode
     */
    public function login_form_shortcode($atts) {
        if (method_exists($this->firebase_auth, 'firebase_login_shortcode')) {
            return $this->firebase_auth->firebase_login_shortcode($atts);
        }
        return '<p>Login form shortcode not available</p>';
    }
    
    /**
     * User dropdown shortcode
     */
    public function user_dropdown_shortcode($atts) {
        if (method_exists($this->firebase_auth, 'user_dropdown_shortcode')) {
            return $this->firebase_auth->user_dropdown_shortcode($atts);
        }
        return '<p>User dropdown shortcode not available</p>';
    }
    
    /**
     * Payment result shortcode
     */
    public function payment_result_shortcode($atts, $content = null) {
        // Extract shortcode attributes
        $atts = shortcode_atts([
            'status' => '',
            'transaction_id' => '',
            'event_id' => ''
        ], $atts);
        
        // Load the template
        ob_start();
        include SUPAFAYA_PLUGIN_DIR . 'templates/payment-result-default.php';
        return ob_get_clean();
    }
    
    /**
     * Payment history shortcode
     */
    public function payment_history_shortcode($atts) {
        if (isset($this->ticket_controller) && method_exists($this->ticket_controller, 'payment_history_shortcode')) {
            return $this->ticket_controller->payment_history_shortcode($atts);
        }
        
        return '<p>Payment history feature is not available.</p>';
    }
}