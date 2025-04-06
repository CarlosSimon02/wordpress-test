<?php

class Supafaya_Tickets_Admin_Settings {
    public function register_settings() {
        register_setting('supafaya_tickets_settings', 'supafaya_tickets_settings');

        // API Settings Section
        add_settings_section(
            'supafaya_tickets_api_section',
            'API Settings',
            array($this, 'api_section_callback'),
            'supafaya_tickets_settings'
        );

        // API URL Field
        add_settings_field(
            'api_url',
            'API URL',
            array($this, 'api_url_callback'),
            'supafaya_tickets_settings',
            'supafaya_tickets_api_section'
        );

        // API Key Field
        add_settings_field(
            'api_key',
            'API Key',
            array($this, 'api_key_callback'),
            'supafaya_tickets_settings',
            'supafaya_tickets_api_section'
        );

        // Organization ID Field
        add_settings_field(
            'organization_id',
            'Organization ID',
            array($this, 'organization_id_callback'),
            'supafaya_tickets_settings',
            'supafaya_tickets_api_section'
        );

        // Firebase Settings Section
        add_settings_section(
            'supafaya_tickets_firebase_section',
            'Firebase Settings',
            array($this, 'firebase_section_callback'),
            'supafaya_tickets_settings'
        );

        // Firebase API Key Field
        add_settings_field(
            'firebase_api_key',
            'Firebase API Key',
            array($this, 'firebase_api_key_callback'),
            'supafaya_tickets_settings',
            'supafaya_tickets_firebase_section'
        );

        // Firebase Auth Domain Field
        add_settings_field(
            'firebase_auth_domain',
            'Firebase Auth Domain',
            array($this, 'firebase_auth_domain_callback'),
            'supafaya_tickets_settings',
            'supafaya_tickets_firebase_section'
        );

        // Firebase Project ID Field
        add_settings_field(
            'firebase_project_id',
            'Firebase Project ID',
            array($this, 'firebase_project_id_callback'),
            'supafaya_tickets_settings',
            'supafaya_tickets_firebase_section'
        );

        // Firebase Storage Bucket Field
        add_settings_field(
            'firebase_storage_bucket',
            'Firebase Storage Bucket',
            array($this, 'firebase_storage_bucket_callback'),
            'supafaya_tickets_settings',
            'supafaya_tickets_firebase_section'
        );

        // Firebase Messaging Sender ID Field
        add_settings_field(
            'firebase_messaging_sender_id',
            'Firebase Messaging Sender ID',
            array($this, 'firebase_messaging_sender_id_callback'),
            'supafaya_tickets_settings',
            'supafaya_tickets_firebase_section'
        );

        // Firebase App ID Field
        add_settings_field(
            'firebase_app_id',
            'Firebase App ID',
            array($this, 'firebase_app_id_callback'),
            'supafaya_tickets_settings',
            'supafaya_tickets_firebase_section'
        );

        // Firebase Measurement ID Field
        add_settings_field(
            'firebase_measurement_id',
            'Firebase Measurement ID',
            array($this, 'firebase_measurement_id_callback'),
            'supafaya_tickets_settings',
            'supafaya_tickets_firebase_section'
        );

        // Page Settings Section
        add_settings_section(
            'supafaya_tickets_page_section',
            'Page Settings',
            array($this, 'page_section_callback'),
            'supafaya_tickets_settings'
        );

        // Login Page URL Field
        add_settings_field(
            'login_page_url',
            'Login Page URL',
            array($this, 'login_page_url_callback'),
            'supafaya_tickets_settings',
            'supafaya_tickets_page_section'
        );

        // Profile Page URL Field
        add_settings_field(
            'profile_page_url',
            'Profile Page URL',
            array($this, 'profile_page_url_callback'),
            'supafaya_tickets_settings',
            'supafaya_tickets_page_section'
        );
        
        // Payment Result Page URL Field
        add_settings_field(
            'payment_result_page_url',
            'Payment Result Page URL',
            array($this, 'payment_result_page_url_callback'),
            'supafaya_tickets_settings',
            'supafaya_tickets_page_section'
        );
    }

    // Page Settings Section Callback
    public function page_section_callback() {
        echo '<p>Configure page URLs for the plugin.</p>';
    }

    // Login Page URL Callback
    public function login_page_url_callback() {
        $options = get_option('supafaya_tickets_settings');
        $value = isset($options['login_page_url']) ? $options['login_page_url'] : '';
        echo '<input type="text" id="login_page_url" name="supafaya_tickets_settings[login_page_url]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">URL of the login page. Example: /login or https://yoursite.com/login</p>';
    }

    // Profile Page URL Callback
    public function profile_page_url_callback() {
        $options = get_option('supafaya_tickets_settings');
        $value = isset($options['profile_page_url']) ? $options['profile_page_url'] : '';
        echo '<input type="text" id="profile_page_url" name="supafaya_tickets_settings[profile_page_url]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">URL of the user profile page. Example: /profile or https://yoursite.com/profile</p>';
    }
    
    // Payment Result Page URL Callback
    public function payment_result_page_url_callback() {
        $options = get_option('supafaya_tickets_settings');
        $value = isset($options['payment_result_page_url']) ? $options['payment_result_page_url'] : '';
        echo '<input type="text" id="payment_result_page_url" name="supafaya_tickets_settings[payment_result_page_url]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">URL of the payment result page that will show success/failure messages. Example: /payment-result or https://yoursite.com/payment-result</p>';
        echo '<p class="description">Create a page with the [supafaya_payment_result] shortcode to display payment results.</p>';
    }
} 