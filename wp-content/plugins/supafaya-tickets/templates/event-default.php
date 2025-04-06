<?php
?>
<div class="supafaya-event-single">
    <div class="event-container">
        <!-- Left Column (Sticky) -->
        <div class="event-left-column">
            <?php if (!empty($event['poster_image'])): ?>
                <div class="event-image">
                    <img src="<?php echo esc_url($event['poster_image']); ?>" alt="<?php echo esc_attr($event['title']); ?>" loading="lazy">
                </div>
            <?php endif; ?>
            
            <div class="event-info">
                <h1 class="event-title"><?php echo esc_html($event['title']); ?></h1>
                
                <div class="event-meta">
                    <div class="meta-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <div>
                            <span class="meta-label">Start Date</span>
                            <span class="meta-value"><?php echo date('F j, Y, g:i a', strtotime($event['start_date'])); ?></span>
                        </div>
                    </div>
                    
                    <div class="meta-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <div>
                            <span class="meta-label">End Date</span>
                            <span class="meta-value"><?php echo date('F j, Y, g:i a', strtotime($event['end_date'])); ?></span>
                        </div>
                    </div>
                    
                    <?php if (!empty($event['location'])): ?>
                        <div class="meta-item">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                            <div>
                                <span class="meta-label">Location</span>
                                <span class="meta-value"><?php echo esc_html($event['location']); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($event['tags']) || !empty($event['categories'])): ?>
                    <div class="event-tags">
                        <h3>Tags & Categories</h3>
                        <div class="tags-container">
                            <?php if (!empty($event['tags'])): ?>
                                <?php foreach ($event['tags'] as $tag): ?>
                                    <span class="tag"><?php echo esc_html($tag); ?></span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            
                            <?php if (!empty($event['categories'])): ?>
                                <?php foreach ($event['categories'] as $category): ?>
                                    <span class="category"><?php echo esc_html($category); ?></span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($event['description'])): ?>
                    <div class="event-description">
                        <h3>About This Event</h3>
                        <div class="description-content">
                            <?php echo wpautop($event['description']); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Right Column -->
        <div class="event-right-column">
            <?php 
            // More robust check for tickets - check different possible structures
            $tickets_data = null;
            if (!empty($event['tickets'])) {
                $tickets_data = $event['tickets'];
            } elseif (!empty($event['ticket_types'])) {
                $tickets_data = $event['ticket_types'];
            }
            
            if ($tickets_data || !empty($event['addons'])): 
            ?>
                <div class="event-tickets">
                    <h2>Tickets</h2>
                    <div class="tickets-list">
                        <?php 
                        // Display Tickets
                        if ($tickets_data):
                            foreach ($tickets_data as $ticket): 
                                // Normalize ticket data - handle different structures
                                $ticket_status = isset($ticket['status']) ? strtolower($ticket['status']) : 'active';
                                $ticket_name = isset($ticket['name']) ? $ticket['name'] : (isset($ticket['title']) ? $ticket['title'] : 'Ticket');
                                $ticket_desc = isset($ticket['description']) ? $ticket['description'] : '';
                                $ticket_price = isset($ticket['price']) ? $ticket['price'] : 0;
                                $ticket_id = isset($ticket['ticket_id']) ? $ticket['ticket_id'] : (isset($ticket['id']) ? $ticket['id'] : '');
                                $ticket_quantity = isset($ticket['quantity']) ? $ticket['quantity'] : 10;
                                
                                if (strtolower($ticket_status) === 'active'): 
                        ?>
                                <div class="ticket-item ticket-item-only">
                                    <div class="ticket-info">
                                        <h3 class="ticket-name"><?php echo esc_html($ticket_name); ?></h3>
                                        <?php if (!empty($ticket_desc)): ?>
                                            <p class="ticket-description"><?php echo esc_html($ticket_desc); ?></p>
                                        <?php endif; ?>
                                        <div class="ticket-price">₱<?php echo number_format($ticket_price, 2); ?></div>
                                    </div>
                                    
                                    <div class="ticket-actions">
                                        <div class="quantity-selector">
                                            <button class="quantity-decrease">-</button>
                                            <input type="number" class="ticket-quantity" value="1" min="1" max="<?php echo esc_attr($ticket_quantity); ?>">
                                            <button class="quantity-increase">+</button>
                                        </div>
                                        <button class="add-to-cart" data-ticket-id="<?php echo esc_attr($ticket_id); ?>">
                                            Add to Cart
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="9" cy="21" r="1"></circle>
                                                <circle cx="20" cy="21" r="1"></circle>
                                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            <?php 
                                endif; 
                            endforeach;
                        endif; 
                        ?>
                        
                        <?php 
                        // Display Addons
                        if (!empty($event['addons'])): 
                        ?>
                            <div class="addons-section">
                                <h2>Available Add-ons</h2>
                                <?php foreach ($event['addons'] as $addon): ?>
                                    <div class="ticket-item addon-item">
                                        <div class="ticket-info">
                                            <h3 class="ticket-name"><?php echo esc_html($addon['title']); ?></h3>
                                            <?php if (!empty($addon['description'])): ?>
                                                <p class="ticket-description"><?php echo esc_html($addon['description']); ?></p>
                                            <?php endif; ?>
                                            <div class="ticket-price">₱<?php echo number_format($addon['price'], 2); ?></div>
                                        </div>
                                        
                                        <div class="ticket-actions">
                                            <div class="quantity-selector">
                                                <button class="quantity-decrease">-</button>
                                                <input type="number" class="ticket-quantity addon-quantity" value="1" min="1" max="10">
                                                <button class="quantity-increase">+</button>
                                            </div>
                                            <button class="add-to-cart add-addon-to-cart" data-addon-id="<?php echo esc_attr($addon['id']); ?>">
                                                Add to Cart
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="9" cy="21" r="1"></circle>
                                                    <circle cx="20" cy="21" r="1"></circle>
                                                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="order-summary">
                            <div class="summary-header">
                                <h3>Order Summary</h3>
                                <button class="clear-cart">Clear All</button>
                            </div>
                            <div class="summary-items">
                                <!-- Will be populated by JavaScript -->
                            </div>
                            <div class="summary-total">
                                <span>Total:</span>
                                <span class="total-amount">₱0.00</span>
                            </div>
                            <button class="checkout-button">Proceed to Checkout</button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($event['details'])): ?>
                <div class="event-details">
                    <h2>Event Details</h2>
                    <div class="details-content">
                        <?php echo wpautop($event['details']); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Purchased Items Button and Dialog -->
