<?php
// Add this at the very top of your event-default.php file

// Debug section - Shows the structure of the event data
if (isset($_GET['debug'])) {
    echo '<div style="background: #f5f5f5; padding: 15px; margin-bottom: 20px; border: 1px solid #ddd; font-family: monospace; white-space: pre-wrap;">';
    echo '<h3>Debug: Event Data Structure</h3>';
    echo '<p>tickets exists: ' . (isset($event['tickets']) ? 'YES' : 'NO') . '</p>';
    
    // If tickets exists, show the structure
    if (isset($event['tickets'])) {
        echo '<p>First ticket:</p>';
        print_r(reset($event['tickets'])); 
    } else {
        // Check what properties are available
        echo '<p>Available properties:</p>';
        print_r(array_keys($event));
    }
    
    // Show the first few levels of the event data
    echo '<p>Event data excerpt:</p>';
    print_r(array_slice($event, 0, 5, true));
    echo '</div>';
}
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
            
            if ($tickets_data): 
            ?>
                <div class="event-tickets">
                    <h2>Tickets</h2>
                    <div class="tickets-list">
                        <?php foreach ($tickets_data as $ticket): ?>
                            <?php 
                            // Normalize ticket data - handle different structures
                            $ticket_status = isset($ticket['status']) ? strtolower($ticket['status']) : 'active';
                            $ticket_name = isset($ticket['name']) ? $ticket['name'] : (isset($ticket['title']) ? $ticket['title'] : 'Ticket');
                            $ticket_desc = isset($ticket['description']) ? $ticket['description'] : '';
                            $ticket_price = isset($ticket['price']) ? $ticket['price'] : 0;
                            $ticket_id = isset($ticket['ticket_id']) ? $ticket['ticket_id'] : (isset($ticket['id']) ? $ticket['id'] : '');
                            $ticket_quantity = isset($ticket['quantity']) ? $ticket['quantity'] : 10;
                            
                            if (strtolower($ticket_status) === 'active'): 
                            ?>
                                <div class="ticket-item">
                                    <div class="ticket-info">
                                        <h3 class="ticket-name"><?php echo esc_html($ticket_name); ?></h3>
                                        <?php if (!empty($ticket_desc)): ?>
                                            <p class="ticket-description"><?php echo esc_html($ticket_desc); ?></p>
                                        <?php endif; ?>
                                        <div class="ticket-price">$<?php echo number_format($ticket_price, 2); ?></div>
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
                            <?php endif; ?>
                        <?php endforeach; ?>
                        
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

