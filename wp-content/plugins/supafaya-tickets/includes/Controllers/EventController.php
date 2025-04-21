<?php
namespace SupafayaTickets\Controllers;

use SupafayaTickets\Api\ApiClient;
use SupafayaTickets\Api\EventService;

class EventController
{
  protected $event_service;
  protected $api_client;

  public function __construct()
  {
    $this->api_client = new ApiClient();
    $this->event_service = new EventService($this->api_client);

    add_action('init', [$this, 'setup_api_token']);
    
    add_shortcode('supafaya_events', [$this, 'events_shortcode']);
    add_shortcode('supafaya_event', [$this, 'event_shortcode']);

    add_action('wp_ajax_supafaya_load_events', [$this, 'ajax_load_events']);
    add_action('wp_ajax_nopriv_supafaya_load_events', [$this, 'ajax_load_events']);
    add_action('wp_ajax_supafaya_get_user_items', [$this, 'ajax_get_user_items']);
    add_action('wp_ajax_nopriv_supafaya_get_user_items', [$this, 'ajax_get_user_items']);

    add_action('rest_api_init', [$this, 'register_rest_routes']);
  }

  public function getEventService(): EventService
  {
    return $this->event_service;
  }

  public function setup_api_token()
  {
    $token = apply_filters('supafaya_api_token', null);
    if ($token) {
      $this->api_client->setToken($token);
    }
  }

  public function events_shortcode($atts)
  {
    $default_org_id = get_option('supafaya_organization_id', '');
    
    $atts = shortcode_atts([
      'organization_id' => $default_org_id,
      'limit' => 10,
      'filter' => 'upcoming',
      'template' => 'grid'
    ], $atts);

    if (empty($atts['organization_id'])) {
      return '<p>Error: Organization ID is not configured. Please set it in the Supafaya Tickets settings.</p>';
    }

    $response = $this->event_service->getEventsByOrganization($atts['organization_id'], [
      'limit' => $atts['limit'],
      'filter' => $atts['filter'],
      'include_private' => true
    ]);

    if (!$response['success']) {
      return '<p>Error loading events: ' . esc_html($response['message'] ?? 'Unknown error') . '</p>';
    }

    $events = $response['data']['data']['results'] ?? [];

    ob_start();
    include SUPAFAYA_PLUGIN_DIR . 'templates/events-' . $atts['template'] . '.php';
    return ob_get_clean();
  }

