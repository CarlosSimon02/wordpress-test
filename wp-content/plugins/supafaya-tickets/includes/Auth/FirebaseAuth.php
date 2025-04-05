<?php
namespace SupafayaTickets\Auth;

use SupafayaTickets\Api\AuthService;
use SupafayaTickets\Api\ApiClient;

/**
 * Firebase Authentication Integration
 */
class FirebaseAuth {
    private $auth_service;
    private $api_client;
    
    public function __construct() {
        $this->api_client = new ApiClient();
        $this->auth_service = new AuthService($this->api_client);
        
        // Register AJAX handlers
        add_action('wp_ajax_supafaya_firebase_auth', [$this, 'handle_firebase_auth']);
        add_action('wp_ajax_nopriv_supafaya_firebase_auth', [$this, 'handle_firebase_auth']);
        
        // Add shortcode for login/logout forms
        add_shortcode('supafaya_firebase_login', [$this, 'firebase_login_shortcode']);
        add_shortcode('supafaya_firebase_logout', [$this, 'firebase_logout_shortcode']);
        
        // Enqueue Firebase scripts
        add_action('wp_enqueue_scripts', [$this, 'enqueue_firebase_scripts']);
        
        // Handle admin AJAX requests with Firebase token
        add_action('admin_init', [$this, 'setup_admin_ajax']);
    }
    
    /**
     * Setup authentication for admin AJAX requests
     */
    public function setup_admin_ajax() {
        // Only apply this for AJAX requests
        if (defined('DOING_AJAX') && DOING_AJAX) {
            add_filter('supafaya_api_token', [$this, 'get_firebase_token_from_request']);
        }
    }
    
    /**
     * Get Firebase token from AJAX request
     */
    public function get_firebase_token_from_request() {
        // Get token from request header
        $headers = getallheaders();
        if (isset($headers['X-Firebase-Token'])) {
            return $headers['X-Firebase-Token'];
        }
        
        // Get token from POST data
        if (isset($_POST['firebase_token'])) {
            return sanitize_text_field($_POST['firebase_token']);
        }
        
        // Get token from cookie
        if (isset($_COOKIE['firebase_user_token'])) {
            return sanitize_text_field($_COOKIE['firebase_user_token']);
        }
        
        return null;
    }
    
    /**
     * Firebase logout shortcode
     */
    public function firebase_logout_shortcode($atts) {
        ob_start();
        include SUPAFAYA_PLUGIN_DIR . 'templates/firebase-logout.php';
        return ob_get_clean();
    }
    
