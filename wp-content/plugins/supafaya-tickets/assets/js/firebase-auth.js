/**
 * Firebase Authentication Handler
 */
(function($) {
    'use strict';

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
        // Start the FirebaseUI Auth
        ui.start('#firebaseui-auth-container', uiConfig);

        // Listen for auth state changes
        auth.onAuthStateChanged(function(user) {
            if (user && !supafayaFirebase.isLoggedIn) {
                // User is signed in with Firebase but not with WordPress
                // handleSignIn will be called by the UI callback
            }
        });
    });

    // Handle user sign in - authenticate with WordPress backend
    function handleSignIn(user) {
        // Show loading
        $('#firebase-loading').show();
        $('#firebase-error').hide();

        // Get ID token
        user.getIdToken(true).then(function(idToken) {
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
                        // Redirect to the requested URL
                        window.location.href = response.data.redirect || supafayaFirebase.redirectUrl;
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

})(jQuery); 