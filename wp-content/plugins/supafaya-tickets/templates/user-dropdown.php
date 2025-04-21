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
        <div class="user-dropdown-toggle-two">
            <div class="user-dropdown-avatar">
                <img id="user-avatar-img" src="" alt="User avatar" style="display: none;">
                <span id="user-initials"></span>
            </div>
            <span class="dropdown-arrow">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </span>
        </div>
        
        <div class="user-dropdown-menu">
            <a href="<?php echo esc_url($profile_url); ?>" class="dropdown-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                <span>Profile</span>
            </a>
            <a href="#" class="dropdown-item firebase-logout-button">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                <span>Log Out</span>
            </a>
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
                    document.querySelector('.auth-logged-in').style.display = 'flex';
                    document.querySelector('.auth-logged-out').style.display = 'none';
                    
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
                        initials.style.display = 'flex';
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
        var toggleElement = e.target.closest('.user-dropdown-toggle-two');
        if (toggleElement) {
            e.preventDefault();
            e.stopPropagation();
            
            var dropdown = document.querySelector('.supafaya-user-dropdown');
            if (dropdown.classList.contains('open')) {
                dropdown.classList.remove('open');
            } else {
                dropdown.classList.add('open');
                // Add closing animation class
                dropdown.classList.add('opening');
                // Remove after animation completes
                setTimeout(function() {
                    dropdown.classList.remove('opening');
                }, 300);
            }
        } else if (!e.target.closest('.supafaya-user-dropdown')) {
            // Close dropdown when clicking outside
            var dropdown = document.querySelector('.supafaya-user-dropdown');
            if (dropdown.classList.contains('open')) {
                dropdown.classList.add('closing');
                // Remove open class after animation starts
                setTimeout(function() {
                    dropdown.classList.remove('open');
                    // Remove closing animation class after it completes
                    setTimeout(function() {
                        dropdown.classList.remove('closing');
                    }, 300);
                }, 10);
            }
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
/* User Dropdown Styles with CSS Variables for Theming */
:root {
    --supafaya-primary: #8800FF;
    --supafaya-primary-hover: #7000cc;
    --supafaya-text: #333;
    --supafaya-text-secondary: #666;
    --supafaya-bg-light: #ffffff;
    --supafaya-bg-hover: #f5f5f5;
    --supafaya-border: rgba(0, 0, 0, 0.1);
    --supafaya-avatar-bg: #e0e0e0;
    --supafaya-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    --supafaya-transition: 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Dark mode support */
:root {
    --supafaya-primary: #9933FF;
    --supafaya-primary-hover: #aa66ff;
    --supafaya-text: #e0e0e0;
    --supafaya-text-secondary: #b0b0b0;
    --supafaya-bg-light: #222222;
    --supafaya-bg-hover: #333333;
    --supafaya-border: rgba(255, 255, 255, 0.1);
    --supafaya-avatar-bg: #444444;
    --supafaya-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.supafaya-user-dropdown {
    position: relative;
    display: inline-block;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    color: var(--supafaya-text);
    width: fit-content;
}

.debug-message {
    color: var(--supafaya-text-secondary);
    font-size: 12px;
    padding: 5px;
    border-radius: 4px;
    background-color: var(--supafaya-bg-hover);
    margin-bottom: 10px;
}

.supafaya-login-button {
    display: inline-block;
    padding: 10px 18px;
    background-color: var(--supafaya-primary);
    color: white !important;
    border-radius: 8px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: all var(--supafaya-transition);
    border: none;
    box-shadow: 0 2px 10px rgba(136, 0, 255, 0.2);
}

.supafaya-login-button:hover {
    background-color: var(--supafaya-primary-hover);
    box-shadow: 0 4px 15px rgba(136, 0, 255, 0.3);
    transform: translateY(-1px);
    color: white !important;
    text-decoration: none;
}

.auth-logged-in {
    display: flex;
    flex-direction: column;
}

.user-dropdown-toggle-two {
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    padding: 8px;
    border-radius: 50%;
    transition: all var(--supafaya-transition);
    background-color: transparent;
    position: relative;
    width: fit;
}

.user-dropdown-toggle-two:hover {
    background-color: var(--supafaya-bg-hover);
}

.user-dropdown-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    overflow: hidden;
    background-color: var(--supafaya-avatar-bg);
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid var(--supafaya-border);
    transition: all var(--supafaya-transition);
}

.user-dropdown-toggle-two:hover .user-dropdown-avatar {
    transform: scale(1.05);
}

.user-dropdown-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

#user-initials {
    font-size: 16px;
    font-weight: 600;
    color: var(--supafaya-text);
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
}

.dropdown-arrow {
    position: absolute;
    bottom: -2px;
    right: -2px;
    background-color: var(--supafaya-primary);
    color: white;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    transition: transform var(--supafaya-transition);
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    border: 2px solid var(--supafaya-bg-light);
}

.dropdown-arrow svg {
    width: 10px;
    height: 10px;
    stroke: white;
    transition: transform var(--supafaya-transition);
}

.supafaya-user-dropdown.open .dropdown-arrow svg {
    transform: rotate(180deg);
}

.user-dropdown-menu {
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    background-color: var(--supafaya-bg-light);
    min-width: 180px;
    box-shadow: var(--supafaya-shadow);
    border-radius: 12px;
    overflow: hidden;
    z-index: 1000;
    display: none;
    border: 1px solid var(--supafaya-border);
    transform-origin: top right;
    transition: all var(--supafaya-transition);
    opacity: 0;
    transform: scale(0.95);
}

.supafaya-user-dropdown.open .user-dropdown-menu {
    display: block;
    opacity: 1;
    transform: scale(1);
}

.supafaya-user-dropdown.opening .user-dropdown-menu {
    animation: dropdownOpen 0.3s forwards;
}

.supafaya-user-dropdown.closing .user-dropdown-menu {
    animation: dropdownClose 0.3s forwards;
}

@keyframes dropdownOpen {
    0% { opacity: 0; transform: scale(0.95); }
    100% { opacity: 1; transform: scale(1); }
}

@keyframes dropdownClose {
    0% { opacity: 1; transform: scale(1); }
    100% { opacity: 0; transform: scale(0.95); }
}

.dropdown-item {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    text-decoration: none;
    color: var(--supafaya-text) !important;
    font-size: 14px;
    transition: background-color var(--supafaya-transition);
    font-weight: 500;
}

.dropdown-item svg {
    margin-right: 12px;
    stroke: var(--supafaya-text-secondary);
    transition: all var(--supafaya-transition);
}

.dropdown-item:hover {
    background-color: var(--supafaya-bg-hover);
    text-decoration: none;
}

.dropdown-item:hover svg {
    stroke: var(--supafaya-primary);
    transform: translateX(2px);
}

.dropdown-item:not(:last-child) {
    border-bottom: 1px solid var(--supafaya-border);
}

.dropdown-item.firebase-logout-button svg {
    stroke: #f87171;
}

.dropdown-item.firebase-logout-button:hover svg {
    stroke: #ef4444;
}
</style> 