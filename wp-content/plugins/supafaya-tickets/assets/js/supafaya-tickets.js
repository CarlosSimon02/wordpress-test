(function($) {
    'use strict';
    
    // Authentication handling
    function initAuthForms() {
        // Connect Supafaya account form
        $('#supafaya-connect-form').on('submit', function(e) {
            e.preventDefault();
            
            const email = $('#supafaya-email').val();
            const password = $('#supafaya-password').val();
            
            $('#connection-result').hide();
            
            $.ajax({
                url: supafayaTickets.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'supafaya_connect_account',
                    email: email,
                    password: password,
                    nonce: supafayaTickets.nonce
                },
                success: function(response) {
                    $('#connection-result')
                        .html(`<p class="${response.success ? 'success' : 'error'}">${response.message}</p>`)
                        .show();
                    
                    if (response.success) {
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    }
                },
                error: function() {
                    $('#connection-result')
                        .html('<p class="error">An error occurred. Please try again.</p>')
                        .show();
                }
            });
        });
        
        // Disconnect account
        $('#disconnect-account').on('click', function() {
            if (confirm('Are you sure you want to disconnect your Supafaya account?')) {
                $.ajax({
                    url: supafayaTickets.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'supafaya_disconnect_account',
                        nonce: supafayaTickets.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            window.location.reload();
                        } else {
                            alert(response.message || 'An error occurred');
                        }
                    }
                });
            }
        });
    }
    
    // Events listing
    function initEventsListing() {
        // Load more events
        $('.supafaya-events-grid').on('click', '.load-more-button', function() {
            const button = $(this);
            const organizationId = button.data('organization');
            const nextCursor = button.data('next-cursor');
            
            button.prop('disabled', true).text('Loading...');
            
            $.ajax({
                url: supafayaTickets.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'supafaya_load_events',
                    organization_id: organizationId,
                    next_cursor: nextCursor,
                    nonce: supafayaTickets.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const events = response.data.data.results;
                        const pagination = response.data.data.pagination;
                        
                        // Remove load more button if no more events
                        if (!pagination.has_more) {
                            button.parent().remove();
                        } else {
                            // Update next cursor for the button
                            button.data('next-cursor', pagination.next_page_cursor);
                            button.prop('disabled', false).text('Load More Events');
                        }
                        
                        // Append the events to the container
                        events.forEach(function(event) {
                            appendEvent(event);
                        });
                    } else {
                        button.parent().html('<p class="error">Error: ' + (response.message || 'Failed to load more events') + '</p>');
                    }
                },
                error: function() {
                    button.parent().html('<p class="error">Error: Failed to connect to the server</p>');
                }
            });
        });
    }
    
    // Ticket checkout
    function initTicketCheckout() {
        const $form = $('#supafaya-ticket-form');
        
        if (!$form.length) return;
        
        const $summaryItems = $form.find('.summary-items');
        const $totalAmount = $form.find('.total-amount');
        const $checkoutButton = $form.find('.checkout-button');
        const eventId = $form.data('event-id');
        
        // Quantity selector
        $form.on('click', '.quantity-decrease, .quantity-increase', function() {
            const $button = $(this);
            const $input = $button.closest('.quantity-selector').find('.ticket-quantity');
            let value = parseInt($input.val());
            
            if ($button.hasClass('quantity-decrease')) {
                value = Math.max(0, value - 1);
            } else {
                value = Math.min(parseInt($input.attr('max')), value + 1);
            }
            
            $input.val(value).trigger('change');
        });
        
        // Update summary on quantity change
        $form.on('change', '.ticket-quantity', function() {
            updateOrderSummary();
        });
        
        function updateOrderSummary() {
            let total = 0;
            let hasItems = false;
            
            $summaryItems.empty();
            
            $form.find('.ticket-item').each(function() {
                const $item = $(this);
                const ticketId = $item.data('ticket-id');
                const ticketName = $item.find('.ticket-name').text();
                const ticketPrice = parseFloat($item.find('.ticket-price').text());
                const $quantity = $item.find('.ticket-quantity');
                const quantity = parseInt($quantity.val());
                
                if (quantity > 0) {
                    hasItems = true;
                    const itemTotal = ticketPrice * quantity;
                    total += itemTotal;
                    
                    $summaryItems.append(`
                        <div class="summary-item" data-ticket-id="${ticketId}">
                            <div class="item-name">${ticketName} x ${quantity}</div>
                            <div class="item-price">${itemTotal.toFixed(2)}</div>
                        </div>
                    `);
                }
            });
            
            $totalAmount.text(total.toFixed(2));
            $checkoutButton.prop('disabled', !hasItems);
        }
        
        // Handle form submission
        $form.on('submit', function(e) {
            e.preventDefault();
            
            const $result = $('#checkout-result');
            $result.hide();
            
            const tickets = [];
            
            $form.find('.summary-item').each(function() {
                const $item = $(this);
                const ticketId = $item.data('ticket-id');
                const quantity = parseInt($item.find('.item-name').text().split(' x ')[1]);
                
                tickets.push({
                    ticket_id: ticketId,
                    quantity: quantity
                });
            });
            
            if (tickets.length === 0) {
                $result.html('<p class="error">Please select at least one ticket</p>').show();
                return;
            }
            
            $checkoutButton.prop('disabled', true).text('Processing...');
            
            $.ajax({
                url: supafayaTickets.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'supafaya_purchase_ticket',
                    event_id: eventId,
                    tickets: tickets,
                    nonce: supafayaTickets.nonce
                },
                success: function(response) {
                    $checkoutButton.prop('disabled', false).text('Proceed to Checkout');
                    
                    if (response.success) {
                        $form.hide();
                        $result.html(`
                            <div class="checkout-success">
                                <h3>Thank you for your purchase!</h3>
                                <p>${response.message || 'Your tickets have been booked successfully.'}</p>
                                <p>Reference: ${response.data?.reference_id || ''}</p>
                                <p><a href="/my-tickets" class="view-tickets-button">View My Tickets</a></p>
                            </div>
                        `).show();
                    } else {
                        $result.html(`<p class="error">${response.message || 'An error occurred'}</p>`).show();
                    }
                },
                error: function() {
                    $checkoutButton.prop('disabled', false).text('Proceed to Checkout');
                    $result.html('<p class="error">A network error occurred. Please try again.</p>').show();
                }
            });
        });
    }
    
    // Admin settings page
    function initAdminSettings() {
        $('#test-connection').on('click', function() {
            const $button = $(this);
            const $result = $('#connection-test-result');
            
            $button.prop('disabled', true).text('Testing...');
            $result.html('<p>Testing connection...</p>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'supafaya_test_connection',
                    nonce: supafayaTickets.nonce
                },
                success: function(response) {
                    $button.prop('disabled', false).text('Test Connection');
                    
                    if (response.success) {
                        $result.html(`<p class="success">Connection successful! API version: ${response.data?.version || 'Unknown'}</p>`);
                    } else {
                        $result.html(`<p class="error">Connection failed: ${response.message || 'Unknown error'}</p>`);
                    }
                },
                error: function() {
                    $button.prop('disabled', false).text('Test Connection');
                    $result.html('<p class="error">A network error occurred. Please check your API URL.</p>');
                }
            });
        });
    }
    
    // Helper function to append event to grid
    function appendEvent(event) {
        const container = $('.supafaya-events-grid .events-container');
        
        let imageHtml = '';
        if (event.poster_image) {
            imageHtml = `
                <div class="event-image">
                    <img src="${event.poster_image}" alt="${event.title}" loading="lazy">
                    <div class="event-date-badge">
                        <span class="event-day">${new Date(event.start_date).getDate()}</span>
                        <span class="event-month">${new Date(event.start_date).toLocaleString('default', { month: 'short' })}</span>
                    </div>
                </div>
            `;
        } else if (event.banner_image_url) {
            imageHtml = `
                <div class="event-image">
                    <img src="${event.banner_image_url}" alt="${event.title}" loading="lazy">
                    <div class="event-date-badge">
                        <span class="event-day">${new Date(event.start_date).getDate()}</span>
                        <span class="event-month">${new Date(event.start_date).toLocaleString('default', { month: 'short' })}</span>
                    </div>
                </div>
            `;
        }
        
        let locationHtml = '';
        if (event.location) {
            locationHtml = `
                <div class="meta-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                        <circle cx="12" cy="10" r="3"></circle>
                    </svg>
                    <span>${event.location}</span>
                </div>
            `;
        }
        
        let descriptionHtml = '';
        if (event.description) {
            const words = event.description.split(' ');
            const truncated = words.length > 15 ? words.slice(0, 15).join(' ') + '...' : event.description;
            descriptionHtml = `<div class="event-description">${truncated}</div>`;
        }
        
        // Get the event page URL from settings, or use current page as fallback
        const eventPageUrl = window.location.href.split('?')[0];
        
        const eventHtml = `
            <div class="event-card">
                ${imageHtml}
                <div class="event-content">
                    <div class="event-category">Event</div>
                    <h3 class="event-title">
                        <a href="${eventPageUrl}?event_id=${event.id}">
                            ${event.title}
                        </a>
                    </h3>
                    <div class="event-meta">
                        ${locationHtml}
                        <div class="meta-item">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            <span>${new Date(event.start_date).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })}</span>
                        </div>
                    </div>
                    ${descriptionHtml}
                    <div class="event-footer">
                        <a href="${eventPageUrl}?event_id=${event.id}" class="event-button">
                            View Details
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        `;
        
        container.append(eventHtml);
    }
    
    // Initialize all components
    $(function() {
        // Load CSS
        $('head').append('<link rel="stylesheet" type="text/css" href="' + supafayaTickets.pluginUrl + 'assets/css/supafaya-tickets.css">');
        
        initAuthForms();
        initEventsListing();
        initTicketCheckout();
        
        // Only initialize admin settings on admin pages
        if (typeof ajaxurl !== 'undefined') {
            initAdminSettings();
        }
    });
    
})(jQuery);