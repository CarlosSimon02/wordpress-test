<?php
namespace SupafayaTickets\Controllers;

use SupafayaTickets\Api\ApiClient;
use SupafayaTickets\Api\TicketService;

class PaymentProofController {
    private $api_client;
    private $ticket_service;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->api_client = new ApiClient();
        $this->ticket_service = new TicketService($this->api_client);
    }
    
    /**
     * AJAX handler for submitting proof of payment
     */
    public function ajax_submit_proof_of_payment() {
        error_log('AJAX Proof of Payment: Request received');
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'supafaya-tickets-nonce')) {
            error_log('AJAX Proof of Payment: Nonce verification failed');
            wp_send_json(array(
                'success' => false,
                'message' => 'Security verification failed'
            ));
            return;
        }
        
        // Log all received form data for debugging (excluding file contents)
        $log_data = $_POST;
        if (isset($log_data['cart_data'])) {
            $log_data['cart_data'] = '(cart data present)';
        }
        error_log('AJAX Proof of Payment: Received form data: ' . json_encode($log_data));
        
        // Check for required fields
        $required_fields = ['name', 'email', 'phone', 'reference', 'bank', 'amount', 'date'];
        $missing_fields = [];
        
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                $missing_fields[] = $field;
                error_log('AJAX Proof of Payment: Missing required field - ' . $field);
            }
        }
        
        if (!empty($missing_fields)) {
            wp_send_json(array(
                'success' => false,
                'message' => 'Please fill all required fields: ' . implode(', ', $missing_fields)
            ));
            return;
        }
        
        // Check if file was uploaded
        if (!isset($_FILES['receipt'])) {
            error_log('AJAX Proof of Payment: No receipt file in request');
            wp_send_json(array(
                'success' => false,
                'message' => 'Please upload a receipt or proof of payment (file missing from request)'
            ));
            return;
        }
        
        if ($_FILES['receipt']['error'] !== UPLOAD_ERR_OK) {
            $error_message = 'Unknown error';
            switch ($_FILES['receipt']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    $error_message = 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $error_message = 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $error_message = 'The uploaded file was only partially uploaded';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $error_message = 'No file was uploaded';
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $error_message = 'Missing a temporary folder';
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $error_message = 'Failed to write file to disk';
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $error_message = 'A PHP extension stopped the file upload';
                    break;
            }
            
            error_log('AJAX Proof of Payment: File upload error: ' . $error_message);
            error_log('AJAX Proof of Payment: File details: ' . json_encode($_FILES['receipt']));
            
            wp_send_json(array(
                'success' => false,
                'message' => 'File upload error: ' . $error_message
            ));
            return;
        }
        
        // Get event ID
        $event_id = isset($_POST['event_id']) ? sanitize_text_field($_POST['event_id']) : '';
        if (empty($event_id)) {
            error_log('AJAX Proof of Payment: Missing event ID');
            wp_send_json(array(
                'success' => false,
                'message' => 'Event ID is required'
            ));
            return;
        }
        
        // Get cart data
        $cart_data = isset($_POST['cart_data']) ? json_decode(stripslashes($_POST['cart_data']), true) : null;
        if (empty($cart_data) || empty($cart_data['tickets']) && empty($cart_data['addons'])) {
            error_log('AJAX Proof of Payment: Empty cart data');
            wp_send_json(array(
                'success' => false,
                'message' => 'No items in cart'
            ));
            return;
        }
        
        // Sanitize input data
        $sanitized_data = array(
            'name' => sanitize_text_field($_POST['name']),
            'email' => sanitize_email($_POST['email']),
            'phone' => sanitize_text_field($_POST['phone']),
            'reference' => sanitize_text_field($_POST['reference']),
            'bank' => sanitize_text_field($_POST['bank']),
            'amount' => floatval($_POST['amount']),
            'date' => sanitize_text_field($_POST['date']),
            'notes' => isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : ''
        );
        
        // Handle file upload
        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['basedir'] . '/supafaya-receipts/';
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            wp_mkdir_p($target_dir);
        }
        
        // Generate a unique filename
        $file_extension = pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION);
        $unique_filename = 'receipt_' . $sanitized_data['reference'] . '_' . uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $unique_filename;
        
        // Check file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
        $file_type = wp_check_filetype($_FILES['receipt']['name']);
        
        if (!in_array($_FILES['receipt']['type'], $allowed_types) || empty($file_type['ext'])) {
            error_log('AJAX Proof of Payment: Invalid file type - ' . $_FILES['receipt']['type']);
            wp_send_json(array(
                'success' => false,
                'message' => 'Invalid file type. Please upload a JPG, PNG, or PDF file.'
            ));
            return;
        }
        
        // Move uploaded file
        if (!move_uploaded_file($_FILES['receipt']['tmp_name'], $target_file)) {
            error_log('AJAX Proof of Payment: Failed to move uploaded file');
            wp_send_json(array(
                'success' => false,
                'message' => 'Failed to upload receipt file. Please try again.'
            ));
            return;
        }
        
        // Get file URL
        $file_url = $upload_dir['baseurl'] . '/supafaya-receipts/' . $unique_filename;
        
        // Get Firebase token for authentication
        $firebase_token = $this->get_firebase_token();
        if (!$firebase_token) {
            error_log('AJAX Proof of Payment: Failed to get Firebase token');
            wp_send_json(array(
                'success' => false,
                'message' => 'Authentication required'
            ));
            return;
        }
        
        // Set token for API requests
        $this->api_client->setToken($firebase_token);
        
        // Format tickets for the API
        $tickets = [];
        $addons = [];
        
        if (!empty($cart_data['tickets'])) {
            foreach ($cart_data['tickets'] as $ticket_id => $ticket) {
                $tickets[] = [
                    'ticket_id' => $ticket_id,
                    'quantity' => $ticket['quantity']
                ];
            }
        }
        
        if (!empty($cart_data['addons'])) {
            foreach ($cart_data['addons'] as $addon_id => $addon) {
                $addons[] = [
                    'addon_id' => $addon_id,
                    'quantity' => $addon['quantity'],
                    'ticket_id' => $addon['ticket_id'] ?? null
                ];
            }
        }
        
        // Log the extracted tickets and addons for debugging
        error_log('AJAX Proof of Payment: Extracted tickets: ' . json_encode($tickets));
        error_log('AJAX Proof of Payment: Extracted addons: ' . json_encode($addons));
        
        // Prepare payment data
        $payment_data = [
            'event_id' => $event_id,
            'tickets' => $tickets,
            'addons' => $addons,
            'user_data' => [
                'name' => $sanitized_data['name'],
                'email' => $sanitized_data['email'],
                'phone' => $sanitized_data['phone']
            ],
            'payment_method' => 'PROOF_OF_PAYMENT',
            'payment_details' => [
                'reference' => $sanitized_data['reference'],
                'bank' => $sanitized_data['bank'],
                'amount' => $sanitized_data['amount'],
                'date' => $sanitized_data['date'],
                'notes' => $sanitized_data['notes'],
                'receipt_url' => $file_url
            ],
            'payment_redirect_urls' => [
                'success' => site_url('/payment-success'),
                'failed' => site_url('/payment-failed'),
                'cancel' => site_url('/payment-cancelled')
            ]
        ];
        
        error_log('AJAX Proof of Payment: Formatted payment data: ' . json_encode($payment_data));
        
        // Call API to submit proof of payment
        $response = $this->ticket_service->purchaseTicket($payment_data);
        
        error_log('AJAX Proof of Payment: API response received, success: ' . ($response['success'] ? 'true' : 'false'));
        error_log('AJAX Proof of Payment: API response: ' . json_encode($response));
        
        if ($response['success']) {
            wp_send_json([
                'success' => true,
                'message' => 'Proof of payment submitted successfully',
                'data' => [
                    'redirect_url' => isset($response['data']['redirectUrl']) ? $response['data']['redirectUrl'] : null
                ]
            ]);
        } else {
            wp_send_json([
                'success' => false,
                'message' => isset($response['message']) ? $response['message'] : 'Failed to submit proof of payment'
            ]);
        }
    }
    
    /**
     * Helper to get Firebase token from various sources
     */
    private function get_firebase_token() {
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
} 