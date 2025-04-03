<div class="supafaya-event-single">
    <div class="event-header">
        <?php if (!empty($event['banner_image_url'])): ?>
            <div class="event-banner">
                <img src="<?php echo esc_url($event['banner_image_url']); ?>" alt="<?php echo esc_attr($event['name']); ?>">
            </div>
        <?php endif; ?>
        
        <h1 class="event-title"><?php echo esc_html($event['name']); ?></h1>
        
        <div class="event-meta">
            <div class="event-dates">
                <span class="meta-label">Date:</span>
                <span class="meta-value">
                    <?php echo date('F j, Y, g:i a', strtotime($event['start_date'])); ?> - 
                    <?php echo date('F j, Y, g:i a', strtotime($event['end_date'])); ?>
                </span>
            </div>
            
            <?php if (!empty($event['location'])): ?>
                <div class="event-location">
                    <span class="meta-label">Location:</span>
                    <span class="meta-value"><?php echo esc_html($event['location']); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($event['organizer_details'])): ?>
                <div class="event-organizer">
                    <span class="meta-label">Organizer:</span>
                    <span class="meta-value"><?php echo esc_html($event['organizer_details']['full_name']); ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="event-content">
        <?php if (!empty($event['description'])): ?>
            <div class="event-description">
                <h2>About This Event</h2>
                <div class="description-content">
                    <?php echo wpautop($event['description']); ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="event-tickets">
            <h2>Tickets</h2>
            <div class="tickets-content">
                <?php echo do_shortcode('[supafaya_ticket_checkout event_id="' . esc_attr($event['id']) . '"]'); ?>
            </div>
        </div>
        
        <?php if (!empty($event['participants']) && count($event['participants']) > 0): ?>
            <div class="event-participants">
     <h2>Participants (<?php echo esc_html($event['total_participants']); ?>)</h2>
                <div class="participants-list">
                    <?php foreach ($event['participants'] as $participant): ?>
                        <div class="participant">
                            <?php if (!empty($participant['photo_url'])): ?>
                                <img src="<?php echo esc_url($participant['photo_url']); ?>" alt="<?php echo esc_attr($participant['full_name']); ?>" class="participant-avatar">
                            <?php endif; ?>
                            <span class="participant-name"><?php echo esc_html($participant['full_name']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>