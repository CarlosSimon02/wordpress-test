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
                    'ticketId' => $ticket_id,
                    'quantity' => $ticket['quantity'],
                    'unitPrice' => $ticket['price'] ?? 0,
                    'ticketType' => $ticket['type'] ?? 'regular',
                    'name' => $ticket['name'] ?? '',
                    'description' => $ticket['description'] ?? ''
                ];
            }
        }
        
        if (!empty($cart_data['addons'])) {
            foreach ($cart_data['addons'] as $addon_id => $addon) {
                $addons[] = [
                    'addonId' => $addon_id,
                    'quantity' => $addon['quantity'],
                    'unitPrice' => $addon['price'] ?? 0,
                    'name' => $addon['name'] ?? '',
                    'ticketId' => $addon['ticket_id'] ?? null
                ];
            }
        }
        
        // Log the extracted tickets and addons for debugging
        error_log('AJAX Proof of Payment: Extracted tickets: ' . json_encode($tickets));
        error_log('AJAX Proof of Payment: Extracted addons: ' . json_encode($addons));
        
        // Step 1: First initialize a payment to get a payment ID
        $payment_data = [
            'eventId' => $event_id,
            'tickets' => $tickets,
            'addons' => $addons ?? [],
            'customerDetails' => [
                'name' => $sanitized_data['name'],
                'email' => $sanitized_data['email'],
                'phone' => $sanitized_data['phone']
            ],
            'paymentMethod' => 'manual_bank_transfer', // New payment method enum value
            'paymentRedirectUrls' => [
                'success' => site_url('/payment-success'),
                'failed' => site_url('/payment-failed'),
                'cancel' => site_url('/payment-cancelled')
            ]
        ];
        
        error_log('AJAX Proof of Payment: Initializing payment request: ' . json_encode($payment_data));
        
        // Initialize the payment to get a payment ID
        $payment_response = $this->api_client->post('/payments/initialize', $payment_data);
        
        if (!$payment_response['success']) {
            error_log('AJAX Proof of Payment: Payment initialization failed: ' . json_encode($payment_response));
            wp_send_json([
                'success' => false,
                'message' => isset($payment_response['data']['message']) 
                    ? $payment_response['data']['message'] 
                    : 'Failed to initialize payment'
            ]);
            return;
        }
        
        error_log('AJAX Proof of Payment: Payment initialized successfully: ' . json_encode($payment_response));
        
        // Extract the payment ID from the response
        if (!isset($payment_response['data']['id'])) {
            error_log('AJAX Proof of Payment: Payment ID not found in response');
            wp_send_json([
                'success' => false,
                'message' => 'Payment ID not found in response'
            ]);
            return;
        }
        
        $payment_id = $payment_response['data']['id'];
        error_log('AJAX Proof of Payment: Payment ID: ' . $payment_id);
        
        // Step 2: Prepare the image file for upload
        // Read the file into a base64 encoded string
        $file_path = $_FILES['receipt']['tmp_name'];
        $file_type = $_FILES['receipt']['type'];
        $file_extension = strtolower(pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION));
        
        // Get file contents as base64
        $file_data = base64_encode(file_get_contents($file_path));
        
        // Step 3: Upload the proof of payment with the payment ID
        $proof_data = [
            'paymentId' => $payment_id,
            'imageBase64' => $file_data,
            'fileExtension' => $file_extension,
            'description' => $sanitized_data['notes'] . ' | Bank: ' . $sanitized_data['bank'] . 
                             ' | Reference: ' . $sanitized_data['reference'] . 
                             ' | Date: ' . $sanitized_data['date']
        ];
        
        error_log('AJAX Proof of Payment: Uploading proof of payment');
        
        // Call the new manual payment proof upload endpoint
        $proof_response = $this->api_client->post('/payments/manual/proof-upload', $proof_data);
        
        if (!$proof_response['success']) {
            error_log('AJAX Proof of Payment: Proof upload failed: ' . json_encode($proof_response));
            wp_send_json([
                'success' => false,
                'message' => isset($proof_response['data']['message'])
                    ? $proof_response['data']['message']
                    : 'Failed to upload proof of payment'
            ]);
            return;
        }
        
        error_log('AJAX Proof of Payment: Proof upload successful: ' . json_encode($proof_response));
        
        // Return success response to client
        wp_send_json([
            'success' => true,
            'message' => 'Your proof of payment has been submitted successfully. The event organizer will review your payment and confirm your tickets.',
            'data' => [
                'paymentId' => $payment_id,
                'status' => $proof_response['data']['status'] ?? 'PENDING_APPROVAL'
            ]
        ]);
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