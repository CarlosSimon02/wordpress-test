<?php
namespace SupafayaTickets\Controllers;

use SupafayaTickets\Api\ApiClient;
use SupafayaTickets\Api\TicketService;
use SupafayaTickets\Api\EventService;

class TicketController
{
  private $ticket_service;
  private $api_client;
  private $event_service;

  public function __construct()
  {
    $this->api_client = new ApiClient();
    $this->ticket_service = new TicketService($this->api_client);
    $this->event_service = new EventService($this->api_client);

    // Apply API token to all requests
    add_action('init', [$this, 'setup_api_token']);

    // Register shortcodes
    add_shortcode('supafaya_ticket_checkout', [$this, 'ticket_checkout_shortcode']);
    add_shortcode('supafaya_my_tickets', [$this, 'my_tickets_shortcode']);

    // Register AJAX handlers
    add_action('wp_ajax_supafaya_purchase_ticket', [$this, 'ajax_purchase_ticket']);
  }

  /**
   * Set up API token from filter
   */
  public function setup_api_token()
  {
    $token = apply_filters('supafaya_api_token', null);
    if ($token) {
      $this->api_client->setToken($token);
    }
  }

  /**
   * Shortcode for ticket checkout form
   */
  public function ticket_checkout_shortcode($atts)
  {
    $atts = shortcode_atts([
      'event_id' => '',
    ], $atts);

    if (empty($atts['event_id'])) {
      return '<p>Error: Event ID is required</p>';
    }

    // Check if user is logged in
    if (!is_user_logged_in()) {
      return '<p>Please <a href="' . wp_login_url(get_permalink()) . '">login</a> to purchase tickets</p>';
    }

    // Get event details using the event service directly
    $event_response = $this->event_service->getEventById($atts['event_id']);

    if (!$event_response['success']) {
      return '<p>Error loading event: ' . esc_html($event_response['message'] ?? 'Unknown error') . '</p>';
    }

    $event = $event_response['data']['data'] ?? null;

    if (!$event) {
      return '<p>Event not found</p>';
    }

    // Get available tickets
    $tickets_response = $this->ticket_service->getEventTickets($atts['event_id']);

    if (!$tickets_response['success']) {
      return '<p>Error loading tickets: ' . esc_html($tickets_response['message'] ?? 'Unknown error') . '</p>';
    }

    $tickets = $tickets_response['data']['data'] ?? [];

    ob_start();
    include SUPAFAYA_PLUGIN_DIR . 'templates/ticket-checkout.php';
    return ob_get_clean();
  }

  /**
   * Shortcode for displaying user's tickets
   */
  public function my_tickets_shortcode($atts)
  {
    $atts = shortcode_atts([
      'limit' => 10,
    ], $atts);

    // Check if user is logged in
    if (!is_user_logged_in()) {
      return '<p>Please <a href="' . wp_login_url(get_permalink()) . '">login</a> to view your tickets</p>';
    }

    $response = $this->ticket_service->getUserTickets([
      'limit' => $atts['limit']
    ]);

    if (!$response['success']) {
      return '<p>Error loading tickets: ' . esc_html($response['message'] ?? 'Unknown error') . '</p>';
    }

    $tickets = $response['data']['data'] ?? [];

    ob_start();
    include SUPAFAYA_PLUGIN_DIR . 'templates/my-tickets.php';
    return ob_get_clean();
  }

  /**
   * AJAX handler for purchasing a ticket
   */
  public function ajax_purchase_ticket()
  {
    // Check if user is logged in
    if (!is_user_logged_in()) {
      wp_send_json([
        'success' => false,
        'message' => 'User not logged in'
      ]);
      return;
    }

    // Verify nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'supafaya_ticket_nonce')) {
      wp_send_json([
        'success' => false,
        'message' => 'Security verification failed'
      ]);
      return;
    }

    $event_id = sanitize_text_field($_POST['event_id'] ?? '');
    $tickets = isset($_POST['tickets']) ? $_POST['tickets'] : [];
    $addons = isset($_POST['addons']) ? $_POST['addons'] : [];

    if (empty($event_id) || empty($tickets)) {
      wp_send_json([
        'success' => false,
        'message' => 'Missing required fields'
      ]);
      return;
    }

    // Sanitize tickets and addons data
    $sanitized_tickets = [];
    foreach ($tickets as $ticket) {
      if (isset($ticket['ticket_id']) && isset($ticket['quantity'])) {
        $sanitized_tickets[] = [
          'ticket_id' => sanitize_text_field($ticket['ticket_id']),
          'quantity' => intval($ticket['quantity'])
        ];
      }
    }

    $sanitized_addons = [];
    foreach ($addons as $addon) {
      if (isset($addon['addon_id']) && isset($addon['quantity'])) {
        $sanitized_addons[] = [
          'addon_id' => sanitize_text_field($addon['addon_id']),
          'quantity' => intval($addon['quantity'])
        ];
      }
    }

    // Prepare the complete ticket data
    $ticket_data = [
      'event_id' => $event_id,
      'tickets' => $sanitized_tickets
    ];

    // Add addons if present
    if (!empty($sanitized_addons)) {
      $ticket_data['addons'] = $sanitized_addons;
    }

    // Call the ticket service to purchase ticket
    $response = $this->ticket_service->purchaseTicket($ticket_data);

    wp_send_json($response);
  }
}