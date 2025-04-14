<?php
namespace SupafayaTickets\Api;

class AnalyticsService {
    protected $api_client;
    
    public function __construct(ApiClient $api_client) {
        $this->api_client = $api_client;
    }
    
    public function ping($reference_id, $reference_type, $user_id = null, $referer = null) {
        try {
            $headers = [
                'x-data-reference-id' => $reference_id,
                'x-data-reference-type' => $reference_type
            ];
            
            if ($user_id) {
                $headers['x-data-user-id'] = $user_id;
            }
            
            if ($referer) {
                $headers['referer'] = $referer;
            }
            
            $response = $this->api_client->request('/analytics/ping', 'GET', null, $headers);
            
            return $response;
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error sending analytics ping: ' . $e->getMessage()
            ];
        }
    }
    
    public function proxyPing($data) {
        $reference_id = $data['reference_id'] ?? null;
        $reference_type = $data['reference_type'] ?? null;
        $user_id = $data['user_id'] ?? null;
        $referer = $data['referer'] ?? ($_SERVER['HTTP_REFERER'] ?? null);
        
        if (!$reference_id || !$reference_type) {
            return [
                'success' => false,
                'message' => 'Missing required parameters'
            ];
        }
        
        if (!in_array($reference_type, ['organizations', 'events'])) {
            return [
                'success' => false,
                'message' => 'Invalid reference type'
            ];
        }
        
        return $this->ping($reference_id, $reference_type, $user_id, $referer);
    }
}