<?php
// Get login and profile page URLs
$login_url = get_option('supafaya_login_page_url', home_url());
$profile_url = get_option('supafaya_profile_page_url', home_url());
?>
<div class="supafaya-user-dropdown">
    <!-- Login button (shown when logged out) -->
    <div class="auth-logged-out" style="display: none;">
        <a href="<?php echo esc_url($login_url); ?>" class="<?php echo esc_attr($atts['button_class']); ?>">
            <?php echo esc_html($atts['button_text']); ?>
        </a>
    </div>
    
    <!-- User dropdown (shown when logged in) -->
    <div class="auth-logged-in" style="display: none;">
        <div class="user-dropdown-toggle">
            <div class="user-avatar">
                <img id="user-avatar-img" src="" alt="User avatar" style="display: none;">
                <span id="user-initials"></span>
            </div>
            <span id="user-name"></span>
            <span class="dropdown-arrow">▼</span>
        </div>
        
        <div class="user-dropdown-menu">
            <a href="<?php echo esc_url($profile_url); ?>" class="dropdown-item">Profile</a>
            <a href="#" class="dropdown-item firebase-logout-button">Log Out</a>
        </div>
    </div>
</div> 