<div class="supafaya-my-tickets">
    <?php if (empty($tickets)): ?>
        <p>You don't have any tickets yet.</p>
    <?php else: ?>
        <div class="tickets-list">
            <?php foreach ($tickets as $ticket): ?>
                <div class="ticket-item">
                    <div class="ticket-details">
                        <h3 class="ticket-event-name">
                            <?php echo esc_html($ticket['event_name']); ?>
                        </h3>
                        
                        <div class="ticket-info">
                            <div class="ticket-type">
                                <?php echo esc_html($ticket['ticket_type']); ?>
                            </div>
                            
                            <div class="ticket-date">
                                <?php echo date('F j, Y, g:i a', strtotime($ticket['event_date'])); ?>
                            </div>
                            
                            <?php if (!empty($ticket['location'])): ?>
                                <div class="ticket-location">
                                    <?php echo esc_html($ticket['location']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="ticket-id">
                            Ticket ID: <?php echo esc_html($ticket['id']); ?>
                        </div>
                    </div>
                    
                    <div class="ticket-actions">
                        <?php if (!empty($ticket['qr_code'])): ?>
                            <div class="ticket-qr-code">
                                <img src="<?php echo esc_url($ticket['qr_code']); ?>" alt="Ticket QR Code">
                            </div>
                        <?php endif; ?>
                        
                        <button class="ticket-download-button" data-ticket-id="<?php echo esc_attr($ticket['id']); ?>">
                            Download Ticket
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>