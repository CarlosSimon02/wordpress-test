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
    <!-- Login button (shown when logged out) -->
    <div class="auth-logged-out">
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

<!-- Embed Firebase libraries directly if they're not already loaded -->
<script src="https://www.gstatic.com/firebasejs/9.6.0/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.6.0/firebase-auth-compat.js"></script>

<script>
(function() {
    // Check if supafayaFirebase is already defined from wp_localize_script
    if (typeof window.supafayaFirebase === 'undefined') {
        // Create a local configuration
        window.supafayaFirebase = {
            apiKey: "<?php echo esc_js($firebase_api_key); ?>",
            authDomain: "<?php echo esc_js($firebase_auth_domain); ?>",
            projectId: "<?php echo esc_js($firebase_project_id); ?>",
            ajaxUrl: "<?php echo esc_js(admin_url('admin-ajax.php')); ?>",
            nonce: "<?php echo esc_js(wp_create_nonce('supafaya-firebase-nonce')); ?>",
            redirectUrl: "<?php echo esc_js(home_url()); ?>",
            profileUrl: "<?php echo esc_js($profile_url); ?>",
            siteUrl: "<?php echo esc_js(site_url()); ?>"
        };
    }
    
    // Initialize Firebase if not already initialized
    var firebaseApp;
    try {
        if (typeof firebase === 'undefined' || !firebase.apps.length) {
            const firebaseConfig = {
                apiKey: window.supafayaFirebase.apiKey,
                authDomain: window.supafayaFirebase.authDomain,
                projectId: window.supafayaFirebase.projectId
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
                
                firebase.auth().signOut().then(function() {
                    window.location.href = "<?php echo esc_js(home_url()); ?>";
                }).catch(function(error) {
                    console.error('Sign out error:', error);
                });
            }
        });
        
    } catch (error) {
        console.error('Firebase initialization error:', error);
    }
})();
</script>

<style>
/* User Dropdown Styles */
.supafaya-user-dropdown {
    position: relative;
    display: inline-block;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}

.supafaya-login-button {
    display: inline-block;
    padding: 8px 16px;
    background-color: #4285f4;
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

.user-avatar {
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

.user-avatar img {
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

.firebase-logout-button {
    color: #f44336 !important;
}
</style> 