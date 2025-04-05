<?php
namespace SupafayaTickets\Helpers;

class UserDropdown {
    private $login_page_url;
    
    public function __construct() {
        // Add our user dropdown to the header menu
        add_filter('wp_nav_menu_items', [$this, 'add_user_dropdown_to_menu'], 10, 2);
        
        // Set login page URL
        $this->login_page_url = $this->get_login_page_url();
        
        // Add script to handle dropdown functionality
        add_action('wp_footer', [$this, 'add_dropdown_script']);
    }
    
    /**
     * Get login page URL
     */
    private function get_login_page_url() {
        // Look for a page with our login shortcode
        $login_pages = get_posts([
            'post_type' => 'page',
            'posts_per_page' => 1,
            's' => '[supafaya_enhanced_login_form]'
        ]);
        
        if (!empty($login_pages)) {
            return get_permalink($login_pages[0]->ID);
        }
        
        // Fallback to WordPress login
        return wp_login_url();
    }
    
    /**
     * Add user dropdown to menu
     */
    public function add_user_dropdown_to_menu($items, $args) {
        // Only add to primary menu
        if ($args->theme_location !== 'primary') {
            return $items;
        }
        
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            $avatar = get_avatar($user->ID, 32);
            
            // If no avatar, use first letter of display name
            if (!$avatar) {
                $initial = substr($user->display_name, 0, 1);
                $avatar = '<div class="user-avatar">' . esc_html($initial) . '</div>';
            }
            
            $dropdown = '
                <li class="menu-item user-dropdown">
                    <button class="user-dropdown-toggle">
                        ' . $avatar . '
                        <span class="user-name">' . esc_html($user->display_name) . '</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </button>
                    <div class="user-dropdown-content">
                        <a href="' . esc_url(home_url('/my-account/')) . '">My Account</a>
                        <a href="' . esc_url(home_url('/my-tickets/')) . '">My Tickets</a>
                        <a href="' . esc_url(wp_logout_url(home_url())) . '" class="logout">Logout</a>
                    </div>
                </li>
            ';
        } else {
            // Login button for non-logged-in users
            $dropdown = '
                <li class="menu-item">
                    <a href="' . esc_url($this->login_page_url) . '" class="login-button-header">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                        Login
                    </a>
                </li>
            ';
        }
        
        return $items . $dropdown;
    }
    
    /**
     * Add dropdown script
     */
    public function add_dropdown_script() {
        ?>
        <script>
            (function($) {
                // Toggle dropdown
                $('.user-dropdown-toggle').on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const $content = $(this).siblings('.user-dropdown-content');
                    $content.toggleClass('active');
                });
                
                // Close dropdown when clicking outside
                $(document).on('click', function(e) {
                    if (!$(e.target).closest('.user-dropdown').length) {
                        $('.user-dropdown-content').removeClass('active');
                    }
                });
                
                // Make sure the dropdown doesn't close when clicking inside it
                $('.user-dropdown-content').on('click', function(e) {
                    e.stopPropagation();
                });
            })(jQuery);
        </script>
        <?php
    }
} 