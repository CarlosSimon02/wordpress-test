<?php
namespace SupafayaTickets\Api;

/**
 * Service for analytics-related API calls
 */
class AnalyticsService {
    protected $api_client;
    
    /**
     * Constructor
     * 
     * @param ApiClient $api_client The API client instance
     */
    public function __construct(ApiClient $api_client) {
        $this->api_client = $api_client;
    }
    
    /**
     * Send analytics ping for an organization or event
     * 
     * @param string $reference_id The ID of the organization or event
     * @param string $reference_type The type of reference ('organizations' or 'events')
     * @param string|null $user_id Optional user ID
     * @param string|null $referer Optional referer URL
     * @return array Response from the API
     */
    public function ping($reference_id, $reference_type, $user_id = null, $referer = null) {
        try {
            // Debug information
            error_log('[Supafaya Analytics] Sending ping - Reference ID: ' . $reference_id . ', Type: ' . $reference_type);
            
            // Set up custom headers according to API expectations
            $headers = [
                'x-data-reference-id' => $reference_id,
                'x-data-reference-type' => $reference_type
            ];
            
            // Add user ID if available
            if ($user_id) {
                $headers['x-data-user-id'] = $user_id;
                error_log('[Supafaya Analytics] Including user ID in headers: ' . $user_id);
            }
            
            // Add referer if available - this is crucial for tracking the origin
            if ($referer) {
                $headers['referer'] = $referer;
                error_log('[Supafaya Analytics] Including referer in headers: ' . $referer);
            }
            
            // Send the request to the ping endpoint
            // We're using an empty params array since we're passing everything in headers
            $response = $this->api_client->request('/analytics/ping', 'GET', null, $headers);
            
            // Log the response
            error_log('[Supafaya Analytics] Ping response: ' . json_encode($response));
            
            return $response;
        } catch (\Exception $e) {
            error_log('[Supafaya Analytics] Error sending ping: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error sending analytics ping: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Proxy the analytics ping to avoid CORS issues
     * This method can be exposed via WordPress REST API
     * 
     * @param array $data Request data containing reference_id and reference_type
     * @return array Response to return to the client
     */
    public function proxyPing($data) {
        $reference_id = $data['reference_id'] ?? null;
        $reference_type = $data['reference_type'] ?? null;
        $user_id = $data['user_id'] ?? null;
        $referer = $data['referer'] ?? ($_SERVER['HTTP_REFERER'] ?? null);
        
        if (!$reference_id || !$reference_type) {
            error_log('[Supafaya Analytics] Missing required parameters for ping');
            return [
                'success' => false,
                'message' => 'Missing required parameters'
            ];
        }
        
        // Validate reference type
        if (!in_array($reference_type, ['organizations', 'events'])) {
            error_log('[Supafaya Analytics] Invalid reference_type: ' . $reference_type);
            return [
                'success' => false,
                'message' => 'Invalid reference type'
            ];
        }
        
        return $this->ping($reference_id, $reference_type, $user_id, $referer);
    }
} 