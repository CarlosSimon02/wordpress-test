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
                    <!-- Tabs -->
                    <div class="tabs-container">
                        <div class="tab-buttons">
                            <button class="tab-button active" data-tab="all">All Items</button>
                            <button class="tab-button" data-tab="tickets">Tickets</button>
                            <button class="tab-button" data-tab="addons">Add-ons</button>
                        </div>
                        
                        <div class="tab-content">
                            <div class="purchased-items-list">
                                <!-- Items will be populated by JavaScript -->
                            </div>
                        </div>
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
    box-shadow: 0 4px 12px rgba(66, 133, 244, 0.3);
    transition: all 0.3s ease;
}

.purchased-items-button:hover {
    background-color: #3367d6;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(66, 133, 244, 0.4);
}

.purchased-items-button:active {
    transform: translateY(0);
    box-shadow: 0 2px 8px rgba(66, 133, 244, 0.3);
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
    background-color: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    animation: fadeIn 0.2s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.dialog-content {
    position: relative;
    background-color: white;
    border-radius: 16px;
    width: 95%;
    max-width: 650px;
    max-height: 85vh;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    transform-origin: center;
    animation: scaleIn 0.2s ease-out;
    display: flex;
    flex-direction: column;
}

@keyframes scaleIn {
    from { 
        opacity: 0;
        transform: scale(0.95);
    }
    to { 
        opacity: 1;
        transform: scale(1);
    }
}

.dialog-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
    background-color: white;
    position: sticky;
    top: 0;
    z-index: 5;
}

.dialog-header h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
    color: #1a1a1a;
}

.dialog-close {
    background: none;
    border: none;
    padding: 8px;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    cursor: pointer;
    color: #666;
    transition: all 0.2s ease;
}

.dialog-close:hover {
    background-color: rgba(0, 0, 0, 0.05);
    color: #333;
}

.dialog-body {
    padding: 0;
    overflow-y: auto;
    flex: 1;
}

/* Loading State */
.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    text-align: center;
    color: #666;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid rgba(66, 133, 244, 0.1);
    border-top: 3px solid #4285f4;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 20px;
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
    padding: 60px 20px;
    text-align: center;
    color: #dc3545;
}

.error-state svg {
    margin-bottom: 20px;
    color: #dc3545;
}

.error-message {
    margin: 0;
    font-size: 16px;
    line-height: 1.6;
    max-width: 400px;
}

/* Empty State */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    text-align: center;
    color: #666;
}

.empty-state svg {
    margin-bottom: 20px;
    color: #999;
}

/* Tabs */
.tabs-container {
    width: 100%;
}

.tab-buttons {
    display: flex;
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
    background-color: #f9f9f9;
    position: sticky;
    top: 0;
    z-index: 4;
}

.tab-button {
    flex: 1;
    padding: 12px 16px;
    background: none;
    border: none;
    color: #666;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
}

.tab-button:after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 3px;
    background-color: #4285f4;
    transform: scaleX(0);
    transition: transform 0.2s ease;
}

.tab-button.active {
    color: #4285f4;
    font-weight: 600;
}

.tab-button.active:after {
    transform: scaleX(1);
}

.tab-button:hover {
    background-color: rgba(0, 0, 0, 0.03);
}

.tab-content {
    padding: 20px;
}

/* Content State */
.purchased-items-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.purchased-item {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 16px;
    background-color: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    transition: all 0.2s ease;
    border: 1px solid rgba(0, 0, 0, 0.06);
}

.purchased-item:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-1px);
}

.item-icon {
    width: 48px;
    height: 48px;
    background-color: #e8f0fe;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #4285f4;
    flex-shrink: 0;
}

.item-details {
    flex: 1;
    min-width: 0; /* For text overflow to work in flex child */
}

.item-name {
    font-weight: 600;
    color: #333;
    margin: 0 0 4px 0;
    font-size: 15px;
    word-break: break-word;
}

.item-meta {
    font-size: 13px;
    color: #666;
    margin-top: 4px;
    word-break: break-word;
}

.item-price {
    font-weight: 600;
    color: #4285f4;
    white-space: nowrap;
    font-size: 15px;
    flex-shrink: 0;
}

/* Item Badge */
.item-badge {
    display: inline-block;
    padding: 2px 8px;
    font-size: 11px;
    font-weight: 600;
    background-color: #f0f0f0;
    color: #666;
    border-radius: 20px;
    margin-bottom: 8px;
}

.item-badge.refunded {
    background-color: #ffdddd;
    color: #d32f2f;
}

/* Addon Styles */
.addon-item {
    margin-left: 24px;
    background-color: #f5f9ff;
    border-left: 3px solid #4285f4;
}

/* QR Code Button */
.qr-button {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 10px;
    padding: 6px 12px;
    background-color: #4285f4;
    color: white;
    border: none;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.qr-button:hover {
    background-color: #3367d6;
}

.qr-button svg {
    width: 14px;
    height: 14px;
}

/* QR Code Container */
.qr-container {
    margin-top: 12px;
    background-color: white;
    border-radius: 8px;
    padding: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    text-align: center;
}

.qr-code img {
    width: 180px;
    height: 180px;
    margin: 0 auto;
    display: block;
    border-radius: 8px;
}

.item-valid-until {
    font-size: 12px;
    color: #666;
    margin-top: 6px;
    font-style: italic;
}

/* Responsive Styles */
@media (max-width: 768px) {
    .dialog-content {
        width: 95%;
        max-height: 80vh;
        margin: 0 10px;
    }
    
    .dialog-header {
        padding: 16px 20px;
    }
    
    .tab-button {
        padding: 10px;
        font-size: 13px;
    }
    
    .tab-content {
        padding: 16px;
    }
    
    .purchased-item {
        padding: 14px;
        flex-direction: column;
    }
    
    .item-icon {
        width: 40px;
        height: 40px;
        margin-bottom: 8px;
    }
    
    .item-price {
        margin-top: 8px;
        align-self: flex-end;
    }
    
    .qr-code img {
        width: 150px;
        height: 150px;
    }
    
    .purchased-items-button {
        padding: 10px 16px;
        font-size: 13px;
        bottom: 15px;
        right: 15px;
    }
}

/* Animations */
.dialog-content {
    transform-origin: center;
    animation: scaleIn 0.2s ease-out;
}

@keyframes pulseAnimation {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}
</style>

<!-- The JavaScript code has been moved to /assets/js/purchased-items.js -->