  public function event_shortcode($atts)
  {
    $atts = shortcode_atts([
      'event_id' => '',
      'template' => 'default',
      'require_login' => 'true'
    ], $atts);

    if (empty($atts['event_id']) && isset($_GET['event_id'])) {
      $atts['event_id'] = sanitize_text_field($_GET['event_id']);
    }

    if (empty($atts['event_id'])) {
      return '<div class="supafaya-event-error">
                <p>No event ID was provided. Please go back to the events page and select an event.</p>
              </div>';
    }

    $response = $this->event_service->getEventById($atts['event_id']);

    if (!$response['success']) {
      return '<div class="supafaya-event-error">
                <p>Error loading event: ' . esc_html($response['message'] ?? 'Unknown error') . '</p>
              </div>';
    }

    $event = $response['data']['data'] ?? null;

    if (!$event) {
      return '<div class="supafaya-event-error">
                <p>Event not found</p>
              </div>';
    }

    $addons_response = $this->event_service->getEventAddons($atts['event_id']);
    if ($addons_response['success']) {
      $event['addons'] = $addons_response['data']['data'] ?? [];
    }

    $require_login = filter_var($atts['require_login'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== false;
    
    $template_data = [
      'event' => $event,
      'require_login' => $require_login,
      'atts' => $atts
    ];
    
    extract($template_data);
    
    ob_start();
    include SUPAFAYA_PLUGIN_DIR . 'templates/event-' . $atts['template'] . '.php';
    return ob_get_clean();
  }

  public function ajax_load_events()
  {
    $default_org_id = get_option('supafaya_organization_id', '');
    
    $organization_id = sanitize_text_field($_POST['organization_id'] ?? $default_org_id);
    $limit = intval($_POST['limit'] ?? 10);
    $filter = sanitize_text_field($_POST['filter'] ?? 'upcoming');
    $next_cursor = sanitize_text_field($_POST['next_cursor'] ?? '');

    if (empty($organization_id)) {
      wp_send_json([
        'success' => false,
        'message' => 'Organization ID is not configured. Please set it in the Supafaya Tickets settings.'
      ]);
      return;
    }

    $params = [
      'limit' => $limit,
      'filter' => $filter,
      'include_private' => true
    ];

    if (!empty($next_cursor)) {
      $params['next_page_cursor'] = $next_cursor;
    }

    $response = $this->event_service->getEventsByOrganization($organization_id, $params);

    wp_send_json($response);
  }

  public function register_rest_routes()
  {
    register_rest_route('supafaya/v1', '/events', [
      'methods' => 'GET',
      'callback' => [$this, 'rest_get_events'],
      'permission_callback' => '__return_true'
    ]);

    register_rest_route('supafaya/v1', '/events/(?P<id>\w+)', [
      'methods' => 'GET',
      'callback' => [$this, 'rest_get_event'],
      'permission_callback' => '__return_true'
    ]);
  }

  public function rest_get_events($request)
  {
    $default_org_id = get_option('supafaya_organization_id', '');
    
    $organization_id = $request->get_param('organization_id') ?? $default_org_id;
    $limit = $request->get_param('limit') ?? 10;
    $filter = $request->get_param('filter') ?? 'upcoming';

    if (empty($organization_id)) {
      return new \WP_Error('missing_param', 'Organization ID is not configured. Please set it in the Supafaya Tickets settings.', ['status' => 400]);
    }

    $response = $this->event_service->getEventsByOrganization($organization_id, [
      'limit' => $limit,
      'filter' => $filter,
      'include_private' => true
    ]);

    return rest_ensure_response($response);
  }

  public function rest_get_event($request)
  {
    $event_id = $request->get_param('id');

    $response = $this->event_service->getEventById($event_id);

    if (!$response['success']) {
      return new \WP_Error('api_error', $response['message'] ?? 'Unknown error', ['status' => $response['status'] ?? 500]);
    }

    return rest_ensure_response($response['data']);
  }

  public function ajax_get_user_items()
  {
    if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'supafaya-tickets-nonce')) {
      wp_send_json([
        'success' => false,
        'message' => 'Security verification failed'
      ]);
      return;
    }

    $event_id = sanitize_text_field($_GET['event_id'] ?? '');

    if (empty($event_id)) {
      wp_send_json([
        'success' => false,
        'message' => 'Event ID is required'
      ]);
      return;
    }

    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $firebase_token = isset($headers['X-Firebase-Token']) ? $headers['X-Firebase-Token'] : null;

    if (!$firebase_token) {
      wp_send_json([
        'success' => false,
        'message' => 'Authentication required'
      ]);
      return;
    }

    $this->api_client->setToken($firebase_token);

    $api_url = '/events/' . $event_id . '/user-items';
    
    $response = $this->api_client->get($api_url);

    if (!$response['success']) {
      wp_send_json([
        'success' => false,
        'message' => $response['message'] ?? 'Failed to fetch user items'
      ]);
      return;
    }

    $responseData = $response['data']['data'] ?? [];
    $tickets = $responseData['tickets'] ?? [];
    $addons = $responseData['addons'] ?? [];

    $formatted_items = [];

    foreach ($tickets as $ticket) {
      $formatted_items[] = [
        'id' => $ticket['id'] ?? '',
        'name' => $ticket['name'] ?? $ticket['ticket_type'] ?? 'Ticket',
        'ticket_type' => $ticket['ticket_type'] ?? '',
        'description' => $ticket['description'] ?? '',
        'price' => $ticket['price'] ?? 0,
        'currency' => $ticket['currency'] ?? 'PHP',
        'is_free' => $ticket['is_free'] ?? ($ticket['price'] == 0),
        'type' => 'ticket',
        'quantity' => $ticket['quantity'] ?? 1,
        'purchase_date' => $ticket['created_at'] ?? $ticket['purchased_date'] ?? '',
        'status' => $ticket['status'] ?? 'active',
        'qr_code' => $ticket['qr_code'] ?? '',
        'ticket_ref' => $ticket['ticket_ref'] ?? '',
        'valid_until' => $ticket['valid_until'] ?? '',
        'ticket_id' => $ticket['ticket_id'] ?? '',
        'consumed' => $ticket['consumed'] ?? false,
        'updated_at' => $ticket['updated_at'] ?? ''
      ];
    }

    foreach ($addons as $addon) {
      $formatted_items[] = [
        'id' => $addon['id'] ?? $addon['addonId'] ?? '',
        'title' => $addon['title'] ?? 'Add-on Item',
        'description' => $addon['description'] ?? '',
        'price' => $addon['price'] ?? 0,
        'type' => 'addon',
        'quantity' => $addon['quantity'] ?? 1,
        'purchase_date' => $addon['created_at'] ?? '',
        'status' => $addon['status'] ?? 'active',
        'refunded' => $addon['refunded'] ?? false,
        'organizerId' => $addon['organizerId'] ?? null,
        'sold' => $addon['sold'] ?? 0,
        'addon_ref' => $addon['addon_ref'] ?? ''
      ];
    }

    wp_send_json([
      'success' => true,
      'data' => $formatted_items
    ]);
  }
}