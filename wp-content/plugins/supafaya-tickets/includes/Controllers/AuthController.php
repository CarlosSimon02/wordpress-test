<?php
namespace SupafayaTickets\Controllers;

use SupafayaTickets\Api\ApiClient;
use SupafayaTickets\Api\AuthService;

class AuthController {
    private $auth_service;
    private $api_client;
    
    public function __construct() {
        $this->api_client = new ApiClient();
        $this->auth_service = new AuthService($this->api_client);
        
        // Add actions for authentication
        add_action('wp_login', [$this, 'handle_login'], 10, 2);
        add_action('wp_logout', [$this, 'handle_logout']);
        add_action('user_register', [$this, 'handle_registration']);
        
        // Add AJAX handlers
        add_action('wp_ajax_supafaya_connect_account', [$this, 'connect_account']);
        add_action('wp_ajax_nopriv_supafaya_connect_account', [$this, 'connect_account']);
        
        // Add shortcodes
        add_shortcode('supafaya_login_form', [$this, 'login_form_shortcode']);
        
        // Filter to set API token when making requests
        add_filter('supafaya_api_token', [$this, 'get_current_user_token']);
    }
    
    /**
     * Handle WordPress login
     */
    public function handle_login($user_login, $user) {
        // Check if user has Supafaya credentials stored
        $supafaya_credentials = get_user_meta($user->ID, 'supafaya_credentials', true);
        
        if ($supafaya_credentials) {
            // Try to login to Supafaya with stored credentials
            $token_data = $this->auth_service->login(
                $supafaya_credentials['email'],
                $supafaya_credentials['password']
            );
            
            if ($token_data) {
                $this->auth_service->storeUserToken($user->ID, $token_data);
            }
        }
    }
    
    /**
     * Handle WordPress logout
     */
    public function handle_logout() {
        $user = wp_get_current_user();
        if ($user->ID > 0) {
            $this->auth_service->deleteUserToken($user->ID);
        }
    }
    
    /**
     * Handle WordPress registration
     */
    public function handle_registration($user_id) {
        // Registration logic
        // This would be called when a new WP user is registered
    }
    
    /**
     * AJAX handler to connect WordPress account with Supafaya
     */
    public function connect_account() {
        if (!isset($_POST['email']) || !isset($_POST['password'])) {
            wp_send_json([
                'success' => false,
                'message' => 'Missing required fields'
            ]);
            return;
        }
        
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        
        $token_data = $this->auth_service->login($email, $password);
        
        if (!$token_data) {
            wp_send_json([
                'success' => false,
                'message' => 'Invalid credentials'
            ]);
            return;
        }
        
        $user = wp_get_current_user();
        
        if ($user->ID > 0) {
            // Store token
            $this->auth_service->storeUserToken($user->ID, $token_data);
            
            // Store credentials (encrypted)
            update_user_meta($user->ID, 'supafaya_credentials', [
                'email' => $email,
                'password' => wp_hash_password($password)
            ]);
            
            wp_send_json([
                'success' => true,
                'message' => 'Account connected successfully'
            ]);
        } else {
            wp_send_json([
                'success' => false,
                'message' => 'User not logged in'
            ]);
        }
    }
    
    /**
     * Get token for current user
     */
    public function get_current_user_token() {
        $user = wp_get_current_user();
        
        if ($user->ID > 0) {
            $token_data = $this->auth_service->getUserToken($user->ID);
            
            if ($token_data) {
                $validated_token = $this->auth_service->validateToken($token_data);
                
                if ($validated_token) {
                    // Update if token was refreshed
                    if ($validated_token !== $token_data) {
                        $this->auth_service->storeUserToken($user->ID, $validated_token);
                    }
                    
                    return $validated_token['access_token'];
                }
            }
        }
        
        return null;
    }
    
    /**
     * Login form shortcode
     */
    public function login_form_shortcode($atts) {
        ob_start();
        include SUPAFAYA_PLUGIN_DIR . 'templates/login-form.php';
        return ob_get_clean();
    }
}