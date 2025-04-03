<div class="supafaya-ticket-checkout">
    <?php if (empty($tickets)): ?>
        <p>No tickets available for this event.</p>
    <?php else: ?>
        <form id="supafaya-ticket-form" data-event-id="<?php echo esc_attr($event['id']); ?>">
            <div class="tickets-list">
                <?php foreach ($tickets as $ticket): ?>
                    <div class="ticket-item" data-ticket-id="<?php echo esc_attr($ticket['id']); ?>">
                        <div class="ticket-info">
                            <h3 class="ticket-name"><?php echo esc_html($ticket['name']); ?></h3>
                            
                            <?php if (!empty($ticket['description'])): ?>
                                <div class="ticket-description">
                                    <?php echo esc_html($ticket['description']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="ticket-price">
                                <?php echo esc_html(number_format($ticket['price'], 2)); ?> <?php echo esc_html($ticket['currency'] ?? 'USD'); ?>
                            </div>
                            
                            <?php if (!empty($ticket['available_quantity'])): ?>
                                <div class="ticket-quantity-available">
                                    <?php echo esc_html($ticket['available_quantity']); ?> tickets available
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="ticket-actions">
                            <div class="quantity-selector">
                                <button type="button" class="quantity-decrease">-</button>
                                <input type="number" name="ticket_quantity[<?php echo esc_attr($ticket['id']); ?>]" value="0" min="0" max="<?php echo esc_attr($ticket['available_quantity'] ?? 10); ?>" class="ticket-quantity">
                                <button type="button" class="quantity-increase">+</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="order-summary">
                <h3>Order Summary</h3>
                <div class="summary-items"></div>
                <div class="summary-total">
                    <span class="total-label">Total:</span>
                    <span class="total-amount">0.00</span>
                </div>
            </div>
            
            <div class="checkout-actions">
                <button type="submit" class="checkout-button" disabled>Proceed to Checkout</button>
            </div>
        </form>
        
        <div id="checkout-result" style="display: none;"></div>
    <?php endif; ?>
</div>