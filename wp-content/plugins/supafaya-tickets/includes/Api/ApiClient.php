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
        
        $default_headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];
        
        if ($this->token) {
            $default_headers['Authorization'] = 'Bearer ' . $this->token;
        }
        
        $headers = array_merge($default_headers, $headers);
        $header_strings = [];
        
        foreach ($headers as $key => $value) {
            $header_strings[] = $key . ': ' . $value;
        }
        
        $args = [
            'method' => $method,
            'timeout' => 30,
            'redirection' => 5,
            'httpversion' => '1.1',
            'headers' => $header_strings,
            'sslverify' => false
        ];
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $args['body'] = json_encode($data);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => $response->get_error_message()
            ];
        }
        
        $body = wp_remote_retrieve_body($response);
        $status = wp_remote_retrieve_response_code($response);
        
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