<div class="purchased-items-container">
    <button class="purchased-items-button">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
        </svg>
        My Purchased Items
    </button>

    <!-- Dialog -->
    <div class="purchased-items-dialog" style="display: none;">
        <div class="dialog-overlay"></div>
        <div class="dialog-content">
            <div class="dialog-header">
                <h2>My Purchased Items</h2>
                <button class="dialog-close">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            
            <div class="dialog-body">
                <!-- Loading State -->
                <div class="loading-state" style="display: none;">
                    <div class="loading-spinner"></div>
                    <p>Loading your purchased items...</p>
                </div>

                <!-- Error State -->
                <div class="error-state" style="display: none;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <p class="error-message"></p>
                </div>

                <!-- Empty State -->
                <div class="empty-state" style="display: none;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    <p>No purchased items found for this event.</p>
                </div>

                <!-- Content State -->
                <div class="content-state" style="display: none;">
                    <div class="purchased-items-list">
                        <!-- Items will be populated by JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Purchased Items Button Styles */
.purchased-items-container {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1000;
}

.purchased-items-button {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    background-color: #4285f4;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    transition: all 0.3s ease;
}

.purchased-items-button:hover {
    background-color: #3367d6;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.purchased-items-button svg {
    width: 20px;
    height: 20px;
}

/* Dialog Styles */
.purchased-items-dialog {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 1100;
    display: flex;
    align-items: center;
    justify-content: center;
}

.dialog-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
}

.dialog-content {
    position: relative;
    background-color: white;
    border-radius: 12px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.15);
}

.dialog-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px;
    border-bottom: 1px solid #eee;
}

.dialog-header h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
    color: #333;
}

.dialog-close {
    background: none;
    border: none;
    padding: 8px;
    cursor: pointer;
    color: #666;
    transition: color 0.3s ease;
}

.dialog-close:hover {
    color: #333;
}

.dialog-body {
    padding: 20px;
}

/* Loading State */
.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px;
    text-align: center;
    color: #666;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #f3f3f3;
    border-top: 3px solid #4285f4;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 16px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Error State */
.error-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px;
    text-align: center;
    color: #dc3545;
}

.error-state svg {
    margin-bottom: 16px;
}

.error-message {
    margin: 0;
    font-size: 16px;
}

/* Empty State */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px;
    text-align: center;
    color: #666;
}

.empty-state svg {
    margin-bottom: 16px;
}

/* Content State */
.purchased-items-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.purchased-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    background-color: #f8f9fa;
    border-radius: 8px;
    transition: background-color 0.3s ease;
}

.purchased-item:hover {
    background-color: #f1f3f5;
}

.item-icon {
    width: 48px;
    height: 48px;
    background-color: #e8f0fe;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #4285f4;
}

.item-details {
    flex: 1;
}

