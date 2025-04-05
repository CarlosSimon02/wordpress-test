<?php
/**
 * Firebase Login Template
 * 
 * Displays a login form using Firebase UI
 */
?>
<div class="supafaya-login-container">
    <?php if (is_user_logged_in()): ?>
        <?php 
            $user = wp_get_current_user();
            $redirect_url = get_permalink();
            $checkout_redirect = isset($_COOKIE['supafaya_checkout_redirect']) ? $_COOKIE['supafaya_checkout_redirect'] : '';
            
            if (empty($checkout_redirect) && !empty($_SERVER['HTTP_REFERER'])) {
                $checkout_redirect = $_SERVER['HTTP_REFERER'];
            }
        ?>
        
        <div class="login-already-logged-in">
            <h2>Welcome, <?php echo esc_html($user->display_name); ?></h2>
            <p>You are already logged in.</p>
            
            <?php if (!empty($checkout_redirect)): ?>
                <a href="<?php echo esc_url($checkout_redirect); ?>" class="button return-to-checkout">Return to Checkout</a>
            <?php else: ?>
                <a href="<?php echo esc_url(home_url()); ?>" class="button return-to-home">Return to Home</a>
            <?php endif; ?>
        </div>
        
    <?php else: ?>
        <div class="login-form-container">
            <div class="login-header">
                <h2>Log In to Your Account</h2>
                <p>Sign in to continue with your purchase</p>
            </div>
            
            <!-- Firebase UI auth container -->
            <div id="firebaseui-auth-container"></div>
            
            <!-- Loading indicator -->
            <div id="firebase-loading" style="text-align: center; display: none;">
                <p>Please wait...</p>
            </div>
            
            <!-- Error message container -->
            <div id="firebase-error" class="error-message" style="display: none;"></div>
        </div>
    <?php endif; ?>
</div>

<script>
    // This script is intentionally left empty
    // The actual Firebase initialization and UI rendering is handled in firebase-auth.js
</script> 