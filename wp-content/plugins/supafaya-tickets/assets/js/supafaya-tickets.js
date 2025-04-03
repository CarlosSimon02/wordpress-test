jQuery(document).ready(function($) {
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
                        button.prop('disabled', false).html('Load More Events <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>');
                    }
                    
                    // Append the events to the container
                    appendEvents(events);
                } else {
                    button.parent().html('<p class="error">Error: ' + (response.message || 'Failed to load more events') + '</p>');
                }
            },
            error: function() {
                button.parent().html('<p class="error">Error: Failed to connect to the server</p>');
            }
        });
    });
    
    // Ticket purchase form
    $('.supafaya-ticket-checkout').on('click', '.quantity-decrease, .quantity-increase', function() {
        const button = $(this);
        const isIncrease = button.hasClass('quantity-increase');
        const input = button.siblings('.ticket-quantity');
        let value = parseInt(input.val()) || 0;
        
        if (isIncrease) {
            value++;
        } else if (value > 0) {
            value--;
        }
        
        input.val(value);
        updateOrderSummary();
    });
    
    $('.supafaya-ticket-checkout').on('input', '.ticket-quantity', function() {
        updateOrderSummary();
    });
    
    function updateOrderSummary() {
        const ticketItems = $('.supafaya-ticket-checkout .ticket-item');
        let totalAmount = 0;
        let totalQuantity = 0;
        const summaryItems = [];
        
        ticketItems.each(function() {
            const item = $(this);
            const price = parseFloat(item.data('price')) || 0;
            const quantity = parseInt(item.find('.ticket-quantity').val()) || 0;
            const amount = price * quantity;
            
            totalAmount += amount;
            totalQuantity += quantity;
            
            if (quantity > 0) {
                summaryItems.push({
                    name: item.find('.ticket-name').text(),
                    quantity: quantity,
                    amount: amount
                });
            }
        });
        
        // Update summary items
        const summaryContainer = $('.order-summary .summary-items');
        summaryContainer.empty();
        
        summaryItems.forEach(function(item) {
            summaryContainer.append(`
                <div class="summary-item">
                    <span>${item.name} x ${item.quantity}</span>
                    <span>$${item.amount.toFixed(2)}</span>
                </div>
            `);
        });
        
        // Update total
        $('.summary-total-amount').text('$' + totalAmount.toFixed(2));
        
        // Enable/disable checkout button
        $('.checkout-button').prop('disabled', totalQuantity === 0);
    }
    
    // Ticket purchase form submission
    $('.supafaya-ticket-checkout form').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitButton = form.find('.checkout-button');
        const eventId = form.data('event-id');
        
        // Collect ticket data
        const tickets = [];
        form.find('.ticket-item').each(function() {
            const item = $(this);
            const ticketId = item.data('ticket-id');
            const quantity = parseInt(item.find('.ticket-quantity').val()) || 0;
            
            if (quantity > 0) {
                tickets.push({
                    ticket_id: ticketId,
                    quantity: quantity
                });
            }
        });
        
        if (tickets.length === 0) {
            return;
        }
        
        submitButton.prop('disabled', true).text('Processing...');
        
        $.ajax({
            url: supafayaTickets.ajaxUrl,
            type: 'POST',
            data: {
                action: 'supafaya_purchase_ticket',
                event_id: eventId,
                ticket_id: tickets[0].ticket_id,
                quantity: tickets[0].quantity,
                nonce: supafayaTickets.nonce
            },
            success: function(response) {
                if (response.success) {
                    form.html('<div class="success"><p>Your tickets have been purchased successfully!</p></div>');
                } else {
                    form.find('.form-message').html('<p class="error">Error: ' + (response.message || 'Failed to purchase tickets') + '</p>');
                    submitButton.prop('disabled', false).text('Complete Purchase');
                }
            },
            error: function() {
                form.find('.form-message').html('<p class="error">Error: Failed to connect to the server</p>');
                submitButton.prop('disabled', false).text('Complete Purchase');
            }
        });
    });
    
    // Connect account form
    $('#supafaya-connect-form').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitButton = form.find('.connect-button');
        const resultDiv = $('#connection-result');
        
        submitButton.prop('disabled', true).text('Connecting...');
        
        $.ajax({
            url: supafayaTickets.ajaxUrl,
            type: 'POST',
            data: {
                action: 'supafaya_connect_account',
                email: form.find('#supafaya-email').val(),
                password: form.find('#supafaya-password').val(),
                nonce: supafayaTickets.nonce
            },
            success: function(response) {
                if (response.success) {
                    resultDiv.html('<div class="success"><p>' + response.message + '</p></div>').show();
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                } else {
                    resultDiv.html('<div class="error"><p>' + (response.message || 'Connection failed') + '</p></div>').show();
                    submitButton.prop('disabled', false).text('Connect Account');
                }
            },
            error: function() {
                resultDiv.html('<div class="error"><p>Failed to connect to the server</p></div>').show();
                submitButton.prop('disabled', false).text('Connect Account');
            }
        });
    });
    
    // Disconnect account
    $('#disconnect-account').on('click', function() {
        if (confirm('Are you sure you want to disconnect your Supafaya account?')) {
            // Implement disconnect functionality
            // This would be a separate AJAX call to disconnect the account
        }
    });
    
    // Helper function to append events to the grid
    function appendEvents(events) {
        const container = $('.supafaya-events-grid .events-container');
        
        events.forEach(function(event) {
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
                // Truncate description to 15 words
                const words = event.description.split(' ');
                const truncated = words.length > 15 ? words.slice(0, 15).join(' ') + '...' : event.description;
                descriptionHtml = `<div class="event-description">${truncated}</div>`;
            }
            
            const eventHtml = `
                <div class="event-card">
                    ${imageHtml}
                    <div class="event-content">
                        <div class="event-category">Event</div>
                        <h3 class="event-title">
                            <a href="${window.location.href.split('?')[0]}?event_id=${event.id}">
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
                            <a href="${window.location.href.split('?')[0]}?event_id=${event.id}" class="event-button">
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
        });
    }
}); 