(function($) {
    'use strict';
    
    const SupafayaAnalytics = {
        init: function() {
            this.registerEventListeners();
        },
        
        registerEventListeners: function() {
            window.addEventListener('load', () => {
                this.trackOrganizationPageView();
            });
            
            if (this.isEventDetailsPage()) {
                this.trackEventPageView();
            }
        },
        
        isEventDetailsPage: function() {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.has('event_id');
        },
        
        trackOrganizationPageView: function() {
            try {
                const organizationId = this.getOrganizationId();
                
                if (!organizationId) {
                    return;
                }
                
                this.sendPing({
                    reference_type: 'organizations',
                    reference_id: organizationId
                });
            } catch (error) {
                // Silent fail
            }
        },
        
        trackEventPageView: function() {
            try {
                const urlParams = new URLSearchParams(window.location.search);
                const eventId = urlParams.get('event_id');
                
                if (!eventId) {
                    return;
                }
                
                this.sendPing({
                    reference_type: 'events',
                    reference_id: eventId
                });
            } catch (error) {
                // Silent fail
            }
        },
        
        getOrganizationId: function() {
            const bodyOrgId = document.body.getAttribute('data-organization-id');
            if (bodyOrgId) {
                return bodyOrgId;
            }
            
            const metaOrgId = document.querySelector('meta[name="supafaya-organization-id"]');
            if (metaOrgId && metaOrgId.getAttribute('content')) {
                return metaOrgId.getAttribute('content');
            }
            
            if (window.supafayaTickets && window.supafayaTickets.organizationId) {
                return window.supafayaTickets.organizationId;
            }
            
            const eventsGrid = document.querySelector('.supafaya-events-grid');
            if (eventsGrid && eventsGrid.dataset.organizationId) {
                return eventsGrid.dataset.organizationId;
            }
            
            const eventSingle = document.querySelector('.supafaya-event-single');
            if (eventSingle && eventSingle.dataset.organizationId) {
                return eventSingle.dataset.organizationId;
            }
            
            const shortcodeElement = document.querySelector('[data-organization-id]');
            if (shortcodeElement && shortcodeElement.dataset.organizationId) {
                return shortcodeElement.dataset.organizationId;
            }
            
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('organization_id')) {
                return urlParams.get('organization_id');
            }
            
            if (window.supafayaTickets && window.supafayaTickets.defaultOrganizationId) {
                return window.supafayaTickets.defaultOrganizationId;
            }
            
            return null;
        },
        
        getUserId: function() {
            let userId = null;
            
            if (typeof firebase !== 'undefined' && firebase.auth && firebase.auth().currentUser) {
                userId = firebase.auth().currentUser.uid;
            } else if (window.supafayaFirebase && typeof window.supafayaFirebase.getUserId === 'function') {
                userId = window.supafayaFirebase.getUserId();
            } else if (window.supafayaTickets && window.supafayaTickets.userId) {
                userId = window.supafayaTickets.userId;
            }
            
            return userId;
        },
        
        sendPing: function(data) {
            try {
                const userId = this.getUserId();
                
                if (userId) {
                    data.user_id = userId;
                }
                
                let restUrl;
                
                if (window.supafayaTickets && window.supafayaTickets.restUrl) {
                    restUrl = window.supafayaTickets.restUrl + 'analytics/ping';
                } else {
                    restUrl = window.location.origin + '/wp-json/supafaya/v1/analytics/ping';
                }
                
                const headers = {
                    'Content-Type': 'application/json',
                    'x-data-reference-id': data.reference_id,
                    'x-data-reference-type': data.reference_type,
                    'referer': window.location.href
                };
                
                if (userId) {
                    headers['x-data-user-id'] = userId;
                }
                
                fetch(restUrl, {
                    method: 'GET',
                    headers: headers,
                    credentials: 'same-origin'
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Analytics ping failed: ${response.status} ${response.statusText}`);
                    }
                    
                    return response.text().then(text => {
                        if (!text) {
                            return { success: true, message: "OK (empty response)" };
                        }
                        
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            return { success: true, message: "Response is not valid JSON" };
                        }
                    });
                })
                .catch(error => {
                    // Silent fail
                });
            } catch (error) {
                // Silent fail
            }
        }
    };
    
    // Initialize when the document is ready
    $(document).ready(function() {
        SupafayaAnalytics.init();
    });
    
})(jQuery);