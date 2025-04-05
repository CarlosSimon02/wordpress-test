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
        const eventPage = $('.supafaya-event-single');
        if (eventPage.length === 0) return;

        // Initialize cart from localStorage or create a new one
        const cart = loadCartFromStorage() || {
            tickets: {},
            addons: {},
            total: 0
        };
        
        // Save cart to localStorage
        function saveCartToStorage() {
            localStorage.setItem('supafaya_cart', JSON.stringify(cart));
        }
        
        // Load cart from localStorage
        function loadCartFromStorage() {
            const savedCart = localStorage.getItem('supafaya_cart');
            if (savedCart) {
                try {
                    return JSON.parse(savedCart);
                } catch (e) {
                    console.error('Error parsing cart from localStorage', e);
                    return null;
                }
            }
            return null;
        }

        // Handle ticket quantity changes
        $(document).on('click', '.ticket-item .quantity-decrease', function() {
            const input = $(this).siblings('.ticket-quantity');
            const currentVal = parseInt(input.val());
            if (currentVal > 1) {
                input.val(currentVal - 1);
            }
        });

        $(document).on('click', '.ticket-item .quantity-increase', function() {
            const input = $(this).siblings('.ticket-quantity');
            const currentVal = parseInt(input.val());
            const max = parseInt(input.attr('max') || 10);
            if (currentVal < max) {
                input.val(currentVal + 1);
            }
        });

        // Handle addon quantity changes
        $(document).on('click', '.addon-item .quantity-decrease', function() {
            const input = $(this).siblings('.addon-quantity');
            const currentVal = parseInt(input.val());
            if (currentVal > 1) {
                input.val(currentVal - 1);
            }
        });

        $(document).on('click', '.addon-item .quantity-increase', function() {
            const input = $(this).siblings('.addon-quantity');
            const currentVal = parseInt(input.val());
            const max = parseInt(input.attr('max') || 10);
            if (currentVal < max) {
                input.val(currentVal + 1);
            }
        });

        // Add ticket to cart
        $(document).on('click', '.add-to-cart:not(.add-addon-to-cart)', function() {
            const ticketItem = $(this).closest('.ticket-item');
            const ticketId = $(this).data('ticket-id');
            const ticketName = ticketItem.find('.ticket-name').text();
            const ticketPrice = parseFloat(ticketItem.find('.ticket-price').text().replace('₱', '').replace('$', '').replace(',', ''));
            const quantityToAdd = parseInt(ticketItem.find('.ticket-quantity').val() || 1);

            // Check if the ticket already exists in the cart
            if (cart.tickets[ticketId]) {
                // Add to the existing quantity
                cart.tickets[ticketId].quantity += quantityToAdd;
            } else {
                // Create a new entry
                cart.tickets[ticketId] = {
                    id: ticketId,
                    name: ticketName,
                    price: ticketPrice,
                    quantity: quantityToAdd
                };
            }

            // Reset quantity input to 1
            ticketItem.find('.ticket-quantity').val(1);

            // Show visual feedback
            const $button = $(this);
            
            // Store original text in data attribute to ensure we can restore it correctly
            if (!$button.data('original-text')) {
                $button.data('original-text', $button.html());
            }
            
            $button.html('<span>Added</span> <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>');
            
            setTimeout(() => {
                // Use the stored original text
                $button.html($button.data('original-text'));
            }, 1000);

            // Show a temporary notification
            const $notification = $('<div class="cart-notification">Added to cart</div>');
            $('body').append($notification);
            setTimeout(() => $notification.addClass('show'), 10);
            setTimeout(() => {
                $notification.removeClass('show');
                setTimeout(() => $notification.remove(), 300);
            }, 1500);

            updateOrderSummary();
            saveCartToStorage();
        });

        // Add addon to cart
        $(document).on('click', '.add-addon-to-cart', function() {
            const addonItem = $(this).closest('.addon-item');
            const addonId = $(this).data('addon-id');
            const addonName = addonItem.find('.ticket-name').text();
            const addonPrice = parseFloat(addonItem.find('.ticket-price').text().replace('₱', '').replace('$', '').replace(',', ''));
            const quantityToAdd = parseInt(addonItem.find('.addon-quantity, .ticket-quantity').val() || 1);

            // Check if the addon already exists in the cart
            if (cart.addons[addonId]) {
                // Add to the existing quantity
                cart.addons[addonId].quantity += quantityToAdd;
            } else {
                // Create a new entry
                cart.addons[addonId] = {
                    id: addonId,
                    name: addonName,
                    price: addonPrice,
                    quantity: quantityToAdd
                };
            }

            // Reset quantity input to 1
            addonItem.find('.addon-quantity, .ticket-quantity').val(1);

            // Show visual feedback
            const $button = $(this);
            const originalText = $button.html();
            
            // Store original text in data attribute to ensure we can restore it correctly
            if (!$button.data('original-text')) {
                $button.data('original-text', originalText);
            }
            
            $button.html('<span>Added</span> <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>');
            
            setTimeout(() => {
                // Use the stored original text
                $button.html($button.data('original-text'));
            }, 1000);

            // Show a temporary notification
            const $notification = $('<div class="cart-notification">Added to cart</div>');
            $('body').append($notification);
            setTimeout(() => $notification.addClass('show'), 10);
            setTimeout(() => {
                $notification.removeClass('show');
                setTimeout(() => $notification.remove(), 300);
            }, 1500);

            updateOrderSummary();
            saveCartToStorage();
        });

        // Clear cart
        $(document).on('click', '.clear-cart', function() {
            cart.tickets = {};
            cart.addons = {};
            cart.total = 0;
            updateOrderSummary();
            saveCartToStorage();
        });

        // Process checkout
        $(document).on('click', '.checkout-button', function() {
            // Check if user is logged in
            if (!supafayaTickets.isLoggedIn) {
                // Save return URL to session storage
                sessionStorage.setItem('supafaya_checkout_redirect', window.location.href);
                
                // Redirect to login page
                window.location.href = supafayaTickets.loginUrl;
                return;
            }
            
            // Prepare ticket data
            const tickets = [];
            const addons = [];

            // Format tickets for API
            for (const id in cart.tickets) {
                tickets.push({
                    ticket_id: id,
                    quantity: cart.tickets[id].quantity
                });
            }

            // Format addons for API
            for (const id in cart.addons) {
                addons.push({
                    addon_id: id,
                    quantity: cart.addons[id].quantity
                });
            }

            // Get event ID from URL
            const urlParams = new URLSearchParams(window.location.search);
            const eventId = urlParams.get('event_id');

            if (!eventId) {
                alert('Event ID not found in URL');
                return;
            }

            // Disable checkout button
            $(this).prop('disabled', true).text('Processing...');

            // Send AJAX request
            $.ajax({
                url: supafayaTickets.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'supafaya_purchase_ticket',
                    nonce: supafayaTickets.nonce,
                    event_id: eventId,
                    tickets: tickets,
                    addons: addons
                },
                success: function(response) {
                    // Re-enable checkout button
                    $('.checkout-button').prop('disabled', false).text('Proceed to Checkout');

                    if (response.success) {
                        // Show success message
                        $('.event-right-column').html(`
                            <div class="checkout-success">
                                <h3>Thank you for your purchase!</h3>
                                <p>${response.message || 'Your tickets have been booked successfully.'}</p>
                                <p>Reference: ${response.data?.reference_id || ''}</p>
                                <p><a href="/my-tickets" class="view-tickets-button">View My Tickets</a></p>
                            </div>
                        `);
                    } else {
                        // Show error message
                        alert('Error: ' + (response.message || 'An error occurred'));
                    }
                },
                error: function() {
                    // Re-enable checkout button
                    $('.checkout-button').prop('disabled', false).text('Proceed to Checkout');
                    alert('A network error occurred. Please try again.');
                }
            });
        });

        function updateOrderSummary() {
            const summaryContainer = $('.summary-items');
            let html = '';
            let total = 0;
            let totalItems = 0;

            // Add tickets to summary
            for (const id in cart.tickets) {
                const ticket = cart.tickets[id];
                const itemTotal = ticket.price * ticket.quantity;
                total += itemTotal;
                totalItems += ticket.quantity;

                html += `
                    <div class="summary-item" data-id="${id}" data-type="ticket">
                        <div class="item-info">
                            <span class="item-name">${ticket.name}</span>
                            <span class="item-quantity">x${ticket.quantity}</span>
                        </div>
                        <div class="item-price">₱${itemTotal.toFixed(2)}</div>
                        <button class="remove-item" title="Remove item">×</button>
                    </div>
                `;
            }

            // Add addons to summary
            for (const id in cart.addons) {
                const addon = cart.addons[id];
                const itemTotal = addon.price * addon.quantity;
                total += itemTotal;
                totalItems += addon.quantity;

                html += `
                    <div class="summary-item" data-id="${id}" data-type="addon">
                        <div class="item-info">
                            <span class="item-name">${addon.name} (Add-on)</span>
                            <span class="item-quantity">x${addon.quantity}</span>
                        </div>
                        <div class="item-price">₱${itemTotal.toFixed(2)}</div>
                        <button class="remove-item" title="Remove item">×</button>
                    </div>
                `;
            }

            // If cart is empty, show a message
            if (totalItems === 0) {
                html = '<div class="empty-cart-message">Your cart is empty</div>';
            }

            summaryContainer.html(html);
            $('.total-amount').text(`₱${total.toFixed(2)}`);
            cart.total = total;

            // Update cart count if there's an element for it
            if ($('.cart-count').length) {
                $('.cart-count').text(totalItems > 0 ? totalItems : '');
            }

            // Show or hide checkout button based on cart contents
            if (totalItems > 0) {
                $('.checkout-button').show();
            } else {
                $('.checkout-button').hide();
            }
        }

        // Remove items from cart
        $(document).on('click', '.remove-item', function() {
            const item = $(this).closest('.summary-item');
            const id = item.data('id');
            const type = item.data('type');
            
            if (type === 'ticket') {
                delete cart.tickets[id];
            } else if (type === 'addon') {
                delete cart.addons[id];
            }
            
            updateOrderSummary();
            saveCartToStorage();
        });

        // Initialize with empty cart
        updateOrderSummary();
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