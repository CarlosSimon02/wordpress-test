<?php
namespace SupafayaTickets\Controllers;

use SupafayaTickets\Api\ApiClient;
use SupafayaTickets\Api\AnalyticsService;

class AnalyticsController {
    protected $analytics_service;
    protected $api_client;
    
    public function __construct() {
        $this->api_client = new ApiClient();
        $this->analytics_service = new AnalyticsService($this->api_client);
        
        // Apply API token to all requests
        add_action('init', [$this, 'setup_api_token']);
        
        // Register REST API endpoints for analytics
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }
    
    /**
     * Set up API token from filter
     */
    public function setup_api_token() {
        $token = apply_filters('supafaya_api_token', null);
        if ($token) {
            $this->api_client->setToken($token);
        }
    }
    
    /**
     * Register REST API routes for analytics endpoints
     */
    public function register_rest_routes() {
        // Register REST route for analytics ping
        register_rest_route('supafaya/v1', '/analytics/ping', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_analytics_ping'],
            'permission_callback' => '__return_true'
        ]);
    }
    
    /**
     * REST API handler for analytics ping
     * 
     * @param \WP_REST_Request $request The request object
     * @return \WP_REST_Response The response object
     */
    public function rest_analytics_ping($request) {
        // Log the raw request for debugging
        error_log('[Supafaya Analytics Controller] Raw request: ' . print_r($request, true));
        
        // Get parameters from headers first (preferred method)
        $headers = $request->get_headers();
        
        // Extract header values (normalize header case)
        $reference_id = null;
        $reference_type = null;
        $user_id = null;
        $referer = null;
        
        // Headers can be mixed case, so we need to normalize
        foreach ($headers as $header => $value) {
            $header_lower = strtolower($header);
            
            // Check for headers in both formats (with hyphens and underscores)
            if ($header_lower === 'x_data_reference_id' || $header_lower === 'http_x_data_reference_id') {
                $reference_id = is_array($value) ? $value[0] : $value;
            } else if ($header_lower === 'x_data_reference_type' || $header_lower === 'http_x_data_reference_type') {
                $reference_type = is_array($value) ? $value[0] : $value;
            } else if ($header_lower === 'x_data_user_id' || $header_lower === 'http_x_data_user_id') {
                $user_id = is_array($value) ? $value[0] : $value;
            } else if ($header_lower === 'referer' || $header_lower === 'http_referer') {
                $referer = is_array($value) ? $value[0] : $value;
            }
        }
        
        // Fallback to query parameters if headers are not present
        if (empty($reference_id)) {
            $reference_id = $request->get_param('reference_id');
        }
        
        if (empty($reference_type)) {
            $reference_type = $request->get_param('reference_type');
        }
        
        if (empty($user_id)) {
            $user_id = $request->get_param('user_id');
        }
        
        // If referer isn't in the headers, try to get it from the request
        if (empty($referer)) {
            $referer = $request->get_param('referer') ?? $_SERVER['HTTP_REFERER'] ?? null;
        }
        
        // Debug logging
        error_log('[Supafaya Analytics Controller] Ping request - Headers: ' . json_encode($headers));
        error_log('[Supafaya Analytics Controller] Extracted values - Reference ID: ' . $reference_id . ', Type: ' . $reference_type . ', User ID: ' . $user_id . ', Referer: ' . $referer);
        
        // Validate required parameters
        if (empty($reference_id) || empty($reference_type)) {
            $error = new \WP_Error(
                'missing_params',
                'Missing required parameters: reference_id and reference_type are required (either in headers or query)',
                ['status' => 400]
            );
            error_log('[Supafaya Analytics Controller] Error: ' . print_r($error, true));
            return $error;
        }
        
        // Proxy the analytics ping to the API
        $result = $this->analytics_service->proxyPing([
            'reference_id' => $reference_id,
            'reference_type' => $reference_type,
            'user_id' => $user_id,
            'referer' => $referer
        ]);
        
        // Log the result
        error_log('[Supafaya Analytics Controller] API Result: ' . json_encode($result));
        
        // Return the result
        return rest_ensure_response($result);
    }
} 