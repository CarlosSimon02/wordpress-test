<?php
namespace SupafayaTickets\Api;

class TicketService {
    private $api_client;
    
    public function __construct(ApiClient $api_client) {
        $this->api_client = $api_client;
    }
    
    /**
     * Purchase ticket
     * 
     * Formats the ticket data according to the PurchaseRequest interface
     * @param array $ticket_data Raw ticket data
     * @return array API response
     */
    public function purchaseTicket($ticket_data) {
        error_log('Raw ticket data: ' . print_r($ticket_data, true));
        
        // Get user info from ticket data or cookies
        $user_data = isset($ticket_data['user_data']) ? $ticket_data['user_data'] : [];
        
        // Calculate total amount to determine if it's a free transaction
        $total_amount = 0;
        
        // Calculate tickets total
        if (!empty($ticket_data['tickets'])) {
            foreach ($ticket_data['tickets'] as $ticket) {
                $ticket_info = isset($ticket_data['ticket_info'][$ticket['ticket_id']]) 
                    ? $ticket_data['ticket_info'][$ticket['ticket_id']] 
                    : [];
                
                $unit_price = isset($ticket_info['price']) ? (float)$ticket_info['price'] : 0;
                $quantity = (int)$ticket['quantity'];
                
                $total_amount += $unit_price * $quantity;
            }
        }
        
        // Add addon total if any
        if (!empty($ticket_data['addons'])) {
            foreach ($ticket_data['addons'] as $addon) {
                $addon_info = isset($ticket_data['addon_info'][$addon['addon_id']]) 
                    ? $ticket_data['addon_info'][$addon['addon_id']] 
                    : [];
                
                $unit_price = isset($addon_info['price']) ? (float)$addon_info['price'] : 0;
                $quantity = (int)$addon['quantity'];
                
                $total_amount += $unit_price * $quantity;
            }
        }
        
        // Determine payment method
        $payment_method = 'free'; // Default to free
        if ($total_amount > 0) {
            // Use specified payment method if provided, otherwise default to 'card'
            $payment_method = isset($ticket_data['payment_method']) ? $ticket_data['payment_method'] : 'card';
        }
        
        // Format data according to PurchaseRequest interface
        $purchase_request = [
            'eventId' => $ticket_data['event_id'],
            'tickets' => [], // Will be populated below
            'paymentMethod' => $payment_method,
            'customerDetails' => [
                'name' => $user_data['name'] ?? '',
                'email' => $user_data['email'] ?? '',
                'phone' => $user_data['phone'] ?? null
            ],
            'paymentRedirectUrls' => [
                'success' => $ticket_data['payment_redirect_urls']['success'] ?? site_url('/payment-success'), 
                'failed' => $ticket_data['payment_redirect_urls']['failed'] ?? site_url('/payment-failed'),
                'cancel' => $ticket_data['payment_redirect_urls']['cancel'] ?? site_url('/payment-cancelled')
            ]
        ];
        
        // Add payment details if it's a manual payment method
        if ($payment_method === 'manual_bank_transfer' && isset($ticket_data['payment_details'])) {
            $purchase_request['paymentDetails'] = $ticket_data['payment_details'];
        }
        
        // Format tickets according to TicketPurchaseItem interface
        if (!empty($ticket_data['tickets'])) {
            foreach ($ticket_data['tickets'] as $ticket) {
                // Get additional ticket info if available
                $ticket_info = isset($ticket_data['ticket_info'][$ticket['ticket_id']]) 
                    ? $ticket_data['ticket_info'][$ticket['ticket_id']] 
                    : [];
                
                $purchase_request['tickets'][] = [
                    'ticketId' => $ticket['ticket_id'],
                    'quantity' => (int)$ticket['quantity'],
                    'unitPrice' => isset($ticket_info['price']) ? (float)$ticket_info['price'] : 0,
                    'ticketType' => $ticket_info['type'] ?? 'regular',
                    'name' => $ticket_info['name'] ?? '',
                    'description' => $ticket_info['description'] ?? ''
                ];
            }
        }
        
        // Format addons according to AddOnItem interface (if any)
        if (!empty($ticket_data['addons'])) {
            $purchase_request['addons'] = [];
            
            foreach ($ticket_data['addons'] as $addon) {
                // Get additional addon info if available
                $addon_info = isset($ticket_data['addon_info'][$addon['addon_id']]) 
                    ? $ticket_data['addon_info'][$addon['addon_id']] 
                    : [];
                
                $purchase_request['addons'][] = [
                    'addonId' => $addon['addon_id'],
                    'quantity' => (int)$addon['quantity'],
                    'unitPrice' => isset($addon_info['price']) ? (float)$addon_info['price'] : 0,
                    'name' => $addon_info['name'] ?? '',
                    'ticketId' => $addon_info['ticket_id'] ?? $addon['ticket_id'] ?? null
                ];
            }
        }
        
        error_log('Formatted purchase request: ' . json_encode($purchase_request, JSON_PRETTY_PRINT));
        
        return $this->api_client->post('/payments/initialize', $purchase_request);
    }
    
    /**
     * Get user tickets
     */
    public function getUserTickets($params = []) {
        return $this->api_client->get('/tickets', $params);
    }
    
    /**
     * Get event tickets
     */
    public function getEventTickets($event_id, $params = []) {
        return $this->api_client->get('/events/' . $event_id . '/tickets', $params);
    }
}