<div class="supafaya-event-single">
    <div class="event-container">
        <!-- Left Column (Sticky) -->
        <aside class="event-sidebar">
            <?php if (!empty($event['poster_image'])): ?>
                <div class="event-media">
                    <img src="<?php echo esc_url($event['poster_image']); ?>" 
                         alt="<?php echo esc_attr($event['title']); ?>" 
                         class="event-poster"
                         loading="lazy">
                </div>
            <?php endif; ?>

            <div class="event-info-card">
                <h1 class="event-title"><?php echo esc_html($event['title']); ?></h1>
                
                <div class="event-meta">
                    <div class="meta-item">
                        <svg class="meta-icon" viewBox="0 0 24 24" width="20" height="20">
                            <path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zM5 8V6h14v2H5z"/>
                        </svg>
                        <div class="meta-content">
                            <span class="meta-label">Date & Time</span>
                            <time datetime="<?php echo esc_attr($event['start_date']); ?>">
                                <?php echo date('F j, Y', strtotime($event['start_date'])); ?>
                            </time>
                            <span class="meta-time">
                                <?php echo date('g:i A', strtotime($event['start_date'])); ?> - 
                                <?php echo date('g:i A', strtotime($event['end_date'])); ?>
                            </span>
                        </div>
                    </div>

                    <?php if (!empty($event['location'])): ?>
                    <div class="meta-item">
                        <svg class="meta-icon" viewBox="0 0 24 24" width="20" height="20">
                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                        </svg>
                        <div class="meta-content">
                            <span class="meta-label">Location</span>
                            <?php echo esc_html($event['location']); ?>
                            <?php if (!empty($event['city']) || !empty($event['country'])): ?>
                            <div class="meta-location">
                                <?php echo esc_html(implode(', ', array_filter([$event['city'] ?? '', $event['country'] ?? '']))); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($event['organizer_details'])): ?>
                    <div class="meta-item">
                        <svg class="meta-icon" viewBox="0 0 24 24" width="20" height="20">
                            <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                        </svg>
                        <div class="meta-content">
                            <span class="meta-label">Organized By</span>
                            <div class="organizer-details">
                                <?php echo esc_html($event['organizer_details']['full_name']); ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($event['tags']) || !empty($event['categories'])): ?>
                <div class="event-tags">
                    <?php foreach (array_merge($event['tags'] ?? [], $event['categories'] ?? []) as $tag): ?>
                        <span class="event-tag"><?php echo esc_html($tag); ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($event['description'])): ?>
                <div class="event-description">
                    <h3 class="description-heading">About the Event</h3>
                    <div class="description-content">
                        <?php echo wpautop($event['description']); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </aside>

        <!-- Right Column (Main Content) -->
        <main class="event-main">
            <section class="ticket-section">
                <h2 class="section-heading">Available Tickets</h2>
                <div class="tickets-container">
                    <?php echo do_shortcode('[supafaya_ticket_checkout event_id="' . esc_attr($event['id']) . '"]'); ?>
                </div>
            </section>

            <?php if (!empty($event['details'])): ?>
            <section class="event-details">
                <h2 class="section-heading">Event Details</h2>
                <div class="details-content">
                    <?php echo wp_kses_post($event['details']); ?>
                </div>
            </section>
            <?php endif; ?>

            <section class="event-location">
                <h2 class="section-heading">Venue Location</h2>
                <div class="map-placeholder">
                    <!-- Google Maps integration placeholder -->
                    <div class="map-fallback">
                        <svg viewBox="0 0 24 24" width="48" height="48">
                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5S10.62 6.5 12 6.5s2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                        </svg>
                        <p>Map preview for <?php echo esc_html($event['location']); ?></p>
                    </div>
                </div>
            </section>

            <?php if (!empty($event['participants'])): ?>
            <section class="event-participants">
                <h2 class="section-heading">
                    Participants <span class="participant-count">(<?php echo esc_html($event['total_participants']); ?>)</span>
                </h2>
                <div class="participants-grid">
                    <?php foreach ($event['participants'] as $participant): ?>
                    <div class="participant-card">
                        <?php if (!empty($participant['photo_url'])): ?>
                        <img src="<?php echo esc_url($participant['photo_url']); ?>" 
                             alt="<?php echo esc_attr($participant['full_name']); ?>" 
                             class="participant-avatar"
                             loading="lazy">
                        <?php endif; ?>
                        <div class="participant-info">
                            <h3 class="participant-name"><?php echo esc_html($participant['full_name']); ?></h3>
                            <?php if (!empty($participant['role'])): ?>
                            <p class="participant-role"><?php echo esc_html($participant['role']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
        </main>
    </div>
</div>