.item-name {
    font-weight: 500;
    color: #333;
    margin: 0 0 4px 0;
}

.item-meta {
    font-size: 14px;
    color: #666;
}

.item-price {
    font-weight: 600;
    color: #4285f4;
}

/* Responsive Styles */
@media (max-width: 768px) {
    .purchased-items-button {
        padding: 10px 16px;
        font-size: 13px;
    }

    .dialog-content {
        width: 95%;
        margin: 20px;
    }

    .purchased-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }

    .item-icon {
        width: 40px;
        height: 40px;
    }
}

/* New styles */
.item-badge {
    display: inline-block;
    padding: 2px 6px;
    font-size: 11px;
    font-weight: 600;
    background-color: #f0f0f0;
    color: #666;
    border-radius: 4px;
    margin-bottom: 4px;
}

.item-badge.refunded {
    background-color: #ffdddd;
    color: #d32f2f;
}

.addon-item {
    margin-left: 20px;
    background-color: #f5f9ff;
    border-left: 2px solid #4285f4;
}

.qr-button {
    display: inline-block;
    margin-top: 8px;
    padding: 4px 10px;
    background-color: #4285f4;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 12px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.qr-button:hover {
    background-color: #3367d6;
}

.qr-container {
    margin-top: 8px;
    text-align: center;
}

.qr-code img {
    width: 150px;
    height: 150px;
    margin: 0 auto;
    display: block;
}

.item-valid-until {
    font-size: 12px;
    color: #666;
    margin-top: 4px;
}
</style>

