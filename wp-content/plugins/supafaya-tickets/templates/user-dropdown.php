<?php
// Get login and profile page URLs
$login_url = get_option('supafaya_login_page_url', home_url());
$profile_url = get_option('supafaya_profile_page_url', home_url());

// Get Firebase configuration
$firebase_api_key = get_option('supafaya_firebase_api_key', '');
$firebase_auth_domain = get_option('supafaya_firebase_auth_domain', '');
$firebase_project_id = get_option('supafaya_firebase_project_id', '');
?>
<div class="supafaya-user-dropdown">
    <!-- Debug message - remove in production -->
    <div class="debug-message" style="display: none;">
        Waiting for Firebase auth state...
    </div>
    
    <!-- Login button (shown when logged out) -->
    <div class="auth-logged-out" style="display: none;">
        <a href="<?php echo esc_url($login_url); ?>" class="<?php echo esc_attr($atts['button_class']); ?>">
            <?php echo esc_html($atts['button_text']); ?>
        </a>
    </div>
    
    <!-- User dropdown (shown when logged in) -->
    <div class="auth-logged-in" style="display: none;">
        <div class="user-dropdown-toggle">
            <div class="user-dropdown-avatar">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show debug message initially
    document.querySelector('.supafaya-user-dropdown .debug-message').style.display = 'block';
    
    // Function to initialize Firebase auth listener
    function initializeAuthListener() {
        // Check if Firebase has been loaded
        if (typeof firebase === 'undefined') {
            console.error('Firebase not loaded. Make sure Firebase scripts are included.');
            setTimeout(initializeAuthListener, 500);
            return;
        }
        
        // Check if Firebase auth is available
        if (!firebase.auth) {
            console.error('Firebase Auth not loaded. Make sure Firebase Auth script is included.');
            setTimeout(initializeAuthListener, 500);
            return;
        }
        
        // Hide debug message
        document.querySelector('.supafaya-user-dropdown .debug-message').style.display = 'none';
        
        // Firebase is loaded, initialize if needed
        var firebaseApp;
        try {
            if (!firebase.apps.length) {
                // Ensure we have proper configuration
                const firebaseConfig = {
                    apiKey: "<?php echo esc_js($firebase_api_key); ?>",
                    authDomain: "<?php echo esc_js($firebase_auth_domain); ?>",
                    projectId: "<?php echo esc_js($firebase_project_id); ?>"
                };
                firebaseApp = firebase.initializeApp(firebaseConfig);
            } else {
                firebaseApp = firebase.app();
            }
            
            // Set up auth state listener
            firebase.auth().onAuthStateChanged(function(user) {
                if (user) {
                    // User is signed in
                    document.querySelector('.auth-logged-in').style.display = 'block';
                    document.querySelector('.auth-logged-out').style.display = 'none';
                    
                    // Set user name
                    var userNameElement = document.querySelector('#user-name');
                    if (userNameElement) {
                        userNameElement.textContent = user.displayName || user.email.split('@')[0];
                    }
                    
                    // Set avatar
                    var avatarImg = document.querySelector('#user-avatar-img');
                    var initials = document.querySelector('#user-initials');
                    
                    if (user.photoURL) {
                        avatarImg.src = user.photoURL;
                        avatarImg.style.display = 'block';
                        initials.style.display = 'none';
                    } else {
                        avatarImg.style.display = 'none';
                        initials.textContent = user.displayName ? 
                            user.displayName.split(' ').map(n => n[0]).join('') : 
                            user.email[0].toUpperCase();
                        initials.style.display = 'block';
                    }
                } else {
                    // User is signed out
                    document.querySelector('.auth-logged-in').style.display = 'none';
                    document.querySelector('.auth-logged-out').style.display = 'block';
                }
            });
            
        } catch (error) {
            console.error('Firebase initialization error:', error);
            document.querySelector('.auth-logged-out').style.display = 'block';
        }
    }
    
    // Start the initialization process
    initializeAuthListener();
    
    // Handle dropdown toggle
    document.addEventListener('click', function(e) {
        var toggleElement = e.target.closest('.user-dropdown-toggle');
        if (toggleElement) {
            e.preventDefault();
            e.stopPropagation();
            document.querySelector('.supafaya-user-dropdown').classList.toggle('open');
        } else if (!e.target.closest('.supafaya-user-dropdown')) {
            // Close dropdown when clicking outside
            document.querySelector('.supafaya-user-dropdown').classList.remove('open');
        }
    });
    
    // Handle logout
    document.addEventListener('click', function(e) {
        var logoutBtn = e.target.closest('.firebase-logout-button');
        if (logoutBtn) {
            e.preventDefault();
            
            if (typeof firebase !== 'undefined' && firebase.auth) {
                firebase.auth().signOut().then(function() {
                    window.location.href = "<?php echo esc_js(home_url()); ?>";
                }).catch(function(error) {
                    console.error('Sign out error:', error);
                });
            }
        }
    });
});
</script>

<style>
/* User Dropdown Styles */
.supafaya-user-dropdown {
    position: relative;
    display: inline-block;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}

.debug-message {
    color: #666;
    font-size: 12px;
    padding: 5px;
}

.supafaya-login-button {
    display: inline-block;
    padding: 8px 16px;
    background-color: #8800FF;
    color: white !important;
    border-radius: 4px;
    text-decoration: none;
    font-size: 14px;
    transition: background-color 0.3s;
}

.supafaya-login-button:hover {
    background-color: #3367d6;
    color: white !important;
    text-decoration: none;
}

.user-dropdown-toggle {
    display: flex;
    align-items: center;
    cursor: pointer;
    padding: 6px 10px;
    border-radius: 4px;
    transition: background-color 0.3s;
}

.user-dropdown-toggle:hover {
    background-color: rgba(0, 0, 0, 0.05);
}

.user-dropdown-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    overflow: hidden;
    margin-right: 8px;
    background-color: #e0e0e0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.user-dropdown-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

#user-initials {
    font-size: 14px;
    font-weight: bold;
    color: #555;
}

#user-name {
    font-size: 14px;
    max-width: 120px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-right: 4px;
}

.dropdown-arrow {
    font-size: 10px;
    margin-left: 4px;
    transition: transform 0.3s;
}

.user-dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background-color: white;
    min-width: 160px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    border-radius: 4px;
    overflow: hidden;
    z-index: 1000;
    display: none;
}

.supafaya-user-dropdown.open .user-dropdown-menu {
    display: block;
}

.supafaya-user-dropdown.open .dropdown-arrow {
    transform: rotate(180deg);
}

.dropdown-item {
    display: block;
    padding: 10px 15px;
    text-decoration: none;
    color: #333 !important;
    font-size: 14px;
    transition: background-color 0.3s;
}

.dropdown-item:hover {
    background-color: #f5f5f5;
    text-decoration: none;
}
</style> 