<?php
namespace SupafayaTickets\Api;

class ApiClient {
    private $api_url;
    private $token = null;
    
    public function __construct() {
        $this->api_url = SUPAFAYA_API_URL;
    }
    
    public function setToken($token) {
        $this->token = $token;
    }
    
    public function request($endpoint, $method = 'GET', $data = null, $headers = []) {
        $url = $this->api_url . $endpoint;
        error_log('[Supafaya API Debug] Making request to: ' . $url);
        error_log('[Supafaya API Debug] Method: ' . $method);
        
        // Initialize headers as associative array
        $default_headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];
        
        // Only add token if it exists and is not empty
        if (!empty($this->token)) {
            $default_headers['x-access-token'] = (string)$this->token;
            error_log('[Supafaya API Debug] Token included: ' . substr($this->token, 0, 10) . '...'); 
        } else {
            error_log('[Supafaya API Debug] No token included in this request');
        }
        
        // Merge headers
        $headers = array_merge($default_headers, $headers);
        error_log('[Supafaya API Debug] Request headers: ' . print_r($headers, true));
        
        $args = [
            'method' => $method,
            'timeout' => 30,
            'redirection' => 5,
            'httpversion' => '1.1',
            'headers' => $headers,
            'sslverify' => false,
            'blocking' => true
        ];
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $args['body'] = json_encode($data);
            error_log('[Supafaya API Debug] Request body: ' . print_r($data, true));
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log('[Supafaya API Debug] Request failed: ' . $error_message);
            return [
                'success' => false,
                'message' => $error_message
            ];
        }
        
        $body = wp_remote_retrieve_body($response);
        $status = wp_remote_retrieve_response_code($response);
        $response_headers = wp_remote_retrieve_headers($response);
        
        error_log('[Supafaya API Debug] Response status: ' . $status);
        error_log('[Supafaya API Debug] Response headers: ' . print_r($response_headers, true));
        error_log('[Supafaya API Debug] Response body: ' . $body);
        
        // Try to parse response as JSON
        $parsed_body = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('[Supafaya API Debug] Failed to parse response as JSON: ' . json_last_error_msg());
        }
        
        return [
            'success' => $status >= 200 && $status < 300,
            'status' => $status,
            'data' => $parsed_body
        ];
    }
    
    public function get($endpoint, $params = []) {
        if (!empty($params)) {
            $endpoint .= '?' . http_build_query($params);
        }
        return $this->request($endpoint, 'GET');
    }
    
    public function post($endpoint, $data) {
        return $this->request($endpoint, 'POST', $data);
    }
    
    public function put($endpoint, $data) {
        return $this->request($endpoint, 'PUT', $data);
    }
    
    public function delete($endpoint) {
        return $this->request($endpoint, 'DELETE');
    }
}