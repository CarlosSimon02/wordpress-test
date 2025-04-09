<div class="supafaya-auth-login-container">
    <!-- Initial Loading State -->
    <div class="login-auth-loading-state" style="display: flex;">
        <div class="login-auth-card">
            <div class="login-auth-header">
                <div class="login-auth-logo">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="8.5" cy="7" r="4"></circle>
                        <polyline points="17 11 19 13 23 9"></polyline>
                    </svg>
                </div>
                <h2>Loading</h2>
                <p class="login-auth-subtext">Please wait while we check your authentication status...</p>
            </div>
            
            <div class="login-auth-loading" style="display: flex; justify-content: center; padding: 20px;">
                <div class="login-loading-spinner">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="login-rotating-spinner">
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
            </div>
        </div>
    </div>

    <!-- Logged In State -->
    <div class="login-auth-logged-in" style="display: none;">
        <div class="login-auth-card">
            <div class="login-auth-header">
                <div class="login-user-avatar-container">
                    <div class="login-user-avatar">
                        <img id="login-user-avatar-img" src="" alt="User Avatar" style="display: none;">
                        <span id="login-user-initials"></span>
                    </div>
                </div>
                <h2>Welcome back, <span id="login-user-name">User</span></h2>
                <p class="login-auth-subtext">You're successfully logged in</p>
            </div>
            
            <div class="login-auth-actions">
                <?php if (isset($_COOKIE['supafaya_checkout_redirect']) && !empty($_COOKIE['supafaya_checkout_redirect'])): ?>
                    <a href="<?php echo esc_url($_COOKIE['supafaya_checkout_redirect']); ?>" class="login-auth-button login-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <path d="M16 10a4 4 0 0 1-8 0"></path>
                        </svg>
                        Continue to Checkout
                    </a>
                <?php else: ?>
                    <a href="<?php echo esc_url(home_url()); ?>" class="login-auth-button login-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                        Return to Home
                    </a>
                <?php endif; ?>
                
                <button class="login-auth-button login-secondary login-firebase-logout-button">
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
    <div class="login-auth-logged-out" style="display: none;">
        <div class="login-auth-card">
            <div class="login-auth-header">
                <div class="login-auth-logo">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="8.5" cy="7" r="4"></circle>
                        <polyline points="17 11 19 13 23 9"></polyline>
                    </svg>
                </div>
                <h2>Welcome back</h2>
                <p class="login-auth-subtext">Sign in to access your account</p>
            </div>
            
            <!-- Firebase Auth Container -->
            <div id="firebaseui-auth-container"></div>
            
            <!-- Loading State -->
            <div id="firebase-loading" class="login-auth-loading" style="display: none;">
                <div class="login-loading-spinner">
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
            <div id="firebase-error" class="login-auth-error" style="display: none;"></div>
            
            <!-- <div class="login-auth-footer">
                <p>Don't have an account? <a href="#" class="login-auth-link" id="show-signup">Sign up</a></p>
            </div> -->
        </div>
    </div>
</div>

<style>
.login-rotating-spinner {
    animation: login-spin 1s linear infinite;
}

