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

    // Register AJAX handlers for both logged in and not logged in users
    add_action('wp_ajax_supafaya_purchase_ticket', [$this, 'ajax_purchase_ticket']);
    add_action('wp_ajax_nopriv_supafaya_purchase_ticket', [$this, 'ajax_purchase_ticket']);
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

    // Get Firebase token from cookie - no longer requiring WordPress login
    $firebase_logged_in = isset($_COOKIE['firebase_user_token']);
    if (!$firebase_logged_in) {
      // Redirect to login page
      $login_url = get_option('supafaya_login_page_url', home_url());
      return '<p>Please <a href="' . esc_url($login_url) . '">login</a> to purchase tickets</p>';
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

    // Check if Firebase user is logged in via cookie
    $firebase_logged_in = isset($_COOKIE['firebase_user_token']);
    if (!$firebase_logged_in) {
      // Redirect to login page
      $login_url = get_option('supafaya_login_page_url', home_url());
      return '<p>Please <a href="' . esc_url($login_url) . '">login</a> to view your tickets</p>';
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
    // Check for Firebase token
    $firebase_token = $this->get_firebase_token();
    
    if (!$firebase_token) {
        wp_send_json([
            'success' => false,
            'message' => 'User not authenticated'
        ]);
        return;
    }
    
    // Use the Firebase token for API requests
    $this->api_client->setToken($firebase_token);

    // Verify nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'supafaya-tickets-nonce')) {
        wp_send_json([
            'success' => false,
            'message' => 'Security verification failed'
        ]);
        return;
    }

    // Get user data with improved error handling
    $user_data = $this->get_user_data();
    
    // Validate that we have an email address
    if (empty($user_data['email'])) {
        wp_send_json([
            'success' => false,
            'message' => 'User email is required. Please make sure you are properly logged in.'
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
    $ticket_info = [];
    foreach ($tickets as $ticket) {
        if (isset($ticket['ticket_id']) && isset($ticket['quantity']) && $ticket['quantity'] > 0) {
            $ticket_id = sanitize_text_field($ticket['ticket_id']);
            $sanitized_tickets[] = [
                'ticket_id' => $ticket_id,
                'quantity' => intval($ticket['quantity'])
            ];
            
            // Store additional ticket info if provided
            if (isset($ticket['name']) || isset($ticket['price'])) {
                $ticket_info[$ticket_id] = [
                    'name' => sanitize_text_field($ticket['name'] ?? ''),
                    'price' => floatval($ticket['price'] ?? 0),
                    'description' => sanitize_text_field($ticket['description'] ?? ''),
                    'type' => sanitize_text_field($ticket['type'] ?? 'regular')
                ];
            }
        }
    }

    $sanitized_addons = [];
    $addon_info = [];
    foreach ($addons as $addon) {
        if (isset($addon['addon_id']) && isset($addon['quantity']) && $addon['quantity'] > 0) {
            $addon_id = sanitize_text_field($addon['addon_id']);
            $sanitized_addons[] = [
                'addon_id' => $addon_id,
                'quantity' => intval($addon['quantity']),
                'ticket_id' => sanitize_text_field($addon['ticket_id'] ?? '')
            ];
            
            // Store additional addon info if provided
            if (isset($addon['name']) || isset($addon['price'])) {
                $addon_info[$addon_id] = [
                    'name' => sanitize_text_field($addon['name'] ?? ''),
                    'price' => floatval($addon['price'] ?? 0),
                    'ticket_id' => sanitize_text_field($addon['ticket_id'] ?? '')
                ];
            }
        }
    }
    
    // Load missing ticket/addon info from database if needed
    if (empty($ticket_info) || empty($addon_info)) {
        $this->load_item_info($event_id, $sanitized_tickets, $sanitized_addons, $ticket_info, $addon_info);
    }

    // Get payment redirect URLs if provided
    $payment_redirect_urls = isset($_POST['payment_redirect_urls']) ? $_POST['payment_redirect_urls'] : [];
    
    // Sanitize redirect URLs
    $sanitized_redirect_urls = [];
    if (!empty($payment_redirect_urls)) {
        $sanitized_redirect_urls = [
            'success' => esc_url_raw($payment_redirect_urls['success'] ?? ''),
            'failed' => esc_url_raw($payment_redirect_urls['failed'] ?? ''),
            'cancel' => esc_url_raw($payment_redirect_urls['cancel'] ?? '')
        ];
    }

    // Prepare the complete ticket data
    $ticket_data = [
        'event_id' => $event_id,
        'tickets' => $sanitized_tickets,
        'ticket_info' => $ticket_info,
        'user_data' => $user_data
    ];

    // Add payment redirect URLs if available
    if (!empty($sanitized_redirect_urls)) {
        $ticket_data['payment_redirect_urls'] = $sanitized_redirect_urls;
    }

    // Add addons if present
    if (!empty($sanitized_addons)) {
        $ticket_data['addons'] = $sanitized_addons;
        $ticket_data['addon_info'] = $addon_info;
    }

    // Call the ticket service to purchase ticket
    $response = $this->ticket_service->purchaseTicket($ticket_data);

    wp_send_json($response);
  }

  /**
   * Load ticket and addon information from the database
   */
  private function load_item_info($event_id, $tickets, $addons, &$ticket_info, &$addon_info)
  {
    // Get event tickets to fill in missing info
    $tickets_response = $this->ticket_service->getEventTickets($event_id);
    
    if ($tickets_response['success'] && !empty($tickets_response['data']['data'])) {
        $available_tickets = $tickets_response['data']['data'];
        
        // Fill in ticket info
        foreach ($tickets as $ticket_item) {
            $ticket_id = $ticket_item['ticket_id'];
            
            // Skip if we already have info for this ticket
            if (isset($ticket_info[$ticket_id])) {
                continue;
            }
            
            // Find matching ticket in available tickets
            foreach ($available_tickets as $available_ticket) {
                if ($available_ticket['id'] === $ticket_id) {
                    $ticket_info[$ticket_id] = [
                        'name' => $available_ticket['name'] ?? '',
                        'price' => floatval($available_ticket['price'] ?? 0),
                        'description' => $available_ticket['description'] ?? '',
                        'type' => $available_ticket['type'] ?? 'regular'
                    ];
                    break;
                }
            }
        }
        
        // Fill in addon info (assuming addons are available in the same response)
        if (!empty($addons) && !empty($available_tickets[0]['addons'])) {
            $available_addons = $available_tickets[0]['addons'];
            
            foreach ($addons as $addon_item) {
                $addon_id = $addon_item['addon_id'];
                
                // Skip if we already have info for this addon
                if (isset($addon_info[$addon_id])) {
                    continue;
                }
                
                // Find matching addon in available addons
                foreach ($available_addons as $available_addon) {
                    if ($available_addon['id'] === $addon_id) {
                        $addon_info[$addon_id] = [
                            'name' => $available_addon['name'] ?? '',
                            'price' => floatval($available_addon['price'] ?? 0),
                            'ticket_id' => $addon_item['ticket_id'] ?? ''
                        ];
                        break;
                    }
                }
            }
        }
    }
  }

  /**
   * Get user data from Firebase authentication
   */
  private function get_user_data() {
    $user_data = [
        'name' => '',
        'email' => '',
        'phone' => isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : ''
    ];
    
    // Try to get user data from firebase_user cookie first
    if (!empty($_COOKIE['firebase_user'])) {
        $firebase_user = json_decode(stripslashes($_COOKIE['firebase_user']), true);
        if ($firebase_user && !empty($firebase_user['email'])) {
            $user_data['name'] = sanitize_text_field($firebase_user['displayName'] ?? '');
            $user_data['email'] = sanitize_email($firebase_user['email']);
        }
    }
    
    // If email is still empty, try to get it from Firebase token
    if (empty($user_data['email'])) {
        $firebase_token = $this->get_firebase_token();
        if ($firebase_token) {
            // Try to decode the token (JWT format)
            $token_parts = explode('.', $firebase_token);
            if (count($token_parts) === 3) {
                $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $token_parts[1])), true);
                if ($payload && isset($payload['email'])) {
                    $user_data['email'] = sanitize_email($payload['email']);
                    if (empty($user_data['name']) && isset($payload['name'])) {
                        $user_data['name'] = sanitize_text_field($payload['name']);
                    }
                }
            }
        }
    }
    
    // As a last resort, check if user data was passed directly in the POST request
    if (empty($user_data['email']) && isset($_POST['email'])) {
        $user_data['email'] = sanitize_email($_POST['email']);
    }
    
    if (empty($user_data['name']) && isset($_POST['name'])) {
        $user_data['name'] = sanitize_text_field($_POST['name']);
    }
    
    // Log the user data for debugging
    error_log('User data for purchase: ' . print_r($user_data, true));
    
    return $user_data;
  }

  /**
   * Get Firebase token from request
   */
  private function get_firebase_token() {
    // Check if token is in header
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    if (isset($headers['X-Firebase-Token'])) {
        return $headers['X-Firebase-Token'];
    }
    
    // Check if token is in cookie
    if (isset($_COOKIE['firebase_user_token'])) {
        return $_COOKIE['firebase_user_token'];
    }
    
    // Check if token is in POST data
    if (isset($_POST['firebase_token'])) {
        return sanitize_text_field($_POST['firebase_token']);
    }
    
    return null;
  }
}