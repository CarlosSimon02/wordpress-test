<?php
/**
 * Firebase Login Template
 * 
 * Displays a login form using Firebase UI
 */
?>
<div class="supafaya-login-container">
    <!-- This container will be shown when user is logged in via Firebase -->
    <div class="firebase-user-logged-in" style="display: none;">
        <div class="login-already-logged-in">
            <h2>Welcome, <span class="firebase-user-name">User</span></h2>
            <p>You are already logged in.</p>
            
            <div class="firebase-user-photo"></div>
            
            <?php 
            $checkout_redirect = isset($_COOKIE['supafaya_checkout_redirect']) ? $_COOKIE['supafaya_checkout_redirect'] : '';
            if (!empty($checkout_redirect)): 
            ?>
                <a href="<?php echo esc_url($checkout_redirect); ?>" class="button return-to-checkout">Return to Checkout</a>
            <?php else: ?>
                <a href="<?php echo esc_url(home_url()); ?>" class="button return-to-home">Return to Home</a>
            <?php endif; ?>
            
            <button class="firebase-logout-button button">Log Out</button>
        </div>
    </div>
    
    <!-- This container will be shown when user is not logged in -->
    <div class="firebase-user-not-logged-in">
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
    </div>
</div>

<script>
    // This will be run when the page loads to handle initial auth state
    document.addEventListener('DOMContentLoaded', function() {
        // This code will run after firebase-auth.js has initialized Firebase
        // We'll check for the global supafayaFirebase object periodically
        const checkFirebaseInit = setInterval(function() {
            if (window.firebase && window.firebase.auth) {
                clearInterval(checkFirebaseInit);
                
                // Listen for auth state changes
                firebase.auth().onAuthStateChanged(function(user) {
                    if (user) {
                        // User is signed in
                        document.querySelector('.firebase-user-logged-in').style.display = 'block';
                        document.querySelector('.firebase-user-not-logged-in').style.display = 'none';
                        
                        // Update user info
                        document.querySelector('.firebase-user-name').textContent = user.displayName || user.email;
                        
                        // Add user photo if available
                        if (user.photoURL) {
                            document.querySelector('.firebase-user-photo').innerHTML = 
                                `<img src="${user.photoURL}" alt="Profile Photo" class="user-avatar">`;
                        }
                    } else {
                        // User is not signed in
                        document.querySelector('.firebase-user-logged-in').style.display = 'none';
                        document.querySelector('.firebase-user-not-logged-in').style.display = 'block';
                    }
                });
            }
        }, 100);
    });
</script> 