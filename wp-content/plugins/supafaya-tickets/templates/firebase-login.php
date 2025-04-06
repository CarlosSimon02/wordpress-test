<div class="supafaya-auth-container">
    <!-- Logged In State -->
    <div class="auth-logged-in" style="display: none;">
        <div class="auth-card">
            <div class="auth-header">
                <div class="user-avatar-container">
                    <div class="user-avatar">
                        <img id="user-avatar-img" src="" alt="User Avatar" style="display: none;">
                        <span id="user-initials"></span>
                    </div>
                </div>
                <h2>Welcome back, <span id="user-name">User</span></h2>
                <p class="auth-subtext">You're successfully logged in</p>
            </div>
            
            <div class="auth-actions">
                <?php if (isset($_COOKIE['supafaya_checkout_redirect']) && !empty($_COOKIE['supafaya_checkout_redirect'])): ?>
                    <a href="<?php echo esc_url($_COOKIE['supafaya_checkout_redirect']); ?>" class="auth-button primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <path d="M16 10a4 4 0 0 1-8 0"></path>
                        </svg>
                        Continue to Checkout
                    </a>
                <?php else: ?>
                    <a href="<?php echo esc_url(home_url()); ?>" class="auth-button primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                        Return to Home
                    </a>
                <?php endif; ?>
                
                <button class="auth-button secondary firebase-logout-button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    Sign Out
                </button>
            </div>
        </div>
    </div>
    
    <!-- Logged Out State -->
    <div class="auth-logged-out">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="8.5" cy="7" r="4"></circle>
                        <polyline points="17 11 19 13 23 9"></polyline>
                    </svg>
                </div>
                <h2>Welcome back</h2>
                <p class="auth-subtext">Sign in to access your account</p>
            </div>
            
            <!-- Firebase Auth Container -->
            <div id="firebaseui-auth-container"></div>
            
            <!-- Loading State -->
            <div id="firebase-loading" class="auth-loading" style="display: none;">
                <div class="loading-spinner">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="2" x2="12" y2="6"></line>
                        <line x1="12" y1="18" x2="12" y2="22"></line>
                        <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line>
                        <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line>
                        <line x1="2" y1="12" x2="6" y2="12"></line>
                        <line x1="18" y1="12" x2="22" y2="12"></line>
                        <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line>
                        <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line>
                    </svg>
                </div>
                <p>Authenticating...</p>
            </div>
            
            <!-- Error Message -->
            <div id="firebase-error" class="auth-error" style="display: none;"></div>
            
            <!-- <div class="auth-footer">
                <p>Don't have an account? <a href="#" class="auth-link" id="show-signup">Sign up</a></p>
            </div> -->
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Handle auth state changes
    function handleAuthState(user) {
        if (user) {
            // User is signed in
            $('.auth-logged-in').show();
            $('.auth-logged-out').hide();
            
            // Update user info
            $('#user-name').text(user.displayName || user.email.split('@')[0]);
            
            // Update avatar
            if (user.photoURL) {
                $('#user-avatar-img').attr('src', user.photoURL).show();
                $('#user-initials').hide();
            } else {
                $('#user-avatar-img').hide();
                const initials = user.displayName ? 
                    user.displayName.split(' ').map(n => n[0]).join('') : 
                    user.email[0].toUpperCase();
                $('#user-initials').text(initials).show();
            }
        } else {
            // User is signed out
            $('.auth-logged-in').hide();
            $('.auth-logged-out').show();
        }
    }
    
    // Initialize Firebase auth state listener
    document.addEventListener('DOMContentLoaded', function() {
        const checkFirebaseInit = setInterval(function() {
            if (window.firebase && window.firebase.auth) {
                clearInterval(checkFirebaseInit);
                
                firebase.auth().onAuthStateChanged(function(user) {
                    handleAuthState(user);
                });
            }
        }, 100);
    });
    
    // Handle logout
    $('.firebase-logout-button').on('click', function(e) {
        e.preventDefault();
        firebase.auth().signOut().then(function() {
            // Clear any redirect cookies
            document.cookie = 'supafaya_checkout_redirect=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
        });
    });
});
</script>