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
        
        // Initialize headers as associative array
        $default_headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];
        
        // Only add token if it exists and is not empty
        if (!empty($this->token)) {
            $default_headers['x-access-token'] = (string)$this->token;
            error_log('Sending request with token: ' . substr($this->token, 0, 10) . '...'); // Log first 10 chars for security
        } else {
            error_log('No token available for this request');
        }
        
        // Merge headers preserving the associative array format
        $headers = array_merge($default_headers, $headers);
        
        $args = [
            'method' => $method,
            'timeout' => 30,
            'redirection' => 5,
            'httpversion' => '1.1',
            'headers' => $headers, // Keep as associative array
            'sslverify' => false,
            'blocking' => true
        ];
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $args['body'] = json_encode($data);
            error_log('Request body: ' . print_r($data, true));
        }
        
        error_log('Final request headers: ' . print_r($headers, true));
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            error_log('API request failed: ' . $response->get_error_message());
            return [
                'success' => false,
                'message' => $response->get_error_message()
            ];
        }
        
        $body = wp_remote_retrieve_body($response);
        $status = wp_remote_retrieve_response_code($response);
        
        error_log('API response status: ' . $status);
        error_log('API response body: ' . $body);
        
        return [
            'success' => $status >= 200 && $status < 300,
            'status' => $status,
            'data' => json_decode($body, true)
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