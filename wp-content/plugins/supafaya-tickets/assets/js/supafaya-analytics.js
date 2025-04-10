/**
 * Supafaya Analytics
 * JavaScript module for tracking organization and event page views
 */
(function($) {
    'use strict';

    // Debug utility for analytics
    const SupafayaAnalyticsDebug = {
        enabled: true, // Set to false in production
        log: function(...args) {
            if (this.enabled && console && console.log) {
                console.log('[SupafayaAnalytics]', ...args);
            }
        },
        warn: function(...args) {
            if (this.enabled && console && console.warn) {
                console.warn('[SupafayaAnalytics]', ...args);
            }
        },
        error: function(...args) {
            if (console && console.error) {
                console.error('[SupafayaAnalytics]', ...args);
            }
        }
    };
    
    // Analytics service
    const SupafayaAnalytics = {
        init: function() {
            SupafayaAnalyticsDebug.log('Initializing Supafaya Analytics');
            
            // Register event listeners
            this.registerEventListeners();
        },
        
        registerEventListeners: function() {
            window.addEventListener('load', () => {
                this.trackOrganizationPageView();
            });
            
            // Event-level ping (fires when entering event details page)
            if (this.isEventDetailsPage()) {
                this.trackEventPageView();
            }
        },
        
        // Check if current page is an event details page
        isEventDetailsPage: function() {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.has('event_id');
        },
        
        // Track organization page view
        trackOrganizationPageView: function() {
            try {
                // Get organization ID
                const organizationId = this.getOrganizationId();
                
                if (!organizationId) {
                    SupafayaAnalyticsDebug.warn('No organization ID found, skipping organization ping');
                    return;
                }
                
                SupafayaAnalyticsDebug.log('Tracking organization page view for:', organizationId);
                
                // Send ping to API
                this.sendPing({
                    reference_type: 'organizations',
                    reference_id: organizationId
                });
            } catch (error) {
                SupafayaAnalyticsDebug.error('Error tracking organization page view:', error);
            }
        },
        
        // Track event page view
        trackEventPageView: function() {
            try {
                // Get event ID from URL
                const urlParams = new URLSearchParams(window.location.search);
                const eventId = urlParams.get('event_id');
                
                if (!eventId) {
                    SupafayaAnalyticsDebug.warn('No event ID found, skipping event ping');
                    return;
                }
                
                SupafayaAnalyticsDebug.log('Tracking event page view for:', eventId);
                
                // Send ping to API
                this.sendPing({
                    reference_type: 'events',
                    reference_id: eventId
                });
            } catch (error) {
                SupafayaAnalyticsDebug.error('Error tracking event page view:', error);
            }
        },
        
        // Get organization ID from various possible sources
        getOrganizationId: function() {
            // Try to get from data attribute on body
            const bodyOrgId = document.body.getAttribute('data-organization-id');
            if (bodyOrgId) {
                SupafayaAnalyticsDebug.log('Using organization ID from body data attribute:', bodyOrgId);
                return bodyOrgId;
            }
            
            // Try to get from meta tag
            const metaOrgId = document.querySelector('meta[name="supafaya-organization-id"]');
            if (metaOrgId && metaOrgId.getAttribute('content')) {
                const orgId = metaOrgId.getAttribute('content');
                SupafayaAnalyticsDebug.log('Using organization ID from meta tag:', orgId);
                return orgId;
            }
            
            // Try to get from global variable
            if (window.supafayaTickets && window.supafayaTickets.organizationId) {
                SupafayaAnalyticsDebug.log('Using organization ID from global variable:', window.supafayaTickets.organizationId);
                return window.supafayaTickets.organizationId;
            }
            
            // Try to get from .supafaya-events-grid data attribute
            const eventsGrid = document.querySelector('.supafaya-events-grid');
            if (eventsGrid && eventsGrid.dataset.organizationId) {
                SupafayaAnalyticsDebug.log('Using organization ID from events grid:', eventsGrid.dataset.organizationId);
                return eventsGrid.dataset.organizationId;
            }
            
            // Try to get from .supafaya-event-single data attribute
            const eventSingle = document.querySelector('.supafaya-event-single');
            if (eventSingle && eventSingle.dataset.organizationId) {
                SupafayaAnalyticsDebug.log('Using organization ID from event single:', eventSingle.dataset.organizationId);
                return eventSingle.dataset.organizationId;
            }
            
            // Try to get from any shortcode with data-organization-id
            const shortcodeElement = document.querySelector('[data-organization-id]');
            if (shortcodeElement && shortcodeElement.dataset.organizationId) {
                SupafayaAnalyticsDebug.log('Using organization ID from shortcode element:', shortcodeElement.dataset.organizationId);
                return shortcodeElement.dataset.organizationId;
            }
            
            // Try to get from URL parameter
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('organization_id')) {
                const orgId = urlParams.get('organization_id');
                SupafayaAnalyticsDebug.log('Using organization ID from URL parameter:', orgId);
                return orgId;
            }
            
            // Try to get from option in settings
            if (window.supafayaTickets && window.supafayaTickets.defaultOrganizationId) {
                SupafayaAnalyticsDebug.log('Using default organization ID from settings:', window.supafayaTickets.defaultOrganizationId);
                return window.supafayaTickets.defaultOrganizationId;
            }
            
            // No organization ID found
            SupafayaAnalyticsDebug.warn('No organization ID found, cannot send organization ping');
            return null;
        },
        
        // Get the current user ID if available
        getUserId: function() {
            // Try to get user ID from various sources
            let userId = null;
            
            // Try to get from Firebase if available
            if (typeof firebase !== 'undefined' && firebase.auth && firebase.auth().currentUser) {
                userId = firebase.auth().currentUser.uid;
                SupafayaAnalyticsDebug.log('Using user ID from Firebase auth:', userId);
            } else if (window.supafayaFirebase && typeof window.supafayaFirebase.getUserId === 'function') {
                userId = window.supafayaFirebase.getUserId();
                SupafayaAnalyticsDebug.log('Using user ID from supafayaFirebase:', userId);
            } else if (window.supafayaTickets && window.supafayaTickets.userId) {
                userId = window.supafayaTickets.userId;
                SupafayaAnalyticsDebug.log('Using user ID from supafayaTickets global:', userId);
            }
            
            return userId;
        },
        
        // Send ping to API through WordPress REST API proxy
        sendPing: function(data) {
            try {
                // Get user ID if available
                const userId = this.getUserId();
                
                if (userId) {
                    data.user_id = userId;
                }
                
                SupafayaAnalyticsDebug.log('Sending analytics ping:', data);
                
                // Build the REST API URL using the WordPress REST API
                let restUrl;
                
                // Try to use the REST URL from WordPress if available
                if (window.supafayaTickets && window.supafayaTickets.restUrl) {
                    restUrl = window.supafayaTickets.restUrl + 'analytics/ping';
                    SupafayaAnalyticsDebug.log('Using REST URL from WordPress:', restUrl);
                } else {
                    // Fallback to constructing the URL manually
                    restUrl = window.location.origin + '/wp-json/supafaya/v1/analytics/ping';
                    SupafayaAnalyticsDebug.log('Using manually constructed REST URL:', restUrl);
                }
                
                // Set up the custom headers for reference_id and reference_type
                const headers = {
                    'Content-Type': 'application/json',
                    'x-data-reference-id': data.reference_id,
                    'x-data-reference-type': data.reference_type,
                    'referer': window.location.href
                };
                
                // Add user ID if available
                if (userId) {
                    headers['x-data-user-id'] = userId;
                }
                
                SupafayaAnalyticsDebug.log('Analytics ping headers:', headers);
                
                // Send the request to the WordPress REST API proxy
                fetch(restUrl, {
                    method: 'GET',
                    headers: headers,
                    credentials: 'same-origin'
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Analytics ping failed: ${response.status} ${response.statusText}`);
                    }
                    
                    SupafayaAnalyticsDebug.log('Analytics ping response:', {
                        status: response.status,
                        statusText: response.statusText
                    });
                    
                    // Try to parse the response body, but don't fail if it's empty
                    return response.text().then(text => {
                        if (!text) {
                            SupafayaAnalyticsDebug.log('Empty response body');
                            return { success: true, message: "OK (empty response)" };
                        }
                        
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            SupafayaAnalyticsDebug.warn('Response is not valid JSON:', text);
                            return { success: true, message: "OK (non-JSON response)" };
                        }
                    });
                })
                .then(result => {
                    SupafayaAnalyticsDebug.log('Analytics ping successful:', result);
                })
                .catch(error => {
                    SupafayaAnalyticsDebug.error('Analytics ping failed:', error);
                });
            } catch (error) {
                SupafayaAnalyticsDebug.error('Error sending analytics ping:', error);
            }
        }
    };
    
    // Initialize analytics on DOM ready
    $(document).ready(function() {
        SupafayaAnalytics.init();
    });
    
    // Export to global scope for external access
    window.SupafayaAnalytics = SupafayaAnalytics;
    
})(jQuery); 