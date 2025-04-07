<?php
namespace SupafayaTickets\Services;

/**
 * Firebase Storage Service
 * 
 * Handles direct uploads to Firebase Storage using the REST API
 */
class FirebaseStorageService {
    private $api_key;
    private $storage_bucket;
    private $project_id;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->api_key = get_option('supafaya_firebase_api_key', '');
        $this->storage_bucket = get_option('supafaya_firebase_storage_bucket', 'supafaya-tix-dev.appspot.com');
        $this->project_id = get_option('supafaya_firebase_project_id', '');
    }
    
    /**
     * Upload file to Firebase Storage via the REST API
     * 
     * @param string $firebase_token The Firebase Auth token
     * @param string $file_path Path to the file to upload
     * @param string $file_name Name of the file in Firebase Storage
     * @param string $content_type MIME type of the file
     * @return array Result with success status and file URL
     */
    public function upload_file($firebase_token, $file_path, $file_name, $content_type) {
        error_log('Firebase Storage: Uploading file: ' . $file_name);
        
        if (empty($this->storage_bucket)) {
            error_log('Firebase Storage: Storage bucket not configured');
            return [
                'success' => false,
                'message' => 'Firebase Storage bucket not configured'
            ];
        }
        
        if (empty($firebase_token)) {
            error_log('Firebase Storage: Firebase token not provided');
            return [
                'success' => false,
                'message' => 'Firebase authentication required'
            ];
        }
        
        // Ensure file exists
        if (!file_exists($file_path)) {
            error_log('Firebase Storage: File not found: ' . $file_path);
            return [
                'success' => false,
                'message' => 'File not found'
            ];
        }
        
        // Prepare the upload URL
        $encoded_file_name = str_replace('/', '%2F', $file_name);
        $upload_url = "https://firebasestorage.googleapis.com/v0/b/{$this->storage_bucket}/o?name={$encoded_file_name}";
        
        error_log('Firebase Storage: Upload URL: ' . $upload_url);
        
        // Get file content
        $file_content = file_get_contents($file_path);
        
        // Upload to Firebase Storage
        $args = [
            'method' => 'POST',
            'timeout' => 60,
            'redirection' => 5,
            'httpversion' => '1.1',
            'headers' => [
                'Content-Type' => $content_type,
                'Authorization' => 'Firebase ' . $firebase_token
            ],
            'body' => $file_content,
            'sslverify' => false
        ];
        
        $response = wp_remote_post($upload_url, $args);
        
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log('Firebase Storage: Upload failed with error: ' . $error_message);
            return [
                'success' => false,
                'message' => 'Upload failed: ' . $error_message
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($status_code < 200 || $status_code >= 300) {
            error_log('Firebase Storage: Upload failed with status code: ' . $status_code);
            error_log('Firebase Storage: Response body: ' . $body);
            return [
                'success' => false,
                'message' => 'Upload failed with status code: ' . $status_code
            ];
        }
        
        if (!$data || !isset($data['name'])) {
            error_log('Firebase Storage: Invalid response from Firebase Storage: ' . $body);
            return [
                'success' => false,
                'message' => 'Invalid response from Firebase Storage'
            ];
        }
        
        // Set the file to be publicly accessible
        $make_public_url = "https://firebasestorage.googleapis.com/v0/b/{$this->storage_bucket}/o/{$encoded_file_name}?updateMask=contentDisposition";
        
        $public_args = [
            'method' => 'PATCH',
            'timeout' => 30,
            'redirection' => 5,
            'httpversion' => '1.1',
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Firebase ' . $firebase_token
            ],
            'body' => json_encode([
                'contentDisposition' => 'inline'
            ]),
            'sslverify' => false
        ];
        
        $public_response = wp_remote_post($make_public_url, $public_args);
        
        if (is_wp_error($public_response)) {
            $error_message = $public_response->get_error_message();
            error_log('Firebase Storage: Failed to make file public: ' . $error_message);
            // Not returning error here as the file was uploaded successfully
        }
        
        // Generate the download URL
        $download_url = "https://firebasestorage.googleapis.com/v0/b/{$this->storage_bucket}/o/{$encoded_file_name}?alt=media";
        
        error_log('Firebase Storage: File uploaded successfully. URL: ' . $download_url);
        
        return [
            'success' => true,
            'file_url' => $download_url,
            'name' => $data['name'],
            'bucket' => $this->storage_bucket
        ];
    }
} 