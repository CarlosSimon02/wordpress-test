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
        
        // Add shortcode for login form
        add_shortcode('supafaya_firebase_login', [$this, 'firebase_login_shortcode']);
        
        // Enqueue Firebase scripts
        add_action('wp_enqueue_scripts', [$this, 'enqueue_firebase_scripts']);
    }
    
    /**
     * Enqueue Firebase scripts and styles
     */
    public function enqueue_firebase_scripts() {
        // Only enqueue on pages with our shortcode
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'supafaya_firebase_login')) {
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
            
            // Firebase UI
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
            
            // Our custom Firebase handler
            wp_enqueue_script(
                'supafaya-firebase',
                SUPAFAYA_PLUGIN_URL . 'assets/js/firebase-auth.js',
                ['jquery', 'firebaseui'],
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
                'isLoggedIn' => is_user_logged_in(),
                'redirectUrl' => $this->get_redirect_url()
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
        
        // Find or create a WordPress user
        $user_id = $this->get_or_create_user($user_data);
        
        if (is_wp_error($user_id)) {
            wp_send_json_error($user_id->get_error_message());
            exit;
        }
        
        // Store Supafaya tokens in user meta
        $this->auth_service->storeUserToken($user_id, $api_response);
        
        // Store Firebase UID for future reference
        update_user_meta($user_id, 'supafaya_firebase_uid', $user_data['uid']);
        
        // Log the user in
        wp_set_auth_cookie($user_id, true);
        
        wp_send_json_success([
            'message' => 'Authentication successful',
            'redirect' => $this->get_redirect_url()
        ]);
    }
    
    /**
     * Get existing user or create a new one
     */
    private function get_or_create_user($user_data) {
        // Check if user with this Firebase UID already exists
        $existing_users = get_users([
            'meta_key' => 'supafaya_firebase_uid',
            'meta_value' => $user_data['uid'],
            'number' => 1,
            'count_total' => false
        ]);
        
        if (!empty($existing_users)) {
            return $existing_users[0]->ID;
        }
        
        // Check if user with this email exists
        $user = get_user_by('email', $user_data['email']);
        
        if ($user) {
            // Add Firebase UID to existing user
            update_user_meta($user->ID, 'supafaya_firebase_uid', $user_data['uid']);
            return $user->ID;
        }
        
        // Create a new user
        $username = 'user_' . time();
        $password = wp_generate_password();
        $user_id = wp_create_user($username, $password, $user_data['email']);
        
        if (is_wp_error($user_id)) {
            return $user_id;
        }
        
        // Update user display name if available
        if (!empty($user_data['displayName'])) {
            wp_update_user([
                'ID' => $user_id,
                'display_name' => $user_data['displayName']
            ]);
        }
        
        return $user_id;
    }
} 