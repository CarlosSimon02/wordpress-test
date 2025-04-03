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

    // Apply API token to all requests
    add_action('init', [$this, 'setup_api_token']);

    // Register shortcodes
    add_shortcode('supafaya_events', [$this, 'events_shortcode']);
    add_shortcode('supafaya_event', [$this, 'event_shortcode']);

    // Register AJAX handlers
    add_action('wp_ajax_supafaya_load_events', [$this, 'ajax_load_events']);
    add_action('wp_ajax_nopriv_supafaya_load_events', [$this, 'ajax_load_events']);

    // Register REST API endpoints
    add_action('rest_api_init', [$this, 'register_rest_routes']);
  }

  public function getEventService(): EventService
  {
    return $this->event_service;
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
   * Shortcode for displaying events
   */
  public function events_shortcode($atts)
  {
    $atts = shortcode_atts([
      'organization_id' => '',
      'limit' => 10,
      'filter' => 'upcoming',
      'template' => 'grid' // grid, list, calendar
    ], $atts);

    if (empty($atts['organization_id'])) {
      return '<p>Error: Organization ID is required</p>';
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

  /**
   * Shortcode for displaying a single event
   */
  public function event_shortcode($atts)
  {
    $atts = shortcode_atts([
      'event_id' => '',
      'template' => 'default'
    ], $atts);

    // If event_id is not provided in shortcode, check URL
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

    // Enable debug mode for admins
    if (isset($_GET['debug']) && current_user_can('manage_options')) {
      echo '<div style="background: #f1f1f1; padding: 10px; margin-bottom: 20px; border-left: 4px solid #0073aa;">
              <p>Debug Mode: ON</p>
              <p>Event ID: ' . esc_html($atts['event_id']) . '</p>
            </div>';
    }

    ob_start();
    include SUPAFAYA_PLUGIN_DIR . 'templates/event-' . $atts['template'] . '.php';
    return ob_get_clean();
  }

  /**
   * AJAX handler for loading events
   */
  public function ajax_load_events()
  {
    $organization_id = sanitize_text_field($_POST['organization_id'] ?? '');
    $limit = intval($_POST['limit'] ?? 10);
    $filter = sanitize_text_field($_POST['filter'] ?? 'upcoming');
    $next_cursor = sanitize_text_field($_POST['next_cursor'] ?? '');

    if (empty($organization_id)) {
      wp_send_json([
        'success' => false,
        'message' => 'Organization ID is required'
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

  /**
   * Register WordPress REST API routes
   */
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

  /**
   * REST API handler for getting events
   */
  public function rest_get_events($request)
  {
    $organization_id = $request->get_param('organization_id');
    $limit = $request->get_param('limit') ?? 10;
    $filter = $request->get_param('filter') ?? 'upcoming';

    if (empty($organization_id)) {
      return new \WP_Error('missing_param', 'Organization ID is required', ['status' => 400]);
    }

    $response = $this->event_service->getEventsByOrganization($organization_id, [
      'limit' => $limit,
      'filter' => $filter,
      'include_private' => true
    ]);

    if (!$response['success']) {
      return new \WP_Error('api_error', $response['message'] ?? 'Unknown error', ['status' => $response['status'] ?? 500]);
    }

    return rest_ensure_response($response['data']);
  }

  /**
   * REST API handler for getting a single event
   */
  public function rest_get_event($request)
  {
    $event_id = $request->get_param('id');

    $response = $this->event_service->getEventById($event_id);

    if (!$response['success']) {
      return new \WP_Error('api_error', $response['message'] ?? 'Unknown error', ['status' => $response['status'] ?? 500]);
    }

    return rest_ensure_response($response['data']);
  }
}