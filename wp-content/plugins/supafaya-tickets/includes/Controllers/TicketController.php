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
    add_shortcode('supafaya_payment_history', [$this, 'payment_history_shortcode']);

    // Register AJAX handlers for both logged in and not logged in users
    add_action('wp_ajax_supafaya_purchase_ticket', [$this, 'ajax_purchase_ticket']);
    add_action('wp_ajax_nopriv_supafaya_purchase_ticket', [$this, 'ajax_purchase_ticket']);
    add_action('wp_ajax_supafaya_load_payment_history', [$this, 'ajax_load_payment_history']);
    add_action('wp_ajax_nopriv_supafaya_load_payment_history', [$this, 'ajax_load_payment_history']);
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
   * Shortcode handler for displaying payment history
   */
  public function payment_history_shortcode($atts)
  {
    // Parse attributes
    $atts = shortcode_atts(array(
      'organization_id' => '',
      'limit' => 10,
      'page' => 1
    ), $atts, 'supafaya_payment_history');
    
    // Debug
    error_log('Payment History: Shortcode attributes: ' . json_encode($atts));
    
    // Also try to get the Firebase user info from cookie if available
    $user_info = '';
    if (isset($_COOKIE['firebase_user'])) {
      $user_data = json_decode(stripslashes($_COOKIE['firebase_user']), true);
      if ($user_data && isset($user_data['email'])) {
        $user_info = 'Logged in as: ' . esc_html($user_data['email']);
      }
    }

    // Prepare to display the template
    wp_enqueue_script('supafaya-payment-history', SUPAFAYA_PLUGIN_URL . 'assets/js/payment-history.js', array('jquery'), SUPAFAYA_VERSION, true);
    
    // Localize script with needed data
    $script_data = array(
      'ajaxUrl' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('supafaya-tickets-nonce'),
      'organizationId' => esc_attr($atts['organization_id']),
      'limit' => intval($atts['limit']),
      'currentPage' => intval($atts['page']),
      'debug' => defined('WP_DEBUG') && WP_DEBUG,
      'userInfo' => $user_info
    );
    wp_localize_script('supafaya-payment-history', 'supafayaPaymentHistory', $script_data);
    
    // Log debug info
    error_log('Payment History: Template ready to load, using organization_id: ' . $atts['organization_id']);
    
    // Load the template
    ob_start();
    include SUPAFAYA_PLUGIN_DIR . 'templates/payment-history.php';
    return ob_get_clean();
  }
  
  /**
   * AJAX handler for loading payment history
   */
  public function ajax_load_payment_history()
  {
    error_log('AJAX Payment History: Request received');
    
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'supafaya-tickets-nonce')) {
      error_log('AJAX Payment History: Nonce verification failed. Provided nonce: ' . $_POST['nonce']);
      wp_send_json(array(
        'success' => false,
        'message' => 'Security verification failed',
        'debug_info' => 'Please check that the nonce is correct'
      ));
      return;
    }
    
    // Get parameters
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
    $organization_id = isset($_POST['organization_id']) ? sanitize_text_field($_POST['organization_id']) : '';
    
    error_log('AJAX Payment History: Parameters - page: ' . $page . ', limit: ' . $limit . ', organization_id: ' . $organization_id);
    
    // Get Firebase token
    $firebase_token = $this->get_firebase_token();
    
    error_log('AJAX Payment History: Firebase token check - ' . ($firebase_token ? 'Found token' : 'No token found'));
    
    if (!$firebase_token) {
      error_log('AJAX Payment History: Failed to get Firebase token');
      wp_send_json(array(
        'success' => false,
        'message' => 'Authentication required. Please log in to view your payment history.',
        'error_code' => 'auth_required'
      ));
      return;
    }
    
    // Set token for API requests
    $this->api_client->setToken($firebase_token);
    
    // Prepare query parameters
    $query_params = array(
      'page' => $page,
      'limit' => $limit
    );
    
    if (!empty($organization_id)) {
      $query_params['organizationId'] = $organization_id;
    }
    
    error_log('AJAX Payment History: Query parameters: ' . json_encode($query_params));
    
    // Get the user ID from Firebase token claims
    try {
      // Instead of using the Firebase PHP SDK which isn't available, 
      // we'll manually decode the JWT token to get the user ID
      error_log('AJAX Payment History: Manually decoding the token to get user ID');
      
      // JWT tokens are in the format: header.payload.signature
      $token_parts = explode('.', $firebase_token);
      if (count($token_parts) !== 3) {
        throw new \Exception('Invalid token format');
      }
      
      // The second part is the payload, which is base64 encoded
      $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $token_parts[1]));
      if (!$payload) {
        throw new \Exception('Could not decode token payload');
      }
      
      $payload_data = json_decode($payload, true);
      if (!$payload_data || !isset($payload_data['sub'])) {
        throw new \Exception('Invalid token payload or missing user ID');
      }
      
      $user_id = $payload_data['sub'];
      error_log('AJAX Payment History: Successfully extracted user ID: ' . $user_id);
      
      // Call API to get payment history
      error_log('AJAX Payment History: Calling API endpoint: /payments/user/' . $user_id . '/history');
      $response = $this->api_client->get('/payments/user/' . $user_id . '/history', $query_params);
      
      error_log('AJAX Payment History: API response received, success: ' . ($response['success'] ? 'true' : 'false'));
      error_log('AJAX Payment History: Full API response: ' . json_encode($response));
      
      if ($response['success']) {
        // Log summary of results - Fix the nested data structure access
        $api_data = $response['data']['data'] ?? [];
        $results_count = isset($api_data['payments']) ? count($api_data['payments']) : 0;
        $pagination = isset($api_data['pagination']) ? $api_data['pagination'] : [];
        
        error_log('AJAX Payment History: Returning ' . $results_count . ' results. Pagination: ' . json_encode($pagination));
        
        wp_send_json($response);
      } else {
        error_log('AJAX Payment History: API error: ' . ($response['message'] ?? 'Unknown error'));
        wp_send_json(array(
          'success' => false,
          'message' => $response['message'] ?? 'Failed to load payment history',
          'error_code' => 'api_error',
          'details' => $response['error'] ?? null
        ));
      }
    } catch (\Exception $e) {
      error_log('AJAX Payment History: Exception: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
      wp_send_json(array(
        'success' => false,
        'message' => 'Authentication error: ' . $e->getMessage(),
        'error_code' => 'auth_error'
      ));
    }
  }
  
  /**
   * Helper to get Firebase token from various sources
   */
  private function get_firebase_token()
  {
    // Try to get from cookie first
    if (isset($_COOKIE['firebase_user_token'])) {
      return $_COOKIE['firebase_user_token'];
    }
    
    // Try to get from request header (for AJAX)
    if (isset($_SERVER['HTTP_X_FIREBASE_TOKEN'])) {
      return $_SERVER['HTTP_X_FIREBASE_TOKEN'];
    }
    
    return null;
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
}