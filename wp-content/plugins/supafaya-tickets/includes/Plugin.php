<?php
namespace SupafayaTickets;

use SupafayaTickets\Controllers\AuthController;
use SupafayaTickets\Controllers\EventController;
use SupafayaTickets\Controllers\TicketController;

class Plugin {
    private $auth_controller;
    private $event_controller;
    private $ticket_controller;
    
    public function init() {
        // Initialize controllers
        $this->auth_controller = new AuthController();
        $this->event_controller = new EventController();
        $this->ticket_controller = new TicketController();
        
        // Register assets
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        
        // Add admin menu
        add_action('admin_menu', [$this, 'add_admin_menu']);
        
        // Add settings
        add_action('admin_init', [$this, 'register_settings']);
        
        // Register page templates
        add_filter('theme_page_templates', [$this, 'register_page_templates']);
        add_filter('template_include', [$this, 'load_page_template']);
    }
    
    public function register_assets() {
        wp_enqueue_style(
            'supafaya-tickets-style',
            SUPAFAYA_PLUGIN_URL . 'assets/css/supafaya-tickets.css',
            [],
            '1.0.0'
        );
        
        wp_enqueue_script(
            'supafaya-tickets-script',
            SUPAFAYA_PLUGIN_URL . 'assets/js/supafaya-tickets.js',
            ['jquery'],
            '1.0.0',
            true
        );
        
        wp_localize_script('supafaya-tickets-script', 'supafayaTickets', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('supafaya-tickets-nonce')
        ]);
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Supafaya Tickets',
            'Supafaya Tickets',
            'manage_options',
            'supafaya-tickets',
            [$this, 'render_admin_page'],
            'dashicons-tickets',
            20
        );
        
        add_submenu_page(
            'supafaya-tickets',
            'Settings',
            'Settings',
            'manage_options',
            'supafaya-tickets-settings',
            [$this, 'render_settings_page']
        );
    }
    
    public function register_settings() {
        register_setting('supafaya_tickets_options', 'supafaya_api_url');
        register_setting('supafaya_tickets_options', 'supafaya_organization_id');
        
        add_settings_section(
            'supafaya_tickets_main',
            'Main Settings',
            function() {
                echo '<p>Configure the connection to your Supafaya Ticketing API</p>';
            },
            'supafaya-tickets-settings'
        );
        
        add_settings_field(
            'supafaya_api_url',
            'API URL',
            function() {
                $value = get_option('supafaya_api_url', SUPAFAYA_API_URL);
                echo '<input type="text" name="supafaya_api_url" value="' . esc_attr($value) . '" class="regular-text">';
            },
            'supafaya-tickets-settings',
            'supafaya_tickets_main'
        );
        
        add_settings_field(
            'supafaya_organization_id',
            'Default Organization ID',
            function() {
                $value = get_option('supafaya_organization_id', '');
                echo '<input type="text" name="supafaya_organization_id" value="' . esc_attr($value) . '" class="regular-text">';
            },
            'supafaya-tickets-settings',
            'supafaya_tickets_main'
        );
    }
    
    public function render_admin_page() {
        include SUPAFAYA_PLUGIN_DIR . 'templates/admin.php';
    }
    
    public function render_settings_page() {
        include SUPAFAYA_PLUGIN_DIR . 'templates/settings.php';
    }
    
    /**
     * Register custom page templates
     */
    public function register_page_templates($templates) {
        $templates[SUPAFAYA_PLUGIN_DIR . 'templates/event-page-template.php'] = 'Supafaya Event Detail';
        return $templates;
    }
    
    /**
     * Load the custom page template when selected
     */
    public function load_page_template($template) {
        global $post;
        
        if (!$post) {
            return $template;
        }
        
        // Event detail page using query parameter
        if (isset($_GET['event_id']) && get_post_meta($post->ID, '_wp_page_template', true) == SUPAFAYA_PLUGIN_DIR . 'templates/event-page-template.php') {
            $file = SUPAFAYA_PLUGIN_DIR . 'templates/event-page-template.php';
            if (file_exists($file)) {
                return $file;
            }
        }
        
        return $template;
    }
}