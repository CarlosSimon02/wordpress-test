<?php
namespace SupafayaTickets\Api;

class TicketService {
    private $api_client;
    
    public function __construct(ApiClient $api_client) {
        $this->api_client = $api_client;
    }
    
    /**
     * Purchase ticket
     */
    public function purchaseTicket($ticket_data) {
        return $this->api_client->post('/payments/initialize', $ticket_data);
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