<script>
(function($) {
    // Debug logging function
    function debugLog(message, data = null) {
        const timestamp = new Date().toISOString();
        const logMessage = `[Supafaya Debug ${timestamp}] ${message}`;
        console.log(logMessage);
        if (data) {
            console.log('Data:', data);
        }
    }

    // Get current event ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    const currentEventId = urlParams.get('event_id');
    
    debugLog('Initializing purchased items dialog', { currentEventId });
    
    if (!currentEventId) {
        debugLog('Error: Event ID not found in URL');
        return;
    }

    // Dialog elements
    const dialog = $('.purchased-items-dialog');
    const dialogContent = $('.dialog-content');
    const dialogClose = $('.dialog-close');
    const dialogOverlay = $('.dialog-overlay');
    const purchasedItemsButton = $('.purchased-items-button');
    
    // State elements
    const loadingState = $('.loading-state');
    const errorState = $('.error-state');
    const emptyState = $('.empty-state');
    const contentState = $('.content-state');
    const purchasedItemsList = $('.purchased-items-list');
    const errorMessage = $('.error-message');

    // Open dialog
    purchasedItemsButton.on('click', function() {
        debugLog('Purchased items button clicked');
        
        // Check if user is logged in
        const currentUser = firebase.auth().currentUser;
        debugLog('Firebase auth state', { 
            isLoggedIn: !!currentUser,
            userId: currentUser?.uid 
        });

        if (!currentUser) {
            debugLog('User not logged in, redirecting to login page');
            // Save the current URL to redirect back after login
            document.cookie = 'supafaya_checkout_redirect=' + window.location.href + '; path=/; max-age=3600';
            
            // Redirect to login page
            window.location.href = supafayaTickets.loginUrl;
            return;
        }

        // Show dialog and loading state
        dialog.show();
        loadingState.show();
        errorState.hide();
        emptyState.hide();
        contentState.hide();

        debugLog('Fetching Firebase token');
        // Get Firebase token
        currentUser.getIdToken(true).then(function(token) {
            debugLog('Firebase token obtained', { tokenLength: token.length });
            
            // Make API request
            debugLog('Making API request for user items', {
                eventId: currentEventId,
                ajaxUrl: supafayaTickets.ajaxUrl
            });

            $.ajax({
                url: supafayaTickets.ajaxUrl,
                method: 'GET',
                data: {
                    action: 'supafaya_get_user_items',
                    event_id: currentEventId,
                    nonce: supafayaTickets.nonce
                },
                headers: {
                    'X-Firebase-Token': token
                },
                success: function(response) {
                    debugLog('API response received', response);
                    loadingState.hide();
                    
                    if (response.success) {
                        const items = response.data;
                        debugLog('Items retrieved', { 
                            itemCount: items?.length || 0,
                            items: items 
                        });
                        
                        if (items && items.length > 0) {
                            // Show items
                            contentState.show();
                            renderItems(items);
                        } else {
                            // Show empty state
                            emptyState.show();
                            debugLog('No items found for user');
                        }
                    } else {
                        // Show error state
                        errorState.show();
                        errorMessage.text(response.message || 'Failed to load purchased items');
                        debugLog('API request failed', { 
                            message: response.message,
                            response: response 
                        });
                    }
                },
                error: function(xhr, status, error) {
                    debugLog('AJAX error', { 
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                    loadingState.hide();
                    errorState.show();
                    errorMessage.text('Failed to load purchased items. Please try again.');
                }
            });
        }).catch(function(error) {
            debugLog('Firebase token error', error);
            loadingState.hide();
            errorState.show();
            errorMessage.text('Authentication error. Please try logging in again.');
        });
    });

    // Close dialog
    function closeDialog() {
        debugLog('Closing dialog');
        dialog.hide();
    }

    dialogClose.on('click', closeDialog);
    dialogOverlay.on('click', closeDialog);

    // Close on escape key
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && dialog.is(':visible')) {
            closeDialog();
        }
    });

    // Render purchased items
    function renderItems(items) {
        debugLog('Rendering items', { itemCount: items.length });
        purchasedItemsList.empty();

        items.forEach(function(item, index) {
            debugLog(`Rendering item ${index + 1}`, item);
            const itemElement = $('<div class="purchased-item">');
            
            // Item icon
            const icon = $('<div class="item-icon">');
            icon.html(getItemIcon(item.type));
            
            // Item details
            const details = $('<div class="item-details">');
            
            // Add badge if refunded
            if (item.refunded) {
                details.append($('<span class="item-badge refunded">').text('Refunded'));
            } else if (item.status && item.status.toLowerCase() !== 'active') {
                details.append($('<span class="item-badge">').text(item.status));
            }
            
            // Item name and description
            details.append($('<div class="item-name">').text(item.name));
            
            // Item meta information
            const metaInfo = [];
            
            // Add description
            if (item.description) {
                metaInfo.push(item.description);
            }
            
            // Add purchase date if available
            if (item.purchase_date) {
                const purchaseDate = new Date(item.purchase_date);
                if (!isNaN(purchaseDate.getTime())) {
                    metaInfo.push('Purchased: ' + purchaseDate.toLocaleDateString());
                }
            }
            
            // Add quantity if more than 1
            if (item.quantity > 1) {
                metaInfo.push(`Quantity: ${item.quantity}`);
            }
            
            // Add reference codes
            if (item.type === 'ticket' && item.ticket_ref) {
                metaInfo.push(`Ref: ${item.ticket_ref}`);
            } else if (item.type === 'addon' && item.addon_ref) {
                metaInfo.push(`Ref: ${item.addon_ref}`);
            }
            
            // Add ticket ID for QR code display
            if (item.type === 'ticket' && item.qr_code) {
                // Create a container for QR code that will be shown on click
                const qrContainer = $('<div class="qr-container">').hide();
                const qrCode = $('<div class="qr-code">');
                qrCode.append($('<img>').attr('src', item.qr_code).attr('alt', 'Ticket QR Code'));
                qrContainer.append(qrCode);
                
                // Create button to show QR code
                const qrButton = $('<button class="qr-button">').text('Show QR Code');
                qrButton.on('click', function(e) {
                    e.preventDefault();
                    qrContainer.toggle();
                    $(this).text(qrContainer.is(':visible') ? 'Hide QR Code' : 'Show QR Code');
                });
                
                details.append(qrButton);
                details.append(qrContainer);
            }
            
            // Add all meta info
            if (metaInfo.length > 0) {
                details.append($('<div class="item-meta">').text(metaInfo.join(' • ')));
            }
            
            // Item price
            const price = $('<div class="item-price">');
            if (item.price > 0) {
                price.text('₱' + parseFloat(item.price).toFixed(2));
            } else {
                price.text('Free');
            }
            
            // Add valid until for tickets
            if (item.type === 'ticket' && item.valid_until) {
                details.append($('<div class="item-valid-until">').text('Valid until: ' + new Date(item.valid_until).toLocaleDateString()));
            }
            
            // Assemble item
            itemElement.append(icon, details, price);
            
            // Add a class if this item is an addon
            if (item.type === 'addon') {
                itemElement.addClass('addon-item');
            }
            
            purchasedItemsList.append(itemElement);
        });
    }

    // Get appropriate icon based on item type
    function getItemIcon(type) {
        debugLog('Getting icon for item type', { type });
        const icons = {
            'ticket': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9V5c0-1.1.9-2 2-2h16a2 2 0 0 1 2 2v4"></path><path d="M2 15v4c0 1.1.9 2 2 2h16a2 2 0 0 0 2-2v-4"></path><path d="M4 9h16"></path><path d="M4 15h16"></path></svg>',
            'addon': '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>'
        };
        
        return icons[type] || icons.ticket;
    }
})(jQuery);
</script>

