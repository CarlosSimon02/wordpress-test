<?php
/**
 * Plugin Name: Supafaya Tickets
 * Description: Integration with Supafaya Ticketing API
 * Version: 1.0.0
 * Author: Your Name
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('SUPAFAYA_VERSION', '1.0.0');
define('SUPAFAYA_API_URL', 'http://host.docker.internal:4001/api/v1');
define('SUPAFAYA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SUPAFAYA_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoload classes
spl_autoload_register(function ($class) {
    $prefix = 'SupafayaTickets\\';
    $base_dir = SUPAFAYA_PLUGIN_DIR . 'includes/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Initialize plugin
require_once SUPAFAYA_PLUGIN_DIR . 'includes/Plugin.php';
$supafaya_tickets = new SupafayaTickets\Plugin();
$supafaya_tickets->init();