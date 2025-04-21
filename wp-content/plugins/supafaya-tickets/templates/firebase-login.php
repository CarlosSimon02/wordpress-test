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
                <?php 
                $return_url = '';
                
                // Check for return_url in URL parameters
                if (isset($_GET['return_url']) && !empty($_GET['return_url'])) {
                    $return_url = esc_url($_GET['return_url']);
                }
                // Fallback to checkout redirect cookie if available
                else if (isset($_COOKIE['supafaya_checkout_redirect']) && !empty($_COOKIE['supafaya_checkout_redirect'])) {
                    $return_url = esc_url($_COOKIE['supafaya_checkout_redirect']);
                }
                
                if (!empty($return_url)): 
                ?>
                    <a href="<?php echo $return_url; ?>" class="login-auth-button login-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15 18l-6-6 6-6"></path>
                        </svg>
                        Continue to Previous Page
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
    function handleAuthState(user) {
        $('.login-auth-loading-state').hide();
        
        if (user) {
            $('.login-auth-logged-in').show();
            $('.login-auth-logged-out').hide();
            
            const displayName = user.displayName || (user.email ? user.email.split('@')[0] : 'User');
            $('#login-user-name').text(displayName);
            
            if (user.photoURL) {
                $('#login-user-avatar-img')
                    .attr('src', user.photoURL)
                    .on('load', function() {
                        $(this).show();
                        $('#login-user-initials').hide();
                    })
                    .on('error', function() {
                        $(this).hide();
                        showUserInitials(user);
                    });
            } else {
                $('#login-user-avatar-img').hide();
                showUserInitials(user);
            }
            
            if (!user.displayName && !user.photoURL) {
                tryLoadUserFromCookie();
            }
        } else {
            $('.login-auth-logged-in').hide();
            $('.login-auth-logged-out').show();
        }
    }
    
    function showUserInitials(user) {
        let initials;
        
        if (user.displayName) {
            initials = user.displayName
                .split(' ')
                .map(n => n[0])
                .join('')
                .toUpperCase();
        } else if (user.email) {
            initials = user.email[0].toUpperCase();
        } else {
            initials = 'U';
        }
        
        $('#login-user-initials').text(initials).show();
    }
    
    function tryLoadUserFromCookie() {
        try {
            const firebaseUserCookie = getCookie('firebase_user');
            
            if (firebaseUserCookie) {
                const userData = JSON.parse(firebaseUserCookie);
                
                if (userData.displayName && $('#login-user-name').text() === 'User') {
                    $('#login-user-name').text(userData.displayName);
                }
                
                if (userData.photoURL && $('#login-user-avatar-img').is(':hidden')) {
                    $('#login-user-avatar-img')
                        .attr('src', userData.photoURL)
                        .on('load', function() {
                            $(this).show();
                            $('#login-user-initials').hide();
                        })
                        .on('error', function() {
                            // Silent fail
                        });
                }
            }
        } catch (e) {
            // Silent fail
        }
    }
    
    function getCookie(name) {
        const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return match ? decodeURIComponent(match[2]) : null;
    }
    
    function initFirebaseAuth() {
        $('.login-auth-loading-state').show();
        $('.login-auth-logged-in, .login-auth-logged-out').hide();
        
        const checkFirebaseInit = setInterval(function() {
            if (window.firebase && window.firebase.auth) {
                clearInterval(checkFirebaseInit);
                
                firebase.auth().onAuthStateChanged(function(user) {
                    handleAuthState(user);
                });
            }
        }, 100);
        
        setTimeout(function() {
            if ($('.login-auth-loading-state').is(':visible')) {
                $('.login-auth-loading-state').hide();
                $('.login-auth-logged-out').show();
            }
        }, 5000);
    }
    
    $('.login-firebase-logout-button').on('click', function(e) {
        e.preventDefault();
        
        $('.login-auth-logged-in').hide();
        $('.login-auth-loading-state').show();
        
        firebase.auth().signOut().then(function() {
            document.cookie = 'supafaya_checkout_redirect=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
        }).catch(function(error) {
            alert('Error signing out: ' + error.message);
            $('.login-auth-loading-state').hide();
            $('.login-auth-logged-in').show();
        });
    });
    
    initFirebaseAuth();
});
</script>