    /**
     * Enqueue Firebase scripts and styles
     */
    public function enqueue_firebase_scripts() {
        // Load Firebase scripts on more pages
        global $post;
        $should_load = false;
        
        // Check if we're on a page with the login shortcode
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'supafaya_firebase_login')) {
            $should_load = true;
        }
        
        // Also load on pages with checkout functionality
        if (isset($_GET['event_id'])) {
            $should_load = true;
        }
        
        // Load on any page with our other shortcodes
        if (is_a($post, 'WP_Post') && (
            has_shortcode($post->post_content, 'supafaya_events') || 
            has_shortcode($post->post_content, 'supafaya_event') || 
            has_shortcode($post->post_content, 'supafaya_ticket_checkout') || 
            has_shortcode($post->post_content, 'supafaya_my_tickets') ||
            has_shortcode($post->post_content, 'supafaya_firebase_logout')
        )) {
            $should_load = true;
        }
        
        if ($should_load) {
            // Firebase core
            wp_enqueue_script(
                'firebase-app',
                'https://www.gstatic.com/firebasejs/9.6.0/firebase-app-compat.js',
                [],
                null,
                true
            );
            
            // Firebase auth
            wp_enqueue_script(
                'firebase-auth',
                'https://www.gstatic.com/firebasejs/9.6.0/firebase-auth-compat.js',
                ['firebase-app'],
                null,
                true
            );
            
            // Firebase UI (only needed on login page)
            if (has_shortcode($post->post_content, 'supafaya_firebase_login')) {
                wp_enqueue_script(
                    'firebaseui',
                    'https://www.gstatic.com/firebasejs/ui/6.0.0/firebase-ui-auth.js',
                    ['firebase-auth'],
                    null,
                    true
                );
                
                // Firebase UI CSS
                wp_enqueue_style(
                    'firebaseui-css',
                    'https://www.gstatic.com/firebasejs/ui/6.0.0/firebase-ui-auth.css',
                    [],
                    null
                );
            }
            
            // Our custom Firebase handler
            wp_enqueue_script(
                'supafaya-firebase',
                SUPAFAYA_PLUGIN_URL . 'assets/js/firebase-auth.js',
                ['jquery', 'firebase-auth'],
                SUPAFAYA_VERSION,
                true
            );
            
            // Pass Firebase config to JavaScript
            wp_localize_script('supafaya-firebase', 'supafayaFirebase', [
                'apiKey' => get_option('supafaya_firebase_api_key', ''),
                'authDomain' => get_option('supafaya_firebase_auth_domain', ''),
                'projectId' => get_option('supafaya_firebase_project_id', ''),
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('supafaya-firebase-nonce'),
                'redirectUrl' => $this->get_redirect_url(),
                'siteUrl' => site_url()
            ]);
        }
    }
    
    /**
     * Get redirect URL from session or referrer
     */
    private function get_redirect_url() {
        $redirect = '';
        
        // Check session storage via cookie first
        if (isset($_COOKIE['supafaya_checkout_redirect'])) {
            $redirect = esc_url_raw($_COOKIE['supafaya_checkout_redirect']);
            // Clear the cookie
            setcookie('supafaya_checkout_redirect', '', time() - 3600, '/');
        }
        // Fallback to HTTP referrer
        else if (!empty($_SERVER['HTTP_REFERER'])) {
            $redirect = esc_url_raw($_SERVER['HTTP_REFERER']);
        }
        // Default to home
        else {
            $redirect = home_url();
        }
        
        return $redirect;
    }
    
    /**
     * Firebase login shortcode
     */
    public function firebase_login_shortcode($atts) {
        ob_start();
        include SUPAFAYA_PLUGIN_DIR . 'templates/firebase-login.php';
        return ob_get_clean();
    }
    
    /**
     * Handle Firebase auth AJAX request
     */
    public function handle_firebase_auth() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'supafaya-firebase-nonce')) {
            wp_send_json_error('Invalid nonce');
            exit;
        }
        
        // Get Firebase token and user data
        $token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';
        $user_data = isset($_POST['user']) ? $_POST['user'] : [];
        
        if (empty($token) || empty($user_data)) {
            wp_send_json_error('Missing token or user data');
            exit;
        }
        
        // Sanitize user data
        $user_data = [
            'uid' => sanitize_text_field($user_data['uid'] ?? ''),
            'email' => sanitize_email($user_data['email'] ?? ''),
            'displayName' => sanitize_text_field($user_data['displayName'] ?? ''),
            'photoURL' => esc_url_raw($user_data['photoURL'] ?? ''),
            'providerId' => sanitize_text_field($user_data['providerId'] ?? '')
        ];
        
        // Authenticate with Supafaya API using Firebase token
        $api_response = $this->auth_service->loginWithFirebase($token, $user_data);
        
        if (!$api_response || !isset($api_response['access_token'])) {
            wp_send_json_error('Failed to authenticate with API');
            exit;
        }
        
        // Store API tokens in cookie (encrypted)
        $this->store_api_token_in_cookie($api_response);
        
        wp_send_json_success([
            'message' => 'Authentication successful',
            'user' => [
                'email' => $user_data['email'],
                'name' => $user_data['displayName'],
                'photo' => $user_data['photoURL']
            ],
            'redirect' => $this->get_redirect_url()
        ]);
    }
    
    /**
     * Store API token in a secure cookie
     */
    private function store_api_token_in_cookie($token_data) {
        // Calculate expiry time
        $expires = isset($token_data['expires_in']) ? time() + $token_data['expires_in'] : time() + DAY_IN_SECONDS;
        
        // Store expiry time in token data
        $token_data['expires_at'] = $expires;
        
        // Encrypt token data for security
        $encrypted = $this->encrypt_data(json_encode($token_data));
        
        // Set secure cookie with token data
        $secure = is_ssl();
        $httponly = true;
        setcookie('supafaya_api_token', $encrypted, $expires, COOKIEPATH, COOKIE_DOMAIN, $secure, $httponly);
    }
    
    /**
     * Simple encryption function
     */
    private function encrypt_data($data) {
        $key = AUTH_KEY ?? 'supafaya-secure-key';
        return base64_encode(openssl_encrypt($data, 'AES-256-CBC', $key, 0, substr($key, 0, 16)));
    }
} 