@keyframes login-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Debug flag
    const debug = true;
    
    // Debug logger
    function logDebug(message, data) {
        if (debug) {
            if (data) {
                console.log('[Firebase Login]', message, data);
            } else {
                console.log('[Firebase Login]', message);
            }
        }
    }
    
    // Handle auth state changes
    function handleAuthState(user) {
        logDebug('Auth state changed', user);
        
        // Hide loading state first
        $('.login-auth-loading-state').hide();
        
        if (user) {
            // User is signed in
            $('.login-auth-logged-in').show();
            $('.login-auth-logged-out').hide();
            
            logDebug('User is logged in', {
                displayName: user.displayName,
                email: user.email,
                photoURL: user.photoURL
            });
            
            // Update user info - check for displayName and fallback to email
            const displayName = user.displayName || (user.email ? user.email.split('@')[0] : 'User');
            $('#login-user-name').text(displayName);
            logDebug('Set display name', displayName);
            
            // Update avatar
            if (user.photoURL) {
                logDebug('User has photo URL', user.photoURL);
                $('#login-user-avatar-img')
                    .attr('src', user.photoURL)
                    .on('load', function() {
                        $(this).show();
                        $('#login-user-initials').hide();
                        logDebug('User photo loaded successfully');
                    })
                    .on('error', function() {
                        logDebug('Failed to load user photo, showing initials instead');
                        $(this).hide();
                        showUserInitials(user);
                    });
            } else {
                logDebug('No photo URL, showing initials');
                $('#login-user-avatar-img').hide();
                showUserInitials(user);
            }
            
            // Check for user details in Firebase cookie as a backup
            if (!user.displayName && !user.photoURL) {
                logDebug('Missing user data, checking cookie');
                tryLoadUserFromCookie();
            }
        } else {
            // User is signed out
            logDebug('User is logged out');
            $('.login-auth-logged-in').hide();
            $('.login-auth-logged-out').show();
        }
    }
    
    // Show user initials
    function showUserInitials(user) {
        let initials;
        
        if (user.displayName) {
            // Get initials from display name
            initials = user.displayName
                .split(' ')
                .map(n => n[0])
                .join('')
                .toUpperCase();
        } else if (user.email) {
            // Get first letter of email
            initials = user.email[0].toUpperCase();
        } else {
            // Fallback
            initials = 'U';
        }
        
        $('#login-user-initials').text(initials).show();
        logDebug('Set user initials', initials);
    }
    
    // Try to load user data from cookie
    function tryLoadUserFromCookie() {
        try {
            const firebaseUserCookie = getCookie('firebase_user');
            logDebug('Firebase user cookie', firebaseUserCookie);
            
            if (firebaseUserCookie) {
                const userData = JSON.parse(firebaseUserCookie);
                logDebug('Parsed user data from cookie', userData);
                
                // Update display name if needed
                if (userData.displayName && $('#login-user-name').text() === 'User') {
                    $('#login-user-name').text(userData.displayName);
                    logDebug('Updated display name from cookie', userData.displayName);
                }
                
                // Update photo if needed
                if (userData.photoURL && $('#login-user-avatar-img').is(':hidden')) {
                    $('#login-user-avatar-img')
                        .attr('src', userData.photoURL)
                        .on('load', function() {
                            $(this).show();
                            $('#login-user-initials').hide();
                            logDebug('Loaded user photo from cookie');
                        })
                        .on('error', function() {
                            logDebug('Failed to load photo from cookie');
                        });
                }
            }
        } catch (e) {
            logDebug('Error loading user data from cookie', e);
        }
    }
    
    // Helper function to get a cookie value
    function getCookie(name) {
        const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return match ? decodeURIComponent(match[2]) : null;
    }
    
    // Initialize Firebase auth state listener
    function initFirebaseAuth() {
        logDebug('Initializing Firebase Auth');
        
        // First show loading state
        $('.login-auth-loading-state').show();
        $('.login-auth-logged-in, .login-auth-logged-out').hide();
        
        const checkFirebaseInit = setInterval(function() {
            if (window.firebase && window.firebase.auth) {
                logDebug('Firebase Auth is available, setting up auth state listener');
                clearInterval(checkFirebaseInit);
                
                firebase.auth().onAuthStateChanged(function(user) {
                    handleAuthState(user);
                });
            }
        }, 100);
        
        // Set a timeout to show the logged-out view if Firebase doesn't initialize
        setTimeout(function() {
            if ($('.login-auth-loading-state').is(':visible')) {
                logDebug('Timed out waiting for Firebase, showing logged out state');
                $('.login-auth-loading-state').hide();
                $('.login-auth-logged-out').show();
            }
        }, 5000);
    }
    
    // Handle logout
    $('.login-firebase-logout-button').on('click', function(e) {
        e.preventDefault();
        logDebug('Logout button clicked');
        
        // Show loading state
        $('.login-auth-logged-in').hide();
        $('.login-auth-loading-state').show();
        
        firebase.auth().signOut().then(function() {
            logDebug('User signed out successfully');
            // Clear any redirect cookies
            document.cookie = 'supafaya_checkout_redirect=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
        }).catch(function(error) {
            logDebug('Error signing out', error);
            // Show error message
            alert('Error signing out: ' + error.message);
            // Reshow logged in state
            $('.login-auth-loading-state').hide();
            $('.login-auth-logged-in').show();
        });
    });
    
    // Start initialization
    initFirebaseAuth();
});
</script>