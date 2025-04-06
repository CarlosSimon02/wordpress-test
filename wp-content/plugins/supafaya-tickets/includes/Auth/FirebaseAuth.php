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
        
        // Add shortcodes
        add_shortcode('supafaya_firebase_login', [$this, 'firebase_login_shortcode']);
        add_shortcode('supafaya_firebase_logout', [$this, 'firebase_logout_shortcode']);
        add_shortcode('supafaya_user_dropdown', [$this, 'user_dropdown_shortcode']);
        
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
        // Load Firebase scripts on appropriate pages
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
            has_shortcode($post->post_content, 'supafaya_firebase_logout') ||
            has_shortcode($post->post_content, 'supafaya_user_dropdown')
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
            
            // FirebaseUI (only needed on login page)
            if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'supafaya_firebase_login')) {
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
            
            // Get profile URL
            $profile_url = get_option('supafaya_profile_page_url', home_url());
            
            // Pass Firebase config to JavaScript
            wp_localize_script('supafaya-firebase', 'supafayaFirebase', [
                'apiKey' => get_option('supafaya_firebase_api_key', ''),
                'authDomain' => get_option('supafaya_firebase_auth_domain', ''),
                'projectId' => get_option('supafaya_firebase_project_id', ''),
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('supafaya-firebase-nonce'),
                'redirectUrl' => $this->get_redirect_url(),
                'profileUrl' => $profile_url,
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
     * User dropdown shortcode
     */
    public function user_dropdown_shortcode($atts) {
        $atts = shortcode_atts([
            'button_text' => 'Log In',
            'button_class' => 'supafaya-login-button',
        ], $atts);
        
        // Force-load Firebase scripts when the shortcode is used
        $this->force_load_firebase_scripts();
        
        ob_start();
        include SUPAFAYA_PLUGIN_DIR . 'templates/user-dropdown.php';
        return ob_get_clean();
    }
    
    /**
     * Force load Firebase scripts on demand
     */
    private function force_load_firebase_scripts() {
        // Firebase core
        wp_enqueue_script(
            'firebase-app',
            'https://www.gstatic.com/firebasejs/9.6.0/firebase-app-compat.js',
            [],
            null,
            false // Load in header
        );
        
        // Firebase auth
        wp_enqueue_script(
            'firebase-auth',
            'https://www.gstatic.com/firebasejs/9.6.0/firebase-auth-compat.js',
            ['firebase-app'],
            null,
            false // Load in header
        );
        
        // Our custom Firebase handler
        wp_enqueue_script(
            'supafaya-firebase',
            SUPAFAYA_PLUGIN_URL . 'assets/js/firebase-auth.js',
            ['jquery', 'firebase-auth'],
            SUPAFAYA_VERSION,
            false // Load in header
        );
        
        // Get profile URL
        $profile_url = get_option('supafaya_profile_page_url', home_url());
        
        // Pass Firebase config to JavaScript
        $firebase_config = [
            'apiKey' => get_option('supafaya_firebase_api_key', ''),
            'authDomain' => get_option('supafaya_firebase_auth_domain', ''),
            'projectId' => get_option('supafaya_firebase_project_id', ''),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('supafaya-firebase-nonce'),
            'redirectUrl' => $this->get_redirect_url(),
            'profileUrl' => $profile_url,
            'siteUrl' => site_url()
        ];
        
        wp_localize_script('supafaya-firebase', 'supafayaFirebase', $firebase_config);
    }
    
    /**
     * Handle Firebase auth AJAX request
     */
    public function handle_firebase_auth() {
        $this->log_debug("Starting Firebase authentication handling");
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'supafaya-firebase-nonce')) {
            $this->log_debug("Invalid nonce", $_POST['nonce'] ?? 'not set', 'error');
            wp_send_json_error('Invalid nonce');
            exit;
        }
        
        // Get Firebase token and user data
        $token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';
        $user_data = isset($_POST['user']) ? $_POST['user'] : [];
        
        if (empty($token)) {
            $this->log_debug("Missing token", null, 'error');
            wp_send_json_error('Missing token');
            exit;
        }
        
        if (empty($user_data)) {
            $this->log_debug("Missing user data", null, 'error');
            wp_send_json_error('Missing user data');
            exit;
        }
        
        $this->log_debug("Received user data from frontend", [
            'uid' => $user_data['uid'] ?? 'not set',
            'email' => $user_data['email'] ?? 'not set',
            'displayName' => $user_data['displayName'] ?? 'not set',
            'hasPhotoURL' => isset($user_data['photoURL']),
            'providerId' => $user_data['providerId'] ?? 'not set'
        ]);
        
        // Sanitize user data
        $user_data = [
            'uid' => sanitize_text_field($user_data['uid'] ?? ''),
            'email' => sanitize_email($user_data['email'] ?? ''),
            'displayName' => sanitize_text_field($user_data['displayName'] ?? ''),
            'photoURL' => esc_url_raw($user_data['photoURL'] ?? ''),
            'providerId' => sanitize_text_field($user_data['providerId'] ?? '')
        ];
        
        $this->log_debug("Sanitized user data", $user_data);
        
        // Authenticate with Supafaya API using Firebase token
        $this->log_debug("Authenticating with Supafaya API using Firebase token");
        $api_response = $this->auth_service->loginWithFirebase($token, $user_data);
        
        // Even if API authentication fails, proceed with creating the user in Firestore directly
        $this->log_debug("Proceeding to create/check Firestore user directly with Firebase token");
        
        // Try to create user in Firestore directly using the Firebase token
        $this->log_debug("Attempting to create Firestore user directly");
        
        // Create or update user in Firestore
        $user_created = $this->create_or_check_firestore_user_directly($token, $user_data);
        
        // Store token in cookie (even if it's temporary)
        if ($api_response) {
            $this->log_debug("Storing API tokens in cookie");
            $this->store_api_token_in_cookie($api_response);
        }
        
        $this->log_debug("Authentication process completed");
        wp_send_json_success([
            'message' => 'Authentication successful',
            'user' => [
                'email' => $user_data['email'],
                'name' => $user_data['displayName'],
                'photo' => $user_data['photoURL']
            ],
            'redirect' => $this->get_redirect_url(),
            'user_created' => $user_created
        ]);
    }
    
    /**
     * Create or check for user in Firestore directly
     */
    private function create_or_check_firestore_user_directly($token, $user_data) {
        $this->log_debug("Attempting direct Firestore user creation/check");
        
        // Try to make a direct request to Firebase using the REST API
        $firebase_project_id = get_option('supafaya_firebase_project_id', '');
        if (empty($firebase_project_id)) {
            $this->log_debug("Missing Firebase project ID", null, 'error');
            return false;
        }
        
        // Build Firebase Firestore REST API URL
        $firestore_url = "https://firestore.googleapis.com/v1/projects/{$firebase_project_id}/databases/(default)/documents/users/{$user_data['uid']}";
        $this->log_debug("Firestore URL", $firestore_url);
        
        // First, check if user exists
        $args = [
            'method' => 'GET',
            'timeout' => 30,
            'redirection' => 5,
            'httpversion' => '1.1',
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ],
            'sslverify' => false
        ];
        
        $this->log_debug("Checking if user exists in Firestore");
        $response = wp_remote_get($firestore_url, $args);
        
        $user_exists = false;
        
        if (!is_wp_error($response)) {
            $status_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            
            $this->log_debug("User check response status", $status_code);
            $this->log_debug("User check response body", $response_body);
            
            $user_exists = ($status_code === 200);
        }
        
        if ($user_exists) {
            $this->log_debug("User already exists in Firestore", $user_data['uid']);
            return true;
        }
        
        // User doesn't exist, create it
        $this->log_debug("User doesn't exist, creating now");
        
        // Current timestamp
        $timestamp = date('c');
        
        // Prepare user data in Firestore document format
        $firestore_doc = [
            'fields' => [
                'id' => ['stringValue' => $user_data['uid']],
                'email' => ['stringValue' => $user_data['email']],
                'name' => ['stringValue' => $user_data['displayName'] ?? ''],
                'photoUrl' => ['stringValue' => $user_data['photoURL'] ?? ''],
                'isGettingStarted' => ['booleanValue' => false],
                'interests' => ['nullValue' => null],
                'birthday' => ['nullValue' => null],
                'createdAt' => ['stringValue' => $timestamp],
                'updatedAt' => ['stringValue' => $timestamp]
            ]
        ];
        
        $args = [
            'method' => 'POST',
            'timeout' => 30,
            'redirection' => 5,
            'httpversion' => '1.1',
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ],
            'body' => json_encode($firestore_doc),
            'sslverify' => false
        ];
        
        // Use the parent collection URL for creation
        $parent_url = "https://firestore.googleapis.com/v1/projects/{$firebase_project_id}/databases/(default)/documents/users?documentId={$user_data['uid']}";
        
        $this->log_debug("Creating user document with URL", $parent_url);
        $response = wp_remote_post($parent_url, $args);
        
        if (is_wp_error($response)) {
            $this->log_debug("Error creating user document", $response->get_error_message(), 'error');
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        $this->log_debug("User creation response status", $status_code);
        $this->log_debug("User creation response body", $response_body);
        
        return ($status_code >= 200 && $status_code < 300);
    }
    
    /**
     * Store API token in a secure cookie
     */
    private function store_api_token_in_cookie($token_data) {
        $this->log_debug("Storing API token in cookie", [
            'has_access_token' => isset($token_data['access_token']),
            'has_refresh_token' => isset($token_data['refresh_token']),
            'has_expires_in' => isset($token_data['expires_in'])
        ]);
        
        // Calculate expiry time
        $expires = isset($token_data['expires_in']) ? time() + $token_data['expires_in'] : time() + DAY_IN_SECONDS;
        
        // Store expiry time in token data
        $token_data['expires_at'] = $expires;
        
        // Encrypt token data for security
        $encrypted = $this->encrypt_data(json_encode($token_data));
        
        // Set secure cookie with token data
        $secure = is_ssl();
        $httponly = true;
        $cookie_set = setcookie('supafaya_api_token', $encrypted, $expires, COOKIEPATH, COOKIE_DOMAIN, $secure, $httponly);
        
        $this->log_debug("Cookie set result", $cookie_set ? "success" : "failed");
    }
    
    /**
     * Simple encryption function
     */
    private function encrypt_data($data) {
        $key = AUTH_KEY ?? 'supafaya-secure-key';
        return base64_encode(openssl_encrypt($data, 'AES-256-CBC', $key, 0, substr($key, 0, 16)));
    }
    
    /**
     * Enhanced logging function
     * 
     * @param string $message Log message
     * @param mixed $data Optional data to include
     * @param string $level Log level (info, warning, error)
     */
    private function log_debug($message, $data = null, $level = 'info') {
        $log_prefix = '[Supafaya Firebase Debug]';
        $timestamp = date('Y-m-d H:i:s');
        
        // Format the log message
        $log_message = "$log_prefix [$timestamp] [$level] $message";
        
        // Add data if provided
        if ($data !== null) {
            // For sensitive data like tokens, show only part
            if (is_string($data) && strlen($data) > 50 && (strpos(strtolower($message), 'token') !== false)) {
                $partial_data = substr($data, 0, 20) . '...[truncated]...';
                $log_message .= " Data: " . print_r($partial_data, true);
            } else {
                $log_message .= " Data: " . print_r($data, true);
            }
        }
        
        // Write to WordPress debug log
        error_log($log_message);
    }
} 