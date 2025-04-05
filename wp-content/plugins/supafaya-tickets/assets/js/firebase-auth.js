/**
 * Firebase Authentication Handler
 */
(function($) {
    'use strict';

    // Global reference to the current user
    let currentUser = null;

    // Initialize Firebase with the configuration passed from WordPress
    const firebaseConfig = {
        apiKey: supafayaFirebase.apiKey,
        authDomain: supafayaFirebase.authDomain,
        projectId: supafayaFirebase.projectId
    };

    // Check if Firebase config is valid
    if (!firebaseConfig.apiKey || !firebaseConfig.authDomain || !firebaseConfig.projectId) {
        console.error('Firebase configuration is missing. Please check your plugin settings.');
        $('#firebaseui-auth-container').html('<div class="error-message">Firebase configuration is missing. Please contact the administrator.</div>');
        return;
    }

    // Initialize Firebase
    const firebaseApp = firebase.initializeApp(firebaseConfig);
    const auth = firebase.auth();
    
    // Initialize the FirebaseUI Widget using Firebase
    const ui = new firebaseui.auth.AuthUI(auth);

    // FirebaseUI configuration
    const uiConfig = {
        callbacks: {
            signInSuccessWithAuthResult: function(authResult, redirectUrl) {
                // Don't redirect automatically - we'll do it via AJAX
                handleSignIn(authResult.user);
                return false;
            },
            uiShown: function() {
                // The widget is rendered, hide the loader
                $('#firebase-loading').hide();
            }
        },
        // Will use popup for IDP Sign In rather than redirect
        signInFlow: 'popup',
        signInOptions: [
            // Google sign in
            {
                provider: firebase.auth.GoogleAuthProvider.PROVIDER_ID,
                customParameters: {
                    // Forces account selection even when one account is available
                    prompt: 'select_account'
                }
            },
            // Email/password sign in
            {
                provider: firebase.auth.EmailAuthProvider.PROVIDER_ID,
                requireDisplayName: true
            }
        ],
        // Terms of service url/privacy policy url.
        tosUrl: '#',
        privacyPolicyUrl: '#'
    };

    // Wait for document ready
    $(document).ready(function() {
        // Check URL for redirect_to parameter
        const urlParams = new URLSearchParams(window.location.search);
        const redirectTo = urlParams.get('redirect_to');
        if (redirectTo) {
            // Store the redirect URL for later
            localStorage.setItem('supafaya_redirect', redirectTo);
        }

        // Check if we're on the login page
        if ($('#firebaseui-auth-container').length > 0) {
            // Start the FirebaseUI Auth
            ui.start('#firebaseui-auth-container', uiConfig);
        }

        // Listen for auth state changes
        auth.onAuthStateChanged(function(user) {
            if (user) {
                // User is signed in
                console.log('User is signed in:', user.email);
                setFirebaseUserCookie(user);
                
                // Update UI for logged-in state
                $('.supafaya-firebase-user-info').html(`
                    <div class="user-details">
                        ${user.photoURL ? `<img src="${user.photoURL}" class="user-avatar" />` : ''}
                        <span class="user-name">${user.displayName || user.email}</span>
                    </div>
                `);
                
                // Show logout container if present
                $('.supafaya-logout-container').show();
                
                // Store token in a cookie for server-side access
                updateFirebaseToken(user);
                
                // Add token to all AJAX requests
                setupAjaxTokenInterceptor(user);
            } else {
                // User is signed out
                console.log('User is signed out');
                // Clear cookies
                document.cookie = 'firebase_user=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
                document.cookie = 'firebase_user_token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
                
                // Update UI for logged-out state
                $('.supafaya-firebase-user-info').html('');
                
                // Hide logout container if present
                $('.supafaya-logout-container').hide();
            }
        });

        // Handle logout button
        $('.firebase-logout-button').on('click', function(e) {
            e.preventDefault();
            
            // Sign out from Firebase
            auth.signOut().then(function() {
                // Clear cookies
                document.cookie = 'firebase_user=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
                document.cookie = 'firebase_user_token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
                
                // Redirect to home
                window.location.href = supafayaFirebase.siteUrl;
            }).catch(function(error) {
                console.error('Sign Out Error', error);
            });
        });
    });

    // Setup AJAX interceptor to include Firebase token in all requests
    function setupAjaxTokenInterceptor(user) {
        $.ajaxSetup({
            beforeSend: function(xhr) {
                user.getIdToken(true).then(function(token) {
                    xhr.setRequestHeader('X-Firebase-Token', token);
                }).catch(function(error) {
                    console.error('Error getting Firebase token', error);
                });
            }
        });
    }
    
    // Update Firebase token in cookie
    function updateFirebaseToken(user) {
        user.getIdToken(true).then(function(token) {
            // Store in cookie for server-side
            setCookie('firebase_user_token', token, 1);
        }).catch(function(error) {
            console.error('Error getting token', error);
        });
    }

    // Handle user sign in - authenticate with WordPress backend
    function handleSignIn(user) {
        // Show loading
        $('#firebase-loading').show();
        $('#firebase-error').hide();

        // Get ID token
        user.getIdToken(true).then(function(idToken) {
            // Store token in cookie for server-side authentication
            setCookie('firebase_user_token', idToken, 1);
            
            // Send the token to the server
            $.ajax({
                url: supafayaFirebase.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'supafaya_firebase_auth',
                    nonce: supafayaFirebase.nonce,
                    token: idToken,
                    user: {
                        uid: user.uid,
                        email: user.email,
                        displayName: user.displayName,
                        photoURL: user.photoURL,
                        providerId: user.providerData[0]?.providerId || 'firebase'
                    }
                },
                success: function(response) {
                    if (response.success) {
                        // Check for stored redirect URL first
                        let redirectUrl = localStorage.getItem('supafaya_redirect');
                        if (redirectUrl) {
                            localStorage.removeItem('supafaya_redirect');
                        } else {
                            // Use the response redirect or default
                            redirectUrl = response.data.redirect || supafayaFirebase.redirectUrl;
                        }
                        
                        // Redirect
                        window.location.href = redirectUrl;
                    } else {
                        // Show error message
                        $('#firebase-error').html('Authentication failed: ' + (response.data || 'Unknown error')).show();
                        $('#firebase-loading').hide();
                    }
                },
                error: function() {
                    // Show error message
                    $('#firebase-error').html('Network error. Please try again.').show();
                    $('#firebase-loading').hide();
                }
            });
        }).catch(function(error) {
            // Show error message
            $('#firebase-error').html('Firebase token error: ' + error.message).show();
            $('#firebase-loading').hide();
        });
    }
    
    // Helper function to set a cookie
    function setCookie(name, value, days) {
        var expires = "";
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "")  + expires + "; path=/";
    }

    // Add global methods for other scripts to use
    window.supafayaFirebase = {
        ...window.supafayaFirebase,
        getCurrentUser: function() {
            return firebase.auth().currentUser;
        },
        getToken: function() {
            return new Promise((resolve, reject) => {
                const user = firebase.auth().currentUser;
                if (user) {
                    user.getIdToken(true).then(resolve).catch(reject);
                } else {
                    reject(new Error('No user is logged in'));
                }
            });
        },
        isLoggedIn: function() {
            return firebase.auth().currentUser !== null;
        }
    };

    // Add this function to set the user data in a cookie
    function setFirebaseUserCookie(user) {
        if (!user) return;
        
        // Create a simplified user object with essential data
        const userData = {
            uid: user.uid,
            email: user.email || '',
            displayName: user.displayName || '',
            photoURL: user.photoURL || ''
        };
        
        // Set the cookie with user data (expires in 1 day)
        document.cookie = 'firebase_user=' + JSON.stringify(userData) + '; path=/; max-age=86400; SameSite=Lax';
        
        // Also set token in a separate cookie
        user.getIdToken().then(token => {
            document.cookie = 'firebase_user_token=' + token + '; path=/; max-age=3600; SameSite=Lax';
        });
    }
})(jQuery); 