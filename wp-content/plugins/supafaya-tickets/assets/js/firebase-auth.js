(function($) {
    'use strict';

    let currentUser = null;

    const firebaseConfig = {
        apiKey: supafayaFirebase.apiKey,
        authDomain: supafayaFirebase.authDomain,
        projectId: supafayaFirebase.projectId
    };

    if (!firebaseConfig.apiKey || !firebaseConfig.authDomain || !firebaseConfig.projectId) {
        $('#firebaseui-auth-container').html('<div class="error-message">Firebase configuration is missing. Please contact the administrator.</div>');
        return;
    }

    const firebaseApp = firebase.initializeApp(firebaseConfig);
    const auth = firebase.auth();
    
    const ui = new firebaseui.auth.AuthUI(auth);

    const uiConfig = {
        callbacks: {
            signInSuccessWithAuthResult: function(authResult, redirectUrl) {
                handleSignIn(authResult.user);
                return false;
            },
            uiShown: function() {
                $('#firebase-loading').hide();
            }
        },
        signInFlow: 'popup',
        signInOptions: [
            {
                provider: firebase.auth.GoogleAuthProvider.PROVIDER_ID,
                customParameters: {
                    prompt: 'select_account'
                }
            },
            {
                provider: firebase.auth.EmailAuthProvider.PROVIDER_ID,
                requireDisplayName: true
            }
        ],
        tosUrl: '#',
        privacyPolicyUrl: '#'
    };

    function updateAuthUI(user) {
        if (user) {
            setFirebaseUserCookie(user);
            
            $('.auth-logged-in').show();
            $('.auth-logged-out').hide();
            
            $('#user-name').text(user.displayName || user.email.split('@')[0]);
            
            const $avatarImg = $('#user-avatar-img');
            const $userInitials = $('#user-initials');
            
            if (user.photoURL) {
                $avatarImg.attr('src', user.photoURL).show();
                $userInitials.hide();
            } else {
                $avatarImg.hide();
                const initials = user.displayName ? 
                    user.displayName.split(' ').map(n => n[0]).join('') : 
                    user.email[0].toUpperCase();
                $userInitials.text(initials).show();
            }
            
            updateFirebaseToken(user);
            
            setupAjaxTokenInterceptor(user);
        } else {
            document.cookie = 'firebase_user=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
            document.cookie = 'firebase_user_token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
            
            $('.auth-logged-in').hide();
            $('.auth-logged-out').show();
        }
    }

    $(document).ready(function() {
        const urlParams = new URLSearchParams(window.location.search);
        const redirectTo = urlParams.get('redirect_to');
        if (redirectTo) {
            localStorage.setItem('supafaya_redirect', redirectTo);
        }

        if ($('#firebaseui-auth-container').length > 0) {
            ui.start('#firebaseui-auth-container', uiConfig);
        }

        auth.onAuthStateChanged(function(user) {
            currentUser = user;
            updateAuthUI(user);
        });

        $('.firebase-logout-button').on('click', function(e) {
            e.preventDefault();
            
            auth.signOut().then(function() {
                document.cookie = 'firebase_user=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
                document.cookie = 'firebase_user_token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
                
                window.location.href = supafayaFirebase.siteUrl;
            }).catch(function(error) {
                // Silent fail
            });
        });

        $(document).on('click', '.user-dropdown-toggle', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $('.supafaya-user-dropdown').toggleClass('open');
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('.supafaya-user-dropdown').length) {
                $('.supafaya-user-dropdown').removeClass('open');
            }
        });
    });

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
    
    function updateFirebaseToken(user) {
        user.getIdToken(true).then(function(token) {
            setCookie('firebase_user_token', token, 1);
        }).catch(function(error) {
            // Silent fail
        });
    }

    function handleSignIn(user) {
        $('#firebase-loading').show();
        $('#firebase-error').hide();

        user.getIdToken(true).then(function(idToken) {
            setCookie('firebase_user_token', idToken, 1);
            
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
                        let redirectUrl = localStorage.getItem('supafaya_redirect');
                        if (redirectUrl) {
                            localStorage.removeItem('supafaya_redirect');
                        } else {
                            redirectUrl = response.data.redirect || supafayaFirebase.redirectUrl;
                        }
                        
                        window.location.href = redirectUrl;
                    } else {
                        $('#firebase-error').html('Authentication failed: ' + (response.data || 'Unknown error')).show();
                        $('#firebase-loading').hide();
                    }
                },
                error: function() {
                    $('#firebase-error').html('Network error. Please try again.').show();
                    $('#firebase-loading').hide();
                }
            });
        }).catch(function(error) {
            $('#firebase-error').html('Firebase token error: ' + error.message).show();
            $('#firebase-loading').hide();
        });
    }
    
    function setCookie(name, value, days) {
        var expires = "";
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "")  + expires + "; path=/";
    }

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

    function setFirebaseUserCookie(user) {
        if (!user) return;
        
        const userData = {
            uid: user.uid,
            email: user.email || '',
            displayName: user.displayName || '',
            photoURL: user.photoURL || ''
        };
        
        document.cookie = 'firebase_user=' + JSON.stringify(userData) + '; path=/; max-age=86400; SameSite=Lax';
        
        user.getIdToken().then(token => {
            document.cookie = 'firebase_user_token=' + token + '; path=/; max-age=3600; SameSite=Lax';
        });
    }
})(jQuery);