<?php
namespace SupafayaTickets\Api;

class AuthService {
    private $api_client;
    private $user_meta_key = 'supafaya_auth_token';
    
    public function __construct(ApiClient $api_client) {
        $this->api_client = $api_client;
    }
    
    /**
     * Login user to Supafaya API
     */
    public function login($email, $password) {
        $response = $this->api_client->post('/auth/login', [
            'email' => $email,
            'password' => $password
        ]);
        
        if ($response['success'] && isset($response['data']['access_token'])) {
            return $response['data'];
        }
        
        return false;
    }
    
    /**
     * Register new user in Supafaya API
     */
    public function register($user_data) {
        $response = $this->api_client->post('/auth/register', $user_data);
        return $response;
    }
    
    /**
     * Store Supafaya token in user meta
     */
    public function storeUserToken($user_id, $token_data) {
        return update_user_meta($user_id, $this->user_meta_key, $token_data);
    }
    
    /**
     * Get stored token for user
     */
    public function getUserToken($user_id) {
        return get_user_meta($user_id, $this->user_meta_key, true);
    }
    
    /**
     * Delete stored token
     */
    public function deleteUserToken($user_id) {
        return delete_user_meta($user_id, $this->user_meta_key);
    }
    
    /**
     * Check if token is valid and refresh if needed
     */
    public function validateToken($token_data) {
        // Check if token is expired
        if (isset($token_data['expires_at']) && time() > $token_data['expires_at']) {
            // Try to refresh
            if (isset($token_data['refresh_token'])) {
                $refresh_result = $this->refreshToken($token_data['refresh_token']);
                if ($refresh_result) {
                    return $refresh_result;
                }
            }
            return false;
        }
        
        return $token_data;
    }
    
    /**
     * Refresh token
     */
    public function refreshToken($refresh_token) {
        $response = $this->api_client->post('/auth/refresh', [
            'refresh_token' => $refresh_token
        ]);
        
        if ($response['success'] && isset($response['data']['access_token'])) {
            return $response['data'];
        }
        
        return false;
    }
}