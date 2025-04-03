<div class="supafaya-events-grid">
    <div class="events-container">
        <?php if (empty($events)): ?>
            <div class="no-events-message">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <h3>No events found</h3>
                <p>Check back later for upcoming events</p>
            </div>
        <?php else: ?>
            <?php foreach ($events as $event): ?>
                <div class="event-card">
                    <?php if (!empty($event['poster_image'])): ?>
                        <div class="event-image">
                            <img src="<?php echo esc_url($event['poster_image']); ?>" alt="<?php echo esc_attr($event['title']); ?>" loading="lazy">
                            <div class="event-date-badge">
                                <span class="event-day"><?php echo date('d', strtotime($event['start_date'])); ?></span>
                                <span class="event-month"><?php echo date('M', strtotime($event['start_date'])); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="event-content">
                        <div class="event-category">Event</div>
                        <h3 class="event-title">
                            <a href="<?php echo esc_url(get_permalink() . '?event_id=' . $event['id']); ?>">
                                <?php echo esc_html($event['title']); ?>
                            </a>
                        </h3>
                        
                        <div class="event-meta">
                            <?php if (!empty($event['location'])): ?>
                                <div class="meta-item">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                        <circle cx="12" cy="10" r="3"></circle>
                                    </svg>
                                    <span><?php echo esc_html($event['location']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="meta-item">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <span><?php echo date('g:i A', strtotime($event['start_date'])); ?></span>
                            </div>
                        </div>
                        
                        <?php if (!empty($event['description'])): ?>
                            <div class="event-description">
                                <?php echo wp_trim_words($event['description'], 15, '...'); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="event-footer">
                            <a href="<?php echo esc_url(get_permalink() . '?event_id=' . $event['id']); ?>" class="event-button">
                                View Details
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                    <polyline points="12 5 19 12 12 19"></polyline>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if ($response['data']['data']['pagination']['has_more']): ?>
                <div class="load-more-container">
                    <button class="load-more-button" data-organization="<?php echo esc_attr($atts['organization_id']); ?>" data-next-cursor="<?php echo esc_attr($response['data']['data']['pagination']['next_page_cursor']); ?>">
                        Load More Events
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                    </button>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>