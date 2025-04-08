/**
 * Purchased Items Dialog Handler
 * Handles the UI and functionality for the purchased items dialog.
 */
(function($) {
    'use strict';
    
    // Initialize the module when document is ready
    $(document).ready(function() {
        SupafayaPurchasedItems.init();
    });
    
    // Module pattern to keep code organized
    var SupafayaPurchasedItems = {
        // Store DOM elements
        elements: {
            dialog: null,
            dialogContent: null,
            dialogClose: null,
            dialogOverlay: null,
            purchasedItemsButton: null,
            loadingState: null,
            errorState: null,
            emptyState: null,
            contentState: null,
            purchasedItemsList: null,
            errorMessage: null,
            tabButtons: null
        },
        
        // Store the event ID and current items data
        data: {
            eventId: null,
            currentItems: []
        },
        
        // Initialize the module
        init: function() {
            // Get the event ID from URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            this.data.eventId = urlParams.get('event_id');
            
            // If no event ID, exit
            if (!this.data.eventId) {
                return;
            }
            
            // Cache DOM elements
            this.cacheElements();
            
            // Bind event handlers
            this.bindEvents();
        },
        
        // Cache DOM elements for better performance
        cacheElements: function() {
            this.elements.dialog = $('.purchased-items-dialog');
            this.elements.dialogContent = $('.dialog-content');
            this.elements.dialogClose = $('.dialog-close');
            this.elements.dialogOverlay = $('.dialog-overlay');
            this.elements.purchasedItemsButton = $('.purchased-items-button');
            this.elements.loadingState = $('.loading-state');
            this.elements.errorState = $('.error-state');
            this.elements.emptyState = $('.empty-state');
            this.elements.contentState = $('.content-state');
            this.elements.purchasedItemsList = $('.purchased-items-list');
            this.elements.errorMessage = $('.error-message');
            this.elements.tabButtons = $('.tab-button');
        },
        
        // Bind event handlers
        bindEvents: function() {
            // Button click handler
            this.elements.purchasedItemsButton.on('click', this.handleButtonClick.bind(this));
            
            // Close dialog handlers
            this.elements.dialogClose.on('click', this.closeDialog.bind(this));
            this.elements.dialogOverlay.on('click', this.closeDialog.bind(this));
            
            // Tab switching
            this.elements.tabButtons.on('click', this.handleTabClick.bind(this));
            
            // Close on Escape key
            $(document).on('keydown', this.handleKeyPress.bind(this));
        },
        
        // Handle button click
        handleButtonClick: function() {
            // Check if Firebase is loaded
            if (typeof firebase === 'undefined' || !firebase.auth) {
                console.error('Firebase is not loaded or initialized');
                return;
            }
            
            // Check if user is logged in
            const currentUser = firebase.auth().currentUser;
            
            if (!currentUser) {
                // Save the current URL to redirect back after login
                document.cookie = 'supafaya_checkout_redirect=' + window.location.href + '; path=/; max-age=3600';
                
                // Redirect to login page
                window.location.href = supafayaTickets.loginUrl;
                return;
            }
            
            // Show dialog and loading state
            this.elements.dialog.show();
            this.elements.loadingState.show();
            this.elements.errorState.hide();
            this.elements.emptyState.hide();
            this.elements.contentState.hide();
            
            // Get fresh token and fetch items
            currentUser.getIdToken(true)
                .then((token) => {
                    this.fetchPurchasedItems(token);
                })
                .catch((error) => {
                    console.error('Error getting Firebase token:', error);
                    this.handleTokenError(error);
                });
        },
        
        // Fetch purchased items from API
        fetchPurchasedItems: function(token) {
            return $.ajax({
                url: supafayaTickets.ajaxUrl,
                method: 'GET',
                data: {
                    action: 'supafaya_get_user_items',
                    event_id: this.data.eventId,
                    nonce: supafayaTickets.nonce
                },
                headers: {
                    'X-Firebase-Token': token
                }
            })
            .done(this.handleApiResponse.bind(this))
            .fail(this.handleApiError.bind(this));
        },
        
        // Handle API response
        handleApiResponse: function(response) {
            this.elements.loadingState.hide();
            
            if (response.success) {
                const items = response.data;
                this.data.currentItems = items; // Store for filtering
                
                if (items && items.length > 0) {
                    // Show items
                    this.elements.contentState.show();
                    
                    // Reset to "All" tab
                    this.elements.tabButtons.removeClass('active');
                    $('.tab-button[data-tab="all"]').addClass('active');
                    
                    this.renderItems(items);
                    this.updateTabCounts(items);
                } else {
                    // Show empty state
                    this.elements.emptyState.show();
                }
            } else {
                // Show error state
                this.elements.errorState.show();
                this.elements.errorMessage.text(response.message || 'Failed to load purchased items');
            }
        },
        
        // Handle API error
        handleApiError: function(xhr, status, error) {
            this.elements.loadingState.hide();
            this.elements.errorState.show();
            this.elements.errorMessage.text('Failed to load purchased items. Please try again.');
        },
        
        // Handle token error
        handleTokenError: function(error) {
            this.elements.loadingState.hide();
            this.elements.errorState.show();
            this.elements.errorMessage.text('Authentication error. Please try logging in again.');
        },
        
        // Close dialog
        closeDialog: function() {
            this.elements.dialog.hide();
        },
        
        // Handle Escape key press
        handleKeyPress: function(e) {
            if (e.key === 'Escape' && this.elements.dialog.is(':visible')) {
                this.closeDialog();
            }
        },
        
        // Handle tab click
        handleTabClick: function(e) {
            const tab = $(e.currentTarget).data('tab');
            
            // Update active state
            this.elements.tabButtons.removeClass('active');
            $(e.currentTarget).addClass('active');
            
            // Filter items based on tab
            if (tab === 'all') {
                this.filterItems();
            } else if (tab === 'tickets') {
                this.filterItems('ticket');
            } else if (tab === 'addons') {
                this.filterItems('addon');
            }
        },
        
        // Filter items by type
        filterItems: function(type = null) {
            this.elements.purchasedItemsList.empty();
            
            const filteredItems = type 
                ? this.data.currentItems.filter(item => item.type === type)
                : this.data.currentItems;
                
            if (filteredItems.length === 0) {
                this.elements.purchasedItemsList.html('<div class="empty-tab-message">No items found in this category</div>');
                return;
            }
            
            this.renderItems(filteredItems);
        },
        
        // Update tab counts
        updateTabCounts: function(items) {
            const ticketCount = items.filter(item => item.type === 'ticket').length;
            const addonCount = items.filter(item => item.type === 'addon').length;
            
            // Update tab text with counts
            $('.tab-button[data-tab="tickets"]').text(`Tickets (${ticketCount})`);
            $('.tab-button[data-tab="addons"]').text(`Add-ons (${addonCount})`);
            $('.tab-button[data-tab="all"]').text(`All Items (${items.length})`);
        },
        
        // Render purchased items
        renderItems: function(items) {
            this.elements.purchasedItemsList.empty();
            
            items.forEach(function(item) {
                const itemElement = $('<div class="purchased-item">');
                
                // Item icon
                const icon = $('<div class="item-icon">');
                icon.html(this.getItemIcon(item.type));
                
                // Item details
                const details = $('<div class="item-details">');
                
                // Display different content based on item type
                if (item.type === 'ticket') {
                    // For tickets: name, ticket_type, price, currency, purchased_date, valid_until, qr_code
                    
                    // Add ticket name (main heading)
                    details.append($('<div class="item-name">').text(item.name));
                    
                    // Create meta info array for tickets
                    const metaInfo = [];
                    
                    // Add ticket type if available
                    if (item.ticket_type) {
                        metaInfo.push(`Type: ${item.ticket_type}`);
                    }
                    
                    // Add purchased date if available
                    if (item.purchase_date) {
                        const purchaseDate = new Date(item.purchase_date);
                        if (!isNaN(purchaseDate.getTime())) {
                            metaInfo.push('Purchased: ' + purchaseDate.toLocaleDateString());
                        }
                    }
                    
                    // Add all meta info
                    if (metaInfo.length > 0) {
                        details.append($('<div class="item-meta">').text(metaInfo.join(' • ')));
                    }
                    
                    // Add valid until for tickets
                    if (item.valid_until) {
                        details.append($('<div class="item-valid-until">').text('Valid until: ' + new Date(item.valid_until).toLocaleDateString()));
                    }
                    
                    // Handle QR code (convert from buffer if needed)
                    if (item.qr_code) {
                        // Check if QR code is a buffer and convert if needed
                        let qrSrc = item.qr_code;
                        
                        // If it's a buffer object, convert to image
                        if (typeof item.qr_code === 'object' && item.qr_code !== null && item.qr_code.type === 'Buffer') {
                            qrSrc = this.bufferToImage(item.qr_code);
                        }
                        
                        if (qrSrc) {
                            const qrContainer = $('<div class="qr-container">').hide();
                            const qrCode = $('<div class="qr-code">');
                            qrCode.append($('<img>').attr('src', qrSrc).attr('alt', 'Ticket QR Code'));
                            qrContainer.append(qrCode);
                            
                            const qrButton = $('<button class="qr-button">').html(`
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                    <rect x="7" y="7" width="3" height="3"></rect>
                                    <rect x="14" y="7" width="3" height="3"></rect>
                                    <rect x="7" y="14" width="3" height="3"></rect>
                                    <rect x="14" y="14" width="3" height="3"></rect>
                                </svg>
                                Show QR Code
                            `);
                            
                            qrButton.on('click', function(e) {
                                e.preventDefault();
                                qrContainer.toggle();
                                
                                // Update button text based on visibility
                                const buttonText = qrContainer.is(':visible') 
                                    ? 'Hide QR Code' 
                                    : 'Show QR Code';
                                    
                                // Keep the SVG icon when updating text
                                $(this).html(`
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                        <rect x="7" y="7" width="3" height="3"></rect>
                                        <rect x="14" y="7" width="3" height="3"></rect>
                                        <rect x="7" y="14" width="3" height="3"></rect>
                                        <rect x="14" y="14" width="3" height="3"></rect>
                                    </svg>
                                    ${buttonText}
                                `);
                            });
                            
                            details.append(qrButton);
                            details.append(qrContainer);
                        }
                    }
                    
                    // Price for tickets
                    const price = $('<div class="item-price">');
                    if (item.price > 0) {
                        // Use currency if available, default to PHP
                        const currencySymbol = item.currency === 'USD' ? '$' : '฿';
                        price.text(currencySymbol + parseFloat(item.price).toFixed(2));
                    } else {
                        price.text('Free');
                    }
                    
                    // Assemble ticket item
                    itemElement.append(icon, details, price);
                    
                } else if (item.type === 'addon') {
                    // For addons: title, quantity, price
                    
                    // Add addon title (main heading)
                    details.append($('<div class="item-name">').text(item.title || 'Add-on'));
                    
                    // Quantity info for addons
                    if (item.quantity && item.quantity > 1) {
                        details.append($('<div class="item-quantity">').text(`Quantity: ${item.quantity}`));
                    }
                    
                    // Price for addons
                    const price = $('<div class="item-price">');
                    if (item.price > 0) {
                        price.text('฿' + parseFloat(item.price).toFixed(2));
                    } else {
                        price.text('Free');
                    }
                    
                    // Assemble addon item
                    itemElement.append(icon, details, price);
                    itemElement.addClass('addon-item');
                }
                
                this.elements.purchasedItemsList.append(itemElement);
            }, this);
        },
        
        // Get item icon based on type
        getItemIcon: function(type) {
            const icons = {
                'ticket': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9V5c0-1.1.9-2 2-2h16a2 2 0 0 1 2 2v4"></path><path d="M2 15v4c0 1.1.9 2 2 2h16a2 2 0 0 0 2-2v-4"></path><path d="M4 9h16"></path><path d="M4 15h16"></path></svg>',
                'addon': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>'
            };
            
            return icons[type] || icons.ticket;
        },
        
        // Buffer to image conversion function
        bufferToImage: function(buffer, mimeType = "image/png") {
            try {
                if (!buffer || typeof buffer !== "object" || buffer.type !== "Buffer" || !Array.isArray(buffer.data)) {
                    throw new Error("Invalid buffer format");
                }

                const uint8Array = new Uint8Array(buffer.data);

                // Convert each byte to a character and join into a string
                const binaryString = uint8Array.reduce(
                    (acc, byte) => acc + String.fromCharCode(byte),
                    ""
                );

                // Convert binary string to Base64
                const base64String = btoa(binaryString);

                return `data:${mimeType};base64,${base64String}`;
            } catch (e) {
                console.error("Failed to convert buffer to image", e);
                return null;
            }
        }
    };
    
    // Make the module available globally
    window.SupafayaPurchasedItems = SupafayaPurchasedItems;
    
})(jQuery); 