<?php
namespace SupafayaTickets\Api;

class EventService {
    private $api_client;
    
    public function __construct(ApiClient $api_client) {
        $this->api_client = $api_client;
    }
    
    /**
     * Get events by organization
     */
    public function getEventsByOrganization($organization_id, $params = []) {
        $default_params = [
            'organization_id' => $organization_id,
            'limit' => 10,
            'filter' => 'upcoming',
            'sort' => 'desc',
            'include_private' => true
        ];
        
        $params = array_merge($default_params, $params);
        return $this->api_client->get('/customer/events', $params);
    }
    
    /**
     * Get event by ID
     */
    public function getEventById($event_id) {
        return $this->api_client->get('/customer/events/' . $event_id);
    }
    
    /**
     * Get event participants
     */
    public function getEventParticipants($event_id, $params = []) {
        return $this->api_client->get('/customer/events/' . $event_id . '/participants', $params);
    }
    
    /**
     * Get event addons
     */
    public function getEventAddons($event_id) {
        return $this->api_client->get('/events/' . $event_id . '/addons');